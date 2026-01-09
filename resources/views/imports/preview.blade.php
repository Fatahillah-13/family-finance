<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Preview — {{ $import->original_filename }}
            </h2>
            <a class="text-sm underline" href="{{ route('imports.create') }}">New Import</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <x-input-error :messages="$errors->get('import')" class="mt-2" />

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm text-gray-700">
                        Account: <strong>{{ $import->account->name }}</strong><br />
                        Status: <strong>{{ $import->status }}</strong><br />
                        Total rows: <strong>{{ $counts['total'] }}</strong>,
                        New: <strong>{{ $counts['new'] }}</strong>,
                        Duplicates (skip): <strong>{{ $counts['duplicates'] }}</strong>
                    </div>

                    @if ($import->status === 'draft')
                        <form method="POST" action="{{ route('imports.commit', $import) }}" class="mt-4"
                            onsubmit="return confirm('Commit import? Transaksi baru akan dibuat.');">
                            @csrf
                            <x-primary-button type="submit">Commit Import</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-3">Preview (max tampil semua yang ter-parse)</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">Date</th>
                                    <th class="py-2 pr-4">Type</th>
                                    <th class="py-2 pr-4">Amount</th>
                                    <th class="py-2 pr-4">Description</th>
                                    <th class="py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preview as $r)
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">{{ $r['occurred_date'] }}</td>
                                        <td class="py-2 pr-4">{{ $r['type'] }}</td>
                                        <td class="py-2 pr-4">Rp {{ number_format($r['amount'], 0, ',', '.') }}</td>
                                        <td class="py-2 pr-4">{{ $r['description'] }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($r['is_duplicate'])
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">duplicate</span>
                                            @else
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-green-50 text-green-800">new</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
