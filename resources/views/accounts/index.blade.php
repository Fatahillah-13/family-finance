<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Accounts</h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('categories.index', ['type' => 'expense']) }}">Categories</a>
                <a class="underline" href="{{ route('tags.index') }}">Tags</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Tambah Account</h3>
                    <form method="POST" action="{{ route('accounts.store') }}"
                        class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <div class="md:col-span-1">
                            <x-input-label for="name" value="Nama" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="type" value="Type" />
                            <select id="type" name="type"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($types as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="note" value="Catatan (opsional)" />
                            <x-text-input id="note" name="note" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>

                        <div class="md:col-span-3">
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Daftar Accounts</h3>

                    <div class="space-y-3">
                        @foreach ($accounts as $a)
                            <div class="border rounded p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">
                                            {{ $a->name }}
                                            @if (!$a->is_active)
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-600">Type: {{ $a->type }}</div>
                                        @if ($a->note)
                                            <div class="text-sm text-gray-600">Note: {{ $a->note }}</div>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('accounts.toggle', $a) }}">
                                        @csrf
                                        <x-secondary-button type="submit">
                                            {{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </x-secondary-button>
                                    </form>
                                </div>

                                <div class="mt-3">
                                    <details>
                                        <summary class="cursor-pointer text-sm underline">Edit</summary>
                                        <form method="POST" action="{{ route('accounts.update', $a) }}"
                                            class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label value="Nama" />
                                                <x-text-input name="name" class="mt-1 block w-full"
                                                    value="{{ $a->name }}" required />
                                            </div>

                                            <div>
                                                <x-input-label value="Type" />
                                                <select name="type"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                    @foreach ($types as $t)
                                                        <option value="{{ $t }}"
                                                            @selected($a->type === $t)>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label value="Catatan" />
                                                <x-text-input name="note" class="mt-1 block w-full"
                                                    value="{{ $a->note }}" />
                                            </div>

                                            <div class="md:col-span-3">
                                                <x-primary-button type="submit">Update</x-primary-button>
                                            </div>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($accounts->isEmpty())
                        <div class="text-gray-600">Belum ada account.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
