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

                        {{-- tampilkan hanya untuk expense --}}
                        @if (request('type') === 'expense')
                            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">Scan Struk</div>
                                        <div class="text-sm text-gray-600">Upload foto struk untuk mengisi otomatis
                                            tanggal, total, dan deskripsi.</div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input id="receipt_image" type="file" accept="image/*" class="text-sm" />
                                        <button id="scan_receipt_btn" type="button"
                                            class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                            Scan
                                        </button>
                                    </div>
                                </div>

                                <div id="scan_receipt_status" class="mt-3 text-sm text-gray-600 hidden"></div>
                            </div>

                            <input type="hidden" name="attachment_id" id="attachment_id" value="" />
                        @endif

                        @push('scripts')
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const btn = document.getElementById('scan_receipt_btn');
                                    if (!btn) return;

                                    const input = document.getElementById('receipt_image');
                                    const statusEl = document.getElementById('scan_receipt_status');

                                    // Sesuaikan ID field form Anda:
                                    const occurredAtEl = document.querySelector('[name="occurred_at"]');
                                    const amountEl = document.querySelector('[name="amount"]');
                                    const descEl = document.querySelector('[name="description"]');
                                    const attachmentIdEl = document.getElementById('attachment_id');

                                    const setStatus = (text) => {
                                        statusEl.classList.remove('hidden');
                                        statusEl.textContent = text;
                                    };

                                    btn.addEventListener('click', async () => {
                                        if (!input.files || !input.files[0]) {
                                            setStatus('Pilih gambar struk dulu.');
                                            return;
                                        }

                                        btn.disabled = true;
                                        setStatus('Memproses struk...');

                                        const fd = new FormData();
                                        fd.append('receipt_image', input.files[0]);
                                        fd.append('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone);

                                        try {
                                            const res = await fetch('{{ route('transactions.scan-receipt') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                                        .getAttribute('content'),
                                                    'Accept': 'application/json',
                                                },
                                                body: fd,
                                            });

                                            const data = await res.json();
                                            if (!res.ok) {
                                                setStatus(data.message || 'Gagal scan struk.');
                                                btn.disabled = false;
                                                return;
                                            }

                                            if (attachmentIdEl) attachmentIdEl.value = data.attachment_id || '';

                                            if (data.prefill) {
                                                if (occurredAtEl && data.prefill.occurred_at) occurredAtEl.value = data.prefill
                                                    .occurred_at;
                                                if (amountEl && data.prefill.amount != null) amountEl.value = data.prefill
                                                    .amount;
                                                if (descEl && data.prefill.description) descEl.value = data.prefill.description;
                                            }

                                            setStatus('Berhasil. Silakan cek kembali data lalu simpan transaksi.');
                                        } catch (e) {
                                            setStatus('Terjadi error saat memproses. Coba lagi.');
                                        } finally {
                                            btn.disabled = false;
                                        }
                                    });
                                });
                            </script>
                        @endpush

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
