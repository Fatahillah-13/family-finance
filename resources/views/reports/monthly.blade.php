<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Monthly Report — {{ $month }}
            </h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('budgets.index', ['month' => $month]) }}">Budgets</a>
                <a class="underline" href="{{ route('transactions.index') }}">Transactions</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <x-input-label for="month" value="Month (YYYY-MM)" />
                            <x-text-input id="month" name="month" class="mt-1 block"
                                value="{{ $month }}" />
                        </div>
                        <x-primary-button type="submit">Go</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Income</div>
                        <div class="text-2xl font-semibold">Rp {{ number_format($income, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Expense</div>
                        <div class="text-2xl font-semibold">Rp {{ number_format($expense, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600">Net</div>
                        <div
                            class="text-2xl font-semibold @if ($net < 0) text-red-700 @else text-green-700 @endif">
                            Rp {{ number_format($net, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold mb-4">Expense by Category</h3>
                        @if ($expenseByCategory->count())
                            <div class="space-y-2">
                                @foreach ($expenseByCategory as $row)
                                    <div class="border rounded p-3 flex items-center justify-between">
                                        <div class="font-medium">{{ $row->category_name }}</div>
                                        <div class="font-semibold">Rp
                                            {{ number_format((int) $row->total, 0, ',', '.') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-gray-600">Belum ada expense.</div>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold mb-4">Income by Category</h3>
                        @if ($incomeByCategory->count())
                            <div class="space-y-2">
                                @foreach ($incomeByCategory as $row)
                                    <div class="border rounded p-3 flex items-center justify-between">
                                        <div class="font-medium">{{ $row->category_name }}</div>
                                        <div class="font-semibold">Rp
                                            {{ number_format((int) $row->total, 0, ',', '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-gray-600">Belum ada income.</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
