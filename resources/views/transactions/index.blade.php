<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transactions</h2>
            <div class="text-sm text-gray-600" id="activeMonthLabel"></div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <!-- Tabs -->
            <div class="bg-white shadow-sm sm:rounded-lg p-3">
                <div class="flex gap-2">
                    <button class="tab-btn px-3 py-2 rounded border" data-type="expense">Expense</button>
                    <button class="tab-btn px-3 py-2 rounded border" data-type="income">Income</button>
                    <button class="tab-btn px-3 py-2 rounded border" data-type="transfer">Transfer</button>
                </div>

                <div class="mt-3 flex gap-2">
                    <input id="q" type="text" class="w-full border-gray-300 rounded"
                        placeholder="Search description..." />
                    <button id="searchBtn" class="px-3 py-2 border rounded">Search</button>
                </div>
            </div>

            <!-- List -->
            <div id="txList" class="space-y-2"></div>

            <!-- Load more -->
            <div class="flex justify-center">
                <button id="loadMoreBtn" class="px-4 py-2 border rounded bg-white shadow-sm hidden">
                    Load more
                </button>
            </div>

            <div id="loading" class="text-center text-sm text-gray-500 hidden">Loading...</div>
        </div>
    </div>

    <script>
        window.TRANSACTIONS_DATA_URL = @json(route('transactions.data'));
        window.CSRF_TOKEN = @json(csrf_token());
    </script>
    @vite(['resources/js/transactions.js'])
</x-app-layout>
