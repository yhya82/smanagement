<?php

namespace App\Models;

use App\Enums\CalendarEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CalendarEvent extends Model
{
    protected $fillable = [
        'term_id',
        'date',
        'title',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => CalendarEventType::class,
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Attendance marking and the timetable calendar both need a fast "is
     * this exact date a public holiday" check without caring which term
     * it falls in - called on every attendance-marking request, so cached
     * per date the same way Term/AcademicYear/SchoolSetting cache their own
     * hot lookups.
     */
    public static function holidayOn(string $date): ?self
    {
        $date = Carbon::parse($date)->toDateString();

        return Cache::remember(
            self::holidayCacheKey($date),
            now()->addHour(),
            fn () => static::whereDate('date', $date)->where('type', CalendarEventType::Holiday)->first()
        );
    }

    public static function isHoliday(string $date): bool
    {
        return static::holidayOn($date) !== null;
    }

    private static function holidayCacheKey(string $date): string
    {
        return "calendar_holiday:{$date}";
    }

    /**
     * Dashboard widget feed - whatever's coming up regardless of which term
     * it belongs to, since a future term's events can already be entered in
     * advance of it becoming active.
     */
    public static function upcoming(int $limit = 5): Collection
    {
        return static::whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }

    protected static function booted(): void
    {
        static::saved(function (self $event): void {
            Cache::forget(self::holidayCacheKey($event->date->toDateString()));

            // The date itself is editable, so a changed date must also bust
            // whatever the *old* date's cached answer was - otherwise it
            // keeps reporting a holiday that moved elsewhere.
            if ($event->wasChanged('date') && $event->getOriginal('date')) {
                Cache::forget(self::holidayCacheKey(Carbon::parse($event->getOriginal('date'))->toDateString()));
            }
        });

        static::deleted(fn (self $event) => Cache::forget(self::holidayCacheKey($event->date->toDateString())));
    }
}
