<?php

namespace Tests\Unit;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Auditable trait originally only logged updates - a created or deleted
 * record left no trail at all of who created/removed it, which is exactly
 * the kind of gap SRS §21's "log approvals, ... promotions, ..." intends to
 * close. StudentApplication is one of the trait's three consumers and has
 * the smallest fixture footprint, so it stands in for all three here.
 */
class AuditableTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_auditable_model_is_logged(): void
    {
        $submitter = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($submitter);

        $application = StudentApplication::create([
            'first_name' => 'Jane', 'last_name' => 'Doe', 'dob' => '2015-01-01',
            'gender' => Gender::Female, 'submitted_by' => $submitter->id,
        ]);

        $log = AuditLog::where('action', 'StudentApplication.created')
            ->where('auditable_id', $application->id)
            ->firstOrFail();

        $this->assertSame($submitter->id, $log->user_id);
        $this->assertSame('Jane', $log->new_values['first_name']);
    }

    public function test_deleting_an_auditable_model_is_logged(): void
    {
        $submitter = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($submitter);

        $application = StudentApplication::create([
            'first_name' => 'Jane', 'last_name' => 'Doe', 'dob' => '2015-01-01',
            'gender' => Gender::Female, 'submitted_by' => $submitter->id,
        ]);

        $application->delete();

        $log = AuditLog::where('action', 'StudentApplication.deleted')
            ->where('auditable_id', $application->id)
            ->firstOrFail();

        $this->assertSame('Jane', $log->old_values['first_name']);
    }
}
