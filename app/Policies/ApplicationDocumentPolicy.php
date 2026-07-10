<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\ApplicationDocument;
use App\Models\User;

class ApplicationDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('applications.create') || $user->hasPermission('applications.approve');
    }

    public function view(User $user, ApplicationDocument $document): bool
    {
        return $user->hasPermission('applications.create') || $user->hasPermission('applications.approve');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('applications.create');
    }

    /**
     * A re-upload updates the existing row in place, but only while the
     * application is still pending - once approved/rejected, the submitted
     * document is a permanent admission record (schema review §2.4).
     */
    public function update(User $user, ApplicationDocument $document): bool
    {
        return $user->hasPermission('applications.create')
            && $document->studentApplication->status === ApprovalStatus::Pending;
    }

    /**
     * Documents are replaced (via update), never deleted - see the
     * immutability rule in the schema review.
     */
    public function delete(User $user, ApplicationDocument $document): bool
    {
        return false;
    }
}
