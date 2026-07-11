<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Notifications\TimetableChanged;
use App\Support\SafeNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

class TimetableService
{
    /**
     * Round-robins the class's assigned subjects across whatever day/period
     * slots are still empty for this class+term - never touches a slot that
     * already has an entry, whether that entry came from a previous
     * generate() call or a manual edit, so it's always safe to re-run.
     * Skips a subject with no active teacher assignment (nothing to
     * actually put on the timetable yet) and skips placing a subject into a
     * slot that would double-book its teacher into a different class at
     * the same day+period.
     *
     * @return array{created: int, skipped: int}
     */
    public function generate(SchoolClass $class, Term $term): array
    {
        $assignedSubjectIds = ClassSubject::where('class_id', $class->id)->where('term_id', $term->id)->pluck('subject_id');

        if ($assignedSubjectIds->isEmpty()) {
            throw new RuntimeException('This class has no subjects assigned for this term yet.');
        }

        // One query for every class's active teacher assignments this term,
        // instead of the old per-slot-attempt query: this map answers both
        // "who teaches this class's subjects" and "who's already busy
        // elsewhere at a given slot" without touching the database again
        // for the rest of this method.
        $teacherMap = TeacherSubjectAssignment::activeTeacherMap($term->id);

        $schedulableSubjectIds = $assignedSubjectIds
            ->filter(fn ($subjectId) => $teacherMap->has("{$class->id}:{$subjectId}"))
            ->unique()
            ->values();

        if ($schedulableSubjectIds->isEmpty()) {
            throw new RuntimeException("None of this class's subjects have an assigned teacher yet.");
        }

        $periods = Period::orderBy('sort_order')->get();

        if ($periods->isEmpty()) {
            throw new RuntimeException('No periods have been configured yet.');
        }

        $existingEntries = TimetableEntry::where('term_id', $term->id)->get(['class_id', 'day_of_week', 'period_id', 'subject_id']);

        $existingSlotKeys = $existingEntries->where('class_id', $class->id)
            ->map(fn (TimetableEntry $entry) => "{$entry->day_of_week}:{$entry->period_id}")
            ->all();

        // "day:period:teacher_id" already taken by a DIFFERENT class - built
        // once, up front, from every other class's entries this term.
        $busySlots = [];

        foreach ($existingEntries as $entry) {
            if ($entry->class_id === $class->id) {
                continue;
            }

            $teacher = $teacherMap->get("{$entry->class_id}:{$entry->subject_id}");

            if ($teacher) {
                $busySlots["{$entry->day_of_week}:{$entry->period_id}:{$teacher->id}"] = true;
            }
        }

        $created = 0;
        $skipped = 0;
        $cursor = 0;
        $subjectCount = $schedulableSubjectIds->count();

        foreach (TimetableEntry::DAYS as $day) {
            foreach ($periods as $period) {
                $slotKey = "{$day}:{$period->id}";

                if (in_array($slotKey, $existingSlotKeys, true)) {
                    continue;
                }

                $placed = false;

                for ($attempt = 0; $attempt < $subjectCount; $attempt++) {
                    $subjectId = $schedulableSubjectIds[$cursor % $subjectCount];
                    $cursor++;

                    $teacherId = $teacherMap->get("{$class->id}:{$subjectId}")->id;

                    if (! isset($busySlots["{$day}:{$period->id}:{$teacherId}"])) {
                        try {
                            TimetableEntry::create([
                                'class_id' => $class->id, 'term_id' => $term->id, 'period_id' => $period->id,
                                'day_of_week' => $day, 'subject_id' => $subjectId,
                            ]);
                            $created++;
                        } catch (UniqueConstraintViolationException) {
                            // Another concurrent generate()/setEntry() call filled this
                            // exact slot after our $existingSlotKeys snapshot was taken -
                            // it's no longer empty, not a failure to schedule it.
                        }

                        $placed = true;

                        break;
                    }
                }

                if (! $placed) {
                    $skipped++;
                }
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Manual single-slot set (or clear, if $subject is null). Rejects a
     * placement that would double-book the resolved teacher into another
     * class at the same day+period. Notifies the affected teacher, unlike
     * generate() - a bulk initial schedule isn't a "change" to tell anyone
     * about, but an individual edit to an existing timetable is.
     */
    public function setEntry(SchoolClass $class, Term $term, Period $period, string $day, ?Subject $subject, User $changedBy): ?TimetableEntry
    {
        if (! in_array($day, TimetableEntry::DAYS, true)) {
            throw new RuntimeException('Invalid day of week.');
        }

        $existing = TimetableEntry::where([
            'class_id' => $class->id, 'term_id' => $term->id, 'period_id' => $period->id, 'day_of_week' => $day,
        ])->first();

        if (! $subject) {
            if ($existing) {
                $existing->delete();
                $this->notifyAffectedTeacher($existing, $changedBy, cleared: true);
            }

            return null;
        }

        if ($this->teacherIsBusyElsewhere($class, $term, $day, $period, $subject->id)) {
            throw new RuntimeException("This subject's teacher is already scheduled in another class at this time.");
        }

        $entry = TimetableEntry::updateOrCreate(
            ['class_id' => $class->id, 'term_id' => $term->id, 'period_id' => $period->id, 'day_of_week' => $day],
            ['subject_id' => $subject->id]
        );

        $this->notifyAffectedTeacher($entry, $changedBy);

        return $entry;
    }

    /**
     * True if the teacher who'd teach $subjectId for $class/$term is
     * already placed in a DIFFERENT class at this exact day+period.
     */
    private function teacherIsBusyElsewhere(SchoolClass $class, Term $term, string $day, Period $period, int $subjectId): bool
    {
        $teacher = TeacherSubjectAssignment::where([
            'class_id' => $class->id, 'subject_id' => $subjectId, 'term_id' => $term->id, 'is_active' => true,
        ])->first()?->teacher;

        if (! $teacher) {
            return false;
        }

        $teacherMap = TeacherSubjectAssignment::activeTeacherMap($term->id);

        return TimetableEntry::where('term_id', $term->id)
            ->where('day_of_week', $day)
            ->where('period_id', $period->id)
            ->where('class_id', '!=', $class->id)
            ->get(['class_id', 'subject_id'])
            ->contains(fn (TimetableEntry $entry) => $teacherMap->get("{$entry->class_id}:{$entry->subject_id}")?->id === $teacher->id);
    }

    private function notifyAffectedTeacher(TimetableEntry $entry, User $changedBy, bool $cleared = false): void
    {
        $teacher = $entry->teacher();

        if ($teacher && $teacher->user_id !== $changedBy->id) {
            SafeNotifier::send($teacher->user, new TimetableChanged($entry, $cleared));
        }
    }
}
