<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tags</h2>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="{{ route('accounts.index') }}">Accounts</a>
                <a class="underline" href="{{ route('categories.index', ['type' => 'expense']) }}">Categories</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Tambah Tag</h3>

                    <form method="POST" action="{{ route('tags.store') }}" class="flex gap-3">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="name" value="Nama" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="pt-6">
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Daftar Tags</h3>

                    <div class="space-y-3">
                        @foreach ($tags as $t)
                            <div class="border rounded p-3 flex items-center justify-between">
                                <div class="font-medium">
                                    {{ $t->name }}
                                    @if (!$t->is_active)
                                        <span
                                            class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('tags.toggle', $t) }}">
                                        @csrf
                                        <x-secondary-button type="submit">
                                            {{ $t->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </x-secondary-button>
                                    </form>

                                    <details>
                                        <summary class="cursor-pointer text-sm underline pt-2">Rename</summary>
                                        <form method="POST" action="{{ route('tags.update', $t) }}"
                                            class="mt-2 flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <x-text-input name="name" class="block w-48" value="{{ $t->name }}"
                                                required />
                                            <x-primary-button type="submit">OK</x-primary-button>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($tags->isEmpty())
                        <div class="text-gray-600">Belum ada tag.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
