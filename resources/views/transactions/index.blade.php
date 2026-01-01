<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transactions</h2>

            <div class="flex gap-2">
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'expense']) }}">+
                    Expense</a>
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'income']) }}">+ Income</a>
                <a class="underline text-sm" href="{{ route('transactions.create', ['type' => 'transfer']) }}">+
                    Transfer</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                        <div class="md:col-span-1">
                            <x-input-label for="type" value="Type" />
                            <select id="type" name="type"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="" @selected(($f['type'] ?? '') === '')>(All)</option>
                                <option value="expense" @selected(($f['type'] ?? '') === 'expense')>expense</option>
                                <option value="income" @selected(($f['type'] ?? '') === 'income')>income</option>
                                <option value="transfer" @selected(($f['type'] ?? '') === 'transfer')>transfer</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="date_from" value="From" />
                            <x-text-input id="date_from" name="date_from" type="date" class="mt-1 w-full"
                                value="{{ $f['date_from'] ?? '' }}" />
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="date_to" value="To" />
                            <x-text-input id="date_to" name="date_to" type="date" class="mt-1 w-full"
                                value="{{ $f['date_to'] ?? '' }}" />
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="account_id" value="Account" />
                            <select id="account_id" name="account_id"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">(All)</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}" @selected(($f['account_id'] ?? null) == $a->id)>
                                        {{ $a->name }} @if (!$a->is_active)
                                            (inactive)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="category_id" value="Category" />
                            <select id="category_id" name="category_id"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">(All)</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" @selected(($f['category_id'] ?? null) == $c->id)>
                                        {{ $c->name }} ({{ $c->type }}) @if (!$c->is_active)
                                            (inactive)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="tag_id" value="Tag" />
                            <select id="tag_id" name="tag_id"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">(All)</option>
                                @foreach ($tags as $t)
                                    <option value="{{ $t->id }}" @selected(($f['tag_id'] ?? null) == $t->id)>
                                        {{ $t->name }} @if (!$t->is_active)
                                            (inactive)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-5">
                            <x-input-label for="q" value="Search (description/account/category)" />
                            <x-text-input id="q" name="q" type="text" class="mt-1 w-full"
                                placeholder="mis. 'internet' atau 'BCA' atau 'Makan'" value="{{ $f['q'] ?? '' }}" />
                        </div>

                        <div class="md:col-span-1 flex gap-2">
                            <x-primary-button type="submit">Apply</x-primary-button>
                            <a class="text-sm underline pt-2" href="{{ route('transactions.index') }}">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach ($transactions as $t)
                            <div class="border rounded p-3 flex items-start justify-between">
                                <div>
                                    <div class="text-sm text-gray-600">
                                        {{ $t->occurred_at->format('Y-m-d H:i') }} • {{ $t->type }}
                                    </div>

                                    <div class="font-medium">
                                        Rp {{ number_format($t->amount, 0, ',', '.') }}
                                    </div>

                                    <div class="text-sm text-gray-700">
                                        @if ($t->type === 'transfer')
                                            {{ $t->fromAccount?->name }} → {{ $t->toAccount?->name }}
                                        @else
                                            {{ $t->account?->name }} • {{ $t->category?->name }}
                                        @endif
                                    </div>

                                    @if ($t->description)
                                        <div class="text-sm text-gray-600 mt-1">{{ $t->description }}</div>
                                    @endif

                                    @if ($t->tags->count())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($t->tags as $tag)
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <a class="underline text-sm" href="{{ route('transactions.edit', $t) }}">Edit</a>

                                    <form method="POST" action="{{ route('transactions.destroy', $t) }}"
                                        onsubmit="return confirm('Hapus transaksi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm underline text-red-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $transactions->links() }}
                    </div>

                    @if ($transactions->isEmpty())
                        <div class="text-gray-600">Tidak ada transaksi sesuai filter.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
