<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Serves a private admission document (birth certificate, passport
     * photo) through an authenticated, permission-checked route - these
     * live on the `documents` disk specifically because they must never be
     * reachable via a guessable public URL (schema review §2.4).
     */
    public function stream(ApplicationDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk('documents')->response(
            $document->file_path,
            $document->original_filename
        );
    }
}
