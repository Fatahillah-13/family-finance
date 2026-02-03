<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transactions</h2>
            <div class="flex items-center gap-2">
                <button id="prevMonthBtn" class="px-2 py-1 border rounded bg-white hover:bg-gray-50" type="button"
                    aria-label="Previous month">
                    ‹
                </button>

                <div class="text-sm text-gray-600 w-[80px] text-center" id="activeMonthLabel"></div>

                <button id="nextMonthBtn" class="px-2 py-1 border rounded bg-white hover:bg-gray-50" type="button"
                    aria-label="Next month">
                    ›
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <!-- Tabs -->
            <div class="bg-white shadow-sm sm:rounded-lg p-3">
                <div class="flex gap-2" role="tablist" aria-label="Transaction types">
                    <button id="tab-expense" class="tab-btn px-3 py-2 rounded border transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" data-type="expense" role="tab" aria-selected="true" aria-controls="txList">Expense</button>
                    <button id="tab-income" class="tab-btn px-3 py-2 rounded border transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" data-type="income" role="tab" aria-selected="false" aria-controls="txList">Income</button>
                    <button id="tab-transfer" class="tab-btn px-3 py-2 rounded border transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" data-type="transfer" role="tab" aria-selected="false" aria-controls="txList">Transfer</button>
                </div>

                <div class="mt-3 flex gap-2">
                    <label for="q" class="sr-only">Search transactions</label>
                    <x-text-input id="q" type="text" class="w-full"
                        placeholder="Search description..." />
                    <button id="searchBtn" class="px-3 py-2 border rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">Search</button>
                </div>
            </div>

            <!-- List -->
            <div id="txList" class="space-y-2" role="tabpanel" aria-labelledby="tab-expense"></div>

            <!-- Load more -->
            <div class="flex justify-center">
                <button id="loadMoreBtn" class="px-4 py-2 border rounded bg-white shadow-sm hidden">
                    Load more
                </button>
            </div>

            <div id="loading" class="text-center text-sm text-gray-500 hidden">Loading...</div>
        </div>
    </div>

    {{-- Floating Action Button: Add Transaction (mobile friendly) --}}
    <a id="fabAddTx" href="{{ route('transactions.create', ['type' => 'expense']) }}"
        class="fixed bottom-6 right-6 z-50 inline-flex items-center justify-center w-14 h-14 rounded-full
          bg-indigo-600 text-white shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2
          focus:ring-indigo-500 focus:ring-offset-2 transition-opacity"
        aria-label="Add transaction">
        <span class="text-2xl leading-none">+</span>
    </a>

    <script>
        window.TRANSACTIONS_DATA_URL = @json(route('transactions.data'));
        window.CSRF_TOKEN = @json(csrf_token());
    </script>
    @vite(['resources/js/transactions.js'])
</x-app-layout>
