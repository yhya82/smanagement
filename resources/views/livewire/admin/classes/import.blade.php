<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Import Students to {{ $class->name }}</h1>
        <div class="flex items-center gap-4">
            <a href="{{ 'data:text/csv;charset=utf-8,' . rawurlencode(implode(',', \App\Services\StudentImportService::EXPECTED_HEADER) . "\n" . 'Jane,Doe,2015-05-01,female,John Doe,Father,0551234567') }}"
                download="student-import-template.csv"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                Download CSV template
            </a>
            <a href="{{ route('admin.classes.add-student', $class) }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Add one student instead</a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200">Back to classes</a>
        </div>
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ $class->students()->count() }}{{ $class->capacity ? '/'.$class->capacity.' enrolled' : ' enrolled (no capacity limit)' }}
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Upload a CSV with the columns: <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ implode(', ', \App\Services\StudentImportService::EXPECTED_HEADER) }}</code>.
            Each row creates one student and one guardian, and enrolls them into {{ $class->name }}.
        </p>

        @if ($importError)
            <div class="rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">
                {{ $importError }}
            </div>
        @endif

        <div>
            <input type="file" wire:model="file" accept=".csv,text/csv" class="text-sm">
            @error('file') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="button" wire:click="import" wire:loading.attr="disabled"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Import
        </button>
    </div>

    @if ($queued)
        <div class="mt-6 rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-3 text-sm text-green-700 dark:text-green-400">
            Import queued - you'll get a notification with the result once it's done processing.
        </div>
    @endif
</div>
