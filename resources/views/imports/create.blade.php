<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Import CSV Transactions</h2>
            <a class="text-sm underline" href="{{ route('transactions.index') }}">Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">

                    <div class="text-sm text-gray-700">
                        CSV minimal harus punya kolom: <strong>tanggal</strong>, <strong>deskripsi</strong>,
                        <strong>amount</strong>.
                        Anda bisa mapping index kolom (0-based).
                    </div>

                    <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="account_id" value="Account tujuan (wajib)" />
                            <select id="account_id" name="account_id"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="csv" value="CSV File" />
                            <input id="csv" name="csv" type="file" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('csv')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <div>
                                <x-input-label for="col_date" value="col_date" />
                                <x-text-input id="col_date" name="col_date" type="number" min="0"
                                    class="mt-1 w-full" value="0" required />
                            </div>
                            <div>
                                <x-input-label for="col_description" value="col_description" />
                                <x-text-input id="col_description" name="col_description" type="number" min="0"
                                    class="mt-1 w-full" value="1" required />
                            </div>
                            <div>
                                <x-input-label for="col_amount" value="col_amount" />
                                <x-text-input id="col_amount" name="col_amount" type="number" min="0"
                                    class="mt-1 w-full" value="2" required />
                            </div>
                            <div>
                                <x-input-label for="col_type" value="col_type (opsional)" />
                                <x-text-input id="col_type" name="col_type" type="number" min="0"
                                    class="mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="date_format" value="date_format" />
                                <x-text-input id="date_format" name="date_format" type="text" class="mt-1 w-full"
                                    value="Y-m-d" required />
                                <div class="text-xs text-gray-600 mt-1">Contoh: Y-m-d atau d/m/Y</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="has_header" name="has_header" type="checkbox" class="rounded border-gray-300"
                                checked />
                            <label for="has_header" class="text-sm">CSV punya header (baris pertama)</label>
                        </div>

                        <x-primary-button type="submit">Upload & Preview</x-primary-button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
