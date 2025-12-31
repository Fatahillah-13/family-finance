<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transactions</h2>

            <div class="flex gap-2">
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'expense']) }}">+
                    Expense</a>
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'income']) }}">+ Income</a>
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'transfer']) }}">+
                    Transfer</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <x-input-label for="type" value="Filter Type" />
                            <select id="type" name="type" class="mt-1 border-gray-300 rounded-md shadow-sm">
                                <option value="">(All)</option>
                                <option value="expense" @selected(request('type') === 'expense')>expense</option>
                                <option value="income" @selected(request('type') === 'income')>income</option>
                                <option value="transfer" @selected(request('type') === 'transfer')>transfer</option>
                            </select>
                        </div>

                        <x-primary-button type="submit">Filter</x-primary-button>
                        <a class="text-sm underline" href="{{ route('transactions.index') }}">Reset</a>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach ($transactions as $t)
                            <div class="border rounded p-3 flex items-start justify-between">
                                <div>
                                    <div class="text-sm text-gray-600">{{ $t->occurred_at->format('Y-m-d H:i') }} •
                                        {{ $t->type }}</div>

                                    <div class="font-medium">
                                        Rp {{ number_format($t->amount, 0, ',', '.') }}
                                    </div>

                                    <div class="text-sm text-gray-700">
                                        @if ($t->type === 'transfer')
                                            {{ $t->fromAccount?->name }} → {{ $t->toAccount?->name }}
                                        @else
                                            {{ $t->account?->name }} • {{ $t->category?->name }}
                                        @endif
                                    </div>

                                    @if ($t->description)
                                        <div class="text-sm text-gray-600 mt-1">{{ $t->description }}</div>
                                    @endif

                                    @if ($t->tags->count())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($t->tags as $tag)
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <a class="underline text-sm" href="{{ route('transactions.edit', $t) }}">Edit</a>

                                    <form method="POST" action="{{ route('transactions.destroy', $t) }}"
                                        onsubmit="return confirm('Hapus transaksi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm underline text-red-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $transactions->links() }}
                    </div>

                    @if ($transactions->isEmpty())
                        <div class="text-gray-600">Belum ada transaksi.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
