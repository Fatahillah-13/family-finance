<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Logs</h2>
            <a class="text-sm underline" href="{{ route('dashboard') }}">Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                        <div class="md:col-span-2">
                            <x-input-label for="action" value="Action" />
                            <select id="action" name="action"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">(All)</option>
                                @foreach ($actions as $a)
                                    <option value="{{ $a }}" @selected(($f['action'] ?? '') === $a)>{{ $a }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <x-input-label for="q" value="Search (action/entity/meta)" />
                            <x-text-input id="q" name="q" class="mt-1 w-full"
                                value="{{ $f['q'] ?? '' }}" />
                        </div>

                        <div class="md:col-span-1 flex gap-2">
                            <x-primary-button type="submit">Apply</x-primary-button>
                            <a class="text-sm underline pt-2" href="{{ route('audit.index') }}">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 pr-4">Time</th>
                                    <th class="py-2 pr-4">Actor</th>
                                    <th class="py-2 pr-4">Action</th>
                                    <th class="py-2 pr-4">Entity</th>
                                    <th class="py-2 pr-4">Meta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr class="border-b align-top">
                                        <td class="py-2 pr-4 whitespace-nowrap">
                                            {{ $log->occurred_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="py-2 pr-4">
                                            {{ $log->actor?->name ?? ($log->actor?->email ?? 'System') }}
                                        </td>
                                        <td class="py-2 pr-4 font-mono">{{ $log->action }}</td>
                                        <td class="py-2 pr-4 font-mono">
                                            {{ $log->entity_type }}#{{ $log->entity_id }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            <pre class="text-xs whitespace-pre-wrap">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $logs->links() }}
                    </div>

                    @if ($logs->isEmpty())
                        <div class="text-gray-600">No logs.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
