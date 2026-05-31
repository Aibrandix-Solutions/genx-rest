<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Housekeeping</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="flex items-center gap-3 mb-4 sm:mb-0">
                    <div class="relative w-48">
                        <select wire:model.live="statusFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    <x-button type="button" wire:click="openTaskModal">Add Task</x-button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Room</th>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Type</th>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Priority</th>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Assigned</th>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($tasks as $task)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="p-4 text-sm font-semibold text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $task->room?->room_number ?? '-' }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                        {{ ucfirst(str_replace('_', ' ', $task->task_type)) }}
                                    </td>
                                    <td class="p-4 text-sm whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                        {{ $task->assignedTo?->name ?? 'Unassigned' }}
                                    </td>
                                    <td class="p-4 text-sm whitespace-nowrap">
                                        @php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-medium rounded {{ $statusClasses[$task->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td class="p-4 space-x-2 whitespace-nowrap">
                                        <button wire:click="editTask({{ $task->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-gray-700 hover:bg-gray-800">
                                            Edit
                                        </button>
                                        @if($task->status === 'pending')
                                            <button wire:click="startTask({{ $task->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-600 hover:bg-blue-700">
                                                Start
                                            </button>
                                        @endif
                                        @if($task->status !== 'completed')
                                            <button wire:click="completeTask({{ $task->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-green-600 hover:bg-green-700">
                                                Complete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-gray-500 dark:text-gray-400">No housekeeping tasks found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-right-modal wire:model.live="showTaskModal">
        <x-slot name="title">{{ $editingTaskId ? 'Edit Task' : 'New Task' }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveTask">
                <div class="space-y-4">
                    <div>
                        <x-label for="room_id" value="Room" />
                        <select id="room_id" wire:model="room_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="">Select room</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="room_id" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="task_type" value="Task Type" />
                        <select id="task_type" wire:model="task_type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="cleaning">Cleaning</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="inspection">Inspection</option>
                        </select>
                        <x-input-error for="task_type" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="priority" value="Priority" />
                        <select id="priority" wire:model="priority" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <x-input-error for="priority" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="assigned_to_user_id" value="Assign To" />
                        <select id="assigned_to_user_id" wire:model="assigned_to_user_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="assigned_to_user_id" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="notes" value="Notes" />
                        <textarea id="notes" wire:model="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm"></textarea>
                        <x-input-error for="notes" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <x-button type="submit">{{ $editingTaskId ? 'Update Task' : 'Create Task' }}</x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>
</div>
