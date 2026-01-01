<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard — {{ $monthLabel }}
            </h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('transactions.create', ['type' => 'expense']) }}">+ Expense</a>
                <a class="underline" href="{{ route('transactions.create', ['type' => 'income']) }}">+ Income</a>
                <a class="underline" href="{{ route('transactions.create', ['type' => 'transfer']) }}">+ Transfer</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Income (bulan ini)</div>
                        <div class="text-2xl font-semibold">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Expense (bulan ini)</div>
                        <div class="text-2xl font-semibold">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Net (bulan ini)</div>
                        <div
                            class="text-2xl font-semibold @if ($monthlyNet < 0) text-red-700 @else text-green-700 @endif">
                            Rp {{ number_format($monthlyNet, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold">Saldo per Account</h3>
                            <a class="text-sm underline" href="{{ route('accounts.index') }}">Kelola</a>
                        </div>

                        <div class="space-y-2">
                            @foreach ($accountBalances as $a)
                                <div class="border rounded p-3 flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">
                                            {{ $a['name'] }}
                                            @if (!$a['is_active'])
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-600">{{ $a['type'] }}</div>
                                    </div>
                                    <div class="font-semibold">
                                        Rp {{ number_format($a['balance'], 0, ',', '.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if (collect($accountBalances)->isEmpty())
                            <div class="text-gray-600">Belum ada account.</div>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold">Top Expense Categories</h3>
                            <a class="text-sm underline"
                                href="{{ route('categories.index', ['type' => 'expense']) }}">Categories</a>
                        </div>

                        @if ($topExpenseCategories->count())
                            <div class="space-y-2">
                                @foreach ($topExpenseCategories as $row)
                                    <div class="border rounded p-3 flex items-center justify-between">
                                        <div class="font-medium">{{ $row->category_name }}</div>
                                        <div class="font-semibold">Rp
                                            {{ number_format((int) $row->total, 0, ',', '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-gray-600">Belum ada expense bulan ini.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold">Recent Transactions</h3>
                        <a class="text-sm underline" href="{{ route('transactions.index') }}">Lihat semua</a>
                    </div>

                    @if ($recentTransactions->count())
                        <div class="space-y-2">
                            @foreach ($recentTransactions as $t)
                                <div class="border rounded p-3 flex items-start justify-between">
                                    <div>
                                        <div class="text-sm text-gray-600">{{ $t->occurred_at->format('Y-m-d H:i') }} •
                                            {{ $t->type }}</div>
                                        <div class="font-medium">Rp {{ number_format($t->amount, 0, ',', '.') }}</div>
                                        <div class="text-sm text-gray-700">
                                            @if ($t->type === 'transfer')
                                                {{ $t->fromAccount?->name }} → {{ $t->toAccount?->name }}
                                            @else
                                                {{ $t->account?->name }} • {{ $t->category?->name }}
                                            @endif
                                        </div>
                                    </div>

                                    <a class="underline text-sm" href="{{ route('transactions.edit', $t) }}">Edit</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-gray-600">Belum ada transaksi.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
