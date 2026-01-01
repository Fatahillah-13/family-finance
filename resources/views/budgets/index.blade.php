<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Budgets — {{ $month }}
            </h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('reports.monthly', ['month' => $month]) }}">Reports</a>
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

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Tambah / Update Budget</h3>

                    <form method="POST" action="{{ route('budgets.store') }}"
                        class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}" />

                        <div>
                            <x-input-label for="category_id" value="Category (Expense)" />
                            <select id="category_id" name="category_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($expenseCategories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="amount" value="Budget (Rupiah)" />
                            <x-text-input id="amount" name="amount" type="number" min="1"
                                class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="md:col-span-3">
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Daftar Budget</h3>

                    <div class="space-y-3">
                        @foreach ($budgets as $b)
                            @php
                                $spent = (int) ($spentByCategory[$b->category_id] ?? 0);
                                $budget = (int) $b->amount;
                                $left = $budget - $spent;
                                $pct = $budget > 0 ? min(100, (int) round(($spent / $budget) * 100)) : 0;
                            @endphp

                            <div class="border rounded p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="font-medium">
                                            {{ $b->category->name }}
                                            @if (!$b->is_active)
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                            @endif
                                        </div>

                                        <div class="text-sm text-gray-700 mt-1">
                                            Budget: <strong>Rp {{ number_format($budget, 0, ',', '.') }}</strong>
                                            • Spent: <strong>Rp {{ number_format($spent, 0, ',', '.') }}</strong>
                                            • Left: <strong
                                                class="@if ($left < 0) text-red-700 @endif">Rp
                                                {{ number_format($left, 0, ',', '.') }}</strong>
                                        </div>

                                        <div class="mt-2 h-2 w-full bg-gray-100 rounded">
                                            <div class="h-2 rounded @if ($pct >= 100) bg-red-600 @else bg-green-600 @endif"
                                                style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">{{ $pct }}%</div>

                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-sm underline">Edit amount</summary>
                                            <form method="POST" action="{{ route('budgets.update', $b) }}"
                                                class="mt-2 flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-text-input name="amount" type="number" min="1"
                                                    value="{{ $budget }}" />
                                                <x-primary-button type="submit">Update</x-primary-button>
                                            </form>
                                        </details>
                                    </div>

                                    <form method="POST" action="{{ route('budgets.toggle', $b) }}">
                                        @csrf
                                        <x-secondary-button type="submit">
                                            {{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </x-secondary-button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($budgets->isEmpty())
                        <div class="text-gray-600">Belum ada budget untuk bulan ini.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
