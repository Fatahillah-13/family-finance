<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Transaction ({{ $type }})
            </h2>
            <a class="text-sm underline" href="{{ route('transactions.index') }}">Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <form method="POST" action="{{ route('transactions.update', $transaction) }}"
                        enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="occurred_at" value="Tanggal & Jam" />
                            <x-text-input id="occurred_at" name="occurred_at" type="datetime-local"
                                class="mt-1 block w-full" value="{{ $transaction->occurred_at->format('Y-m-d\TH:i') }}"
                                required />
                            <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="amount" value="Amount (Rupiah)" />
                            <x-text-input id="amount" name="amount" type="number" min="1"
                                class="mt-1 block w-full" value="{{ $transaction->amount }}" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        @if ($type === 'transfer')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="from_account_id" value="From Account" />
                                    <select id="from_account_id" name="from_account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}" @selected($transaction->from_account_id === $a->id)>
                                                {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="to_account_id" value="To Account" />
                                    <select id="to_account_id" name="to_account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}" @selected($transaction->to_account_id === $a->id)>
                                                {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="account_id" value="Account" />
                                    <select id="account_id" name="account_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}" @selected($transaction->account_id === $a->id)>
                                                {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="category_id" value="Category" />
                                    <select id="category_id" name="category_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected($transaction->category_id === $c->id)>
                                                {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="description" value="Deskripsi (opsional)" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                rows="3">{{ $transaction->description }}</textarea>
                        </div>

                        <div>
                            <x-input-label value="Tags (opsional)" />
                            @php $selected = $transaction->tags->pluck('id')->all(); @endphp
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach ($tags as $tag)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                            class="rounded border-gray-300" @checked(in_array($tag->id, $selected, true)) />
                                        <span>{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <x-input-label for="attachments" value="Tambah Lampiran (opsional)" />
                            <input id="attachments" name="attachments[]" type="file" multiple
                                class="mt-1 block w-full" />
                            <div class="text-xs text-gray-600 mt-1">Max 5MB per file.</div>
                        </div>

                        <x-primary-button type="submit">Update</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-3">Lampiran</h3>

                    @if ($transaction->attachments->count())
                        <div class="space-y-2">
                            @foreach ($transaction->attachments as $att)
                                <div class="border rounded p-2 flex items-center justify-between">
                                    <div class="text-sm">
                                        {{ $att->original_name }}
                                        <span
                                            class="text-xs text-gray-600">({{ number_format(($att->size ?? 0) / 1024, 0) }}
                                            KB)</span>
                                    </div>

                                    <div class="flex gap-3">
                                        <a class="underline text-sm"
                                            href="{{ route('transactions.attachments.download', $att) }}">Download</a>

                                        <form method="POST"
                                            action="{{ route('transactions.attachments.delete', $att) }}"
                                            onsubmit="return confirm('Hapus lampiran ini?');">
                                            @csrf
                                            <button class="underline text-sm text-red-700">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-gray-600 text-sm">Belum ada lampiran.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
