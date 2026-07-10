<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Import Students to {{ $class->name }}</h1>
        <div class="flex items-center gap-4">
            <a href="{{ 'data:text/csv;charset=utf-8,' . rawurlencode(implode(',', \App\Services\StudentImportService::EXPECTED_HEADER) . "\n" . 'Jane,Doe,2015-05-01,female,John Doe,Father,0551234567') }}"
                download="student-import-template.csv"
                class="text-sm text-indigo-600 hover:text-indigo-500">
                Download CSV template
            </a>
            <a href="{{ route('admin.classes.add-student', $class) }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">Add one student instead</a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Back to classes</a>
        </div>
    </div>

    <div class="mb-4 text-sm text-gray-600">
        {{ $class->students()->count() }}{{ $class->capacity ? '/'.$class->capacity.' enrolled' : ' enrolled (no capacity limit)' }}
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <p class="text-sm text-gray-600">
            Upload a CSV with the columns: <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">{{ implode(', ', \App\Services\StudentImportService::EXPECTED_HEADER) }}</code>.
            Each row creates one student and one guardian, and enrolls them into {{ $class->name }}.
        </p>

        @if ($importError)
            <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                {{ $importError }}
            </div>
        @endif

        <div>
            <input type="file" wire:model="file" accept=".csv,text/csv" class="text-sm">
            @error('file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="button" wire:click="import" wire:loading.attr="disabled"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Import
        </button>
    </div>

    @if ($createdCount !== null)
        <div class="mt-6 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">
            Created {{ $createdCount }} student(s).
        </div>
    @endif

    @if (count($importErrors) > 0)
        <div class="mt-4 bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-medium text-gray-900">Rows skipped ({{ count($importErrors) }})</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Row</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Errors</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($importErrors as $error)
                        <tr>
                            <td class="px-4 py-2 text-gray-500">{{ $error['row'] }}</td>
                            <td class="px-4 py-2 text-red-600">{{ implode('; ', $error['messages']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
