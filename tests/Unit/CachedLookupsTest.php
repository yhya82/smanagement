<?php

namespace Tests\Unit;

use App\Enums\CalendarEventType;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\SchoolSetting;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Term::active()/AcademicYear::active()/SchoolSetting::current()/
 * CalendarEvent::holidayOn() are cached (all previously a fresh query on
 * nearly every request) - these are plain model-level tests, not
 * HTTP/Livewire ones, because what's actually being verified is the
 * model's own read/invalidate logic, not any particular page.
 */
class CachedLookupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_term_active_is_cached_and_invalidated_when_activation_changes(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $termA = Term::create(['academic_year_id' => $year->id, 'name' => 'Term A', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'is_active' => true]);

        $this->assertSame($termA->id, Term::active()->id);

        // A raw DB write bypassing the model (simulating a stale cache from
        // before this second term existed) should still be masked by the
        // cache until something actually busts it.
        \Illuminate\Support\Facades\DB::table('terms')->where('id', $termA->id)->update(['is_active' => false]);
        $this->assertSame($termA->id, Term::active()->id, 'Cache should still hold the old value until a model write busts it.');

        $termB = Term::create(['academic_year_id' => $year->id, 'name' => 'Term B', 'start_date' => '2026-12-02', 'end_date' => '2027-03-01', 'is_active' => true]);

        $this->assertSame($termB->id, Term::active()->id, 'Creating a new term must bust the cache so the new active term is visible immediately.');
    }

    public function test_academic_year_active_is_cached_and_invalidated_on_activate(): void
    {
        $yearA = AcademicYear::create(['name' => 'Year A', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_active' => true]);

        $this->assertSame($yearA->id, AcademicYear::active()->id);

        $yearB = AcademicYear::create(['name' => 'Year B', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => false]);
        $yearA->update(['is_active' => false]);
        $yearB->update(['is_active' => true]);

        $this->assertSame($yearB->id, AcademicYear::active()->id);
    }

    public function test_school_setting_current_is_cached_and_invalidated_on_update(): void
    {
        $setting = SchoolSetting::current();
        $this->assertSame('School Management', $setting->name);

        $setting->update(['name' => 'Sunrise Academy']);

        $this->assertSame('Sunrise Academy', SchoolSetting::current()->name);
    }

    public function test_calendar_event_holiday_on_is_cached_and_invalidated_on_write(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term A', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'is_active' => true]);

        $this->assertNull(CalendarEvent::holidayOn('2026-10-05'));

        $holiday = CalendarEvent::create([
            'term_id' => $term->id, 'date' => '2026-10-05', 'title' => 'National Day', 'type' => CalendarEventType::Holiday,
        ]);

        $this->assertTrue(CalendarEvent::isHoliday('2026-10-05'), 'Creating the holiday must bust the cached null result.');

        // Moving it to a different date must bust both the old date's
        // cached "yes" and the new date's cached "no".
        $this->assertFalse(CalendarEvent::isHoliday('2026-10-06'));
        $holiday->update(['date' => '2026-10-06']);

        $this->assertFalse(CalendarEvent::isHoliday('2026-10-05'), 'The old date must no longer report a holiday once it moved.');
        $this->assertTrue(CalendarEvent::isHoliday('2026-10-06'));

        $holiday->delete();

        $this->assertFalse(CalendarEvent::isHoliday('2026-10-06'), 'Deleting the event must bust the cache too.');
    }
}
