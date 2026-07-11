<?php

namespace App\Models;

use App\Enums\CalendarEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * it falls in.
     */
    public static function holidayOn(string $date): ?self
    {
        return static::whereDate('date', $date)->where('type', CalendarEventType::Holiday)->first();
    }

    public static function isHoliday(string $date): bool
    {
        return static::holidayOn($date) !== null;
    }
}
