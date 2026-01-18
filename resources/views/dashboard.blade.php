<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard — {{ $monthLabel }}
            </h2>
            <div class="hidden sm:flex gap-3 text-sm">
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @keydown.escape.window="open = false"
                        class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/20"
                        aria-haspopup="menu" :aria-expanded="open.toString()">
                        <span>+ Add</span>
                        <svg class="h-4 w-4 opacity-80" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
                        role="menu" aria-label="Add transaction">
                        <a class="block px-4 py-2 text-sm hover:bg-gray-50" role="menuitem"
                            href="{{ route('transactions.create', ['type' => 'expense']) }}">
                            + Expense
                        </a>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-50" role="menuitem"
                            href="{{ route('transactions.create', ['type' => 'income']) }}">
                            + Income
                        </a>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-50" role="menuitem"
                            href="{{ route('transactions.create', ['type' => 'transfer']) }}">
                            + Transfer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="fixed bottom-5 right-5 z-50 sm:hidden" x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="h-14 w-14 rounded-full bg-gray-900 text-white shadow-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/20"
            aria-label="Add transaction">
            <span class="text-2xl leading-none">+</span>
        </button>

        <div x-cloak x-show="open" x-transition @click.outside="open = false"
            class="absolute bottom-16 right-0 w-52 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
            <a class="block px-4 py-3 text-sm hover:bg-gray-50"
                href="{{ route('transactions.create', ['type' => 'expense']) }}">
                + Expense
            </a>
            <a class="block px-4 py-3 text-sm hover:bg-gray-50"
                href="{{ route('transactions.create', ['type' => 'income']) }}">
                + Income
            </a>
            <a class="block px-4 py-3 text-sm hover:bg-gray-50"
                href="{{ route('transactions.create', ['type' => 'transfer']) }}">
                + Transfer
            </a>
        </div>
    </div>

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
