<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Categories ({{ $type }})
            </h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('categories.index', ['type' => 'expense']) }}">Expense</a>
                <a class="underline" href="{{ route('categories.index', ['type' => 'income']) }}">Income</a>
                <a class="underline" href="{{ route('accounts.index') }}">Accounts</a>
                <a class="underline" href="{{ route('tags.index') }}">Tags</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Tambah Category</h3>

                    <form method="POST" action="{{ route('categories.store') }}"
                        class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}" />

                        <div>
                            <x-input-label for="name" value="Nama" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="parent_id" value="Parent (opsional)" />
                            <select id="parent_id" name="parent_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">(Tidak ada / Parent)</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div class="md:col-span-3">
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Daftar Categories</h3>

                    <div class="space-y-4">
                        @foreach ($categories as $c)
                            <div class="border rounded p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">
                                        {{ $c->name }}
                                        @if (!$c->is_active)
                                            <span
                                                class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                        @endif
                                    </div>

                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('categories.toggle', $c) }}">
                                            @csrf
                                            <x-secondary-button type="submit">
                                                {{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </x-secondary-button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <details>
                                        <summary class="cursor-pointer text-sm underline">Rename</summary>
                                        <form method="POST" action="{{ route('categories.update', $c) }}"
                                            class="mt-3 flex gap-3">
                                            @csrf
                                            @method('PATCH')
                                            <x-text-input name="name" class="block w-full"
                                                value="{{ $c->name }}" required />
                                            <x-primary-button type="submit">Update</x-primary-button>
                                        </form>
                                    </details>
                                </div>

                                @if ($c->children->count())
                                    <div class="mt-4 pl-4 border-l">
                                        <div class="text-sm text-gray-600 mb-2">Sub-categories:</div>
                                        <div class="space-y-2">
                                            @foreach ($c->children as $ch)
                                                <div class="flex items-center justify-between border rounded p-2">
                                                    <div>
                                                        <span class="font-medium">{{ $ch->name }}</span>
                                                        @if (!$ch->is_active)
                                                            <span
                                                                class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                                        @endif
                                                    </div>

                                                    <div class="flex gap-2">
                                                        <form method="POST"
                                                            action="{{ route('categories.toggle', $ch) }}">
                                                            @csrf
                                                            <x-secondary-button type="submit">
                                                                {{ $ch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </x-secondary-button>
                                                        </form>

                                                        <form method="POST"
                                                            action="{{ route('categories.update', $ch) }}"
                                                            class="flex gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <x-text-input name="name" class="block w-48"
                                                                value="{{ $ch->name }}" required />
                                                            <x-primary-button type="submit">Rename</x-primary-button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($categories->isEmpty())
                        <div class="text-gray-600">Belum ada category.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
