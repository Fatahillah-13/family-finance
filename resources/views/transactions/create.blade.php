<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Transaction ({{ $type }})
            </h2>
            <a class="text-sm underline" href="{{ route('transactions.index') }}">Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">

                    <form method="POST" action="{{ route('transactions.store') }}" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}" />

                        <div>
                            <x-input-label for="occurred_at" value="Tanggal & Jam" />
                            <x-text-input id="occurred_at" name="occurred_at" type="datetime-local"
                                class="mt-1 block w-full" value="{{ now()->format('Y-m-d\TH:i') }}" required />
                            <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="amount" value="Amount (Rupiah)" />
                            <x-text-input id="amount" name="amount" type="number" min="1"
                                class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        @if ($type === 'transfer')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="from_account_id" value="From Account" />
                                    <select id="from_account_id" name="from_account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('from_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="to_account_id" value="To Account" />
                                    <select id="to_account_id" name="to_account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('to_account_id')" class="mt-2" />
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="account_id" value="Account" />
                                    <select id="account_id" name="account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('account_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="category_id" value="Category" />
                                    <select id="category_id" name="category_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                                </div>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="description" value="Deskripsi (opsional)" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                rows="3"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Tags (opsional)" />
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach ($tags as $tag)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                            class="rounded border-gray-300" />
                                        <span>{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('tags')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="attachments" value="Lampiran (opsional, bisa multiple)" />
                            <input id="attachments" name="attachments[]" type="file" multiple
                                class="mt-1 block w-full" />
                            <div class="text-xs text-gray-600 mt-1">Max 5MB per file.</div>
                            <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">Simpan</x-primary-button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
