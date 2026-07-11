<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            {{ $application->first_name }} {{ $application->last_name }}
        </h1>
        <span @class([
            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
            'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-400' => $application->status->value === 'pending',
            'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $application->status->value === 'approved',
            'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $application->status->value === 'rejected',
        ])>
            {{ ucfirst($application->status->value) }}
        </span>
    </div>

    @if ($application->status->value === 'rejected' && $application->rejection_reason)
        <div class="rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">
            Rejected: {{ $application->rejection_reason }}
        </div>
    @endif

    <section class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Applicant details</h2>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Date of birth</dt>
            <dd>{{ $application->dob->format('M j, Y') }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">Gender</dt>
            <dd>{{ ucfirst($application->gender->value) }}</dd>
        </dl>
    </section>

    <section class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Guardians</h2>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($application->guardians as $guardian)
                <div class="py-2 text-sm">
                    <span class="font-medium">{{ $guardian->name }}</span>
                    <span class="text-gray-500 dark:text-gray-400">({{ $guardian->relationship }})</span>
                    - {{ $guardian->phone }}
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Documents</h2>

        @if ($uploadError)
            <div class="rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">
                {{ $uploadError }}
            </div>
        @endif

        @foreach ($documentTypes as $documentType)
            @php
                $existing = $application->documents->firstWhere('document_type_id', $documentType->id);
            @endphp
            <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-4 first:border-t-0 first:pt-0">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $documentType->label }}
                        @if ($documentType->is_required)
                            <span class="text-red-500">*</span>
                        @endif
                    </p>
                    @if ($existing)
                        <a href="{{ route('application-documents.stream', $existing) }}" target="_blank"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                            {{ $existing->original_filename }}
                        </a>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">Not uploaded yet</p>
                    @endif
                </div>

                @if ($application->status->value === 'pending')
                    <div class="flex items-center gap-2">
                        <input type="file" wire:model="uploads.{{ $documentType->id }}" class="text-xs">
                        <button type="button" wire:click="uploadDocument({{ $documentType->id }})"
                            wire:loading.attr="disabled"
                            class="text-xs bg-gray-800 text-white px-3 py-1.5 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                            {{ $existing ? 'Replace' : 'Upload' }}
                        </button>
                    </div>
                @endif
            </div>
            @error("uploads.{$documentType->id}")
                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        @endforeach
    </section>
</div>
