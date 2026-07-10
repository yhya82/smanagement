<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Promotions</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.promotion-rules.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">Manage Rules</a>
            <button type="button" wire:click="$toggle('showEvaluateForm')"
                class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                {{ $showEvaluateForm ? 'Cancel' : 'Evaluate Class' }}
            </button>
            <button type="button" wire:click="$toggle('showManualForm')"
                class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                {{ $showManualForm ? 'Cancel' : 'Manual Promotion' }}
            </button>
        </div>
    </div>

    @if ($actionError)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $actionError }}</div>
    @endif

    @if ($showEvaluateForm)
        <form wire:submit="evaluateClass" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-900">Evaluate a class against promotion rules</h2>
            <p class="text-xs text-gray-500">
                Checks every active student in the class against the promotion rules for their grade level, using their
                ranking for the selected term. Matching students get a pending promotion - nothing moves until you approve it.
            </p>
            @if ($evaluateResult)
                <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ $evaluateResult }}</div>
            @endif
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Class</label>
                    <select wire:model="evaluate_class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('evaluate_class_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Term</label>
                    <select wire:model="evaluate_term_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                        @endforeach
                    </select>
                    @error('evaluate_term_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                Evaluate
            </button>
        </form>
    @endif

    @if ($showManualForm)
        <form wire:submit="createManual" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-900">Create a manual promotion</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Student</label>
                    <select wire:model="manual_student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->student_no }} - {{ $student->first_name }} {{ $student->last_name }}</option>
                        @endforeach
                    </select>
                    @error('manual_student_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Promote to class</label>
                    <select wire:model="manual_class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('manual_class_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Term</label>
                    <select wire:model="manual_term_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                        @endforeach
                    </select>
                    @error('manual_term_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Create
            </button>
        </form>
    @endif

    <div class="flex items-center justify-end mb-4">
        <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="">All</option>
        </select>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">From</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">To</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Term</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Source</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($promotions as $promotion)
                    <tr wire:key="promotion-{{ $promotion->id }}">
                        <td class="px-4 py-2 font-medium">{{ $promotion->student->first_name }} {{ $promotion->student->last_name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $promotion->fromClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $promotion->toClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $promotion->term->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $promotion->promotion_rule_id ? 'Rule' : 'Manual' }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-yellow-100 text-yellow-800' => $promotion->status->value === 'pending',
                                'bg-green-100 text-green-800' => $promotion->status->value === 'approved',
                                'bg-red-100 text-red-800' => $promotion->status->value === 'rejected',
                            ])>
                                {{ ucfirst($promotion->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            @if ($promotion->status->value === 'pending')
                                <button type="button" wire:click="approve({{ $promotion->id }})"
                                    wire:confirm="Approve this promotion? The student will move to the new class immediately."
                                    class="text-xs text-green-600 hover:text-green-500">Approve</button>
                                <span class="text-gray-300 mx-1">|</span>
                                <button type="button" wire:click="reject({{ $promotion->id }})"
                                    wire:confirm="Reject this promotion?"
                                    class="text-xs text-red-600 hover:text-red-500">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">No promotions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $promotions->links() }}
    </div>
</div>
