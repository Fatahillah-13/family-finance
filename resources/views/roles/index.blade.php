<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Roles & Permissions
            </h2>
            <a class="text-sm underline" href="{{ route('households.members') }}">Members</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Buat Role Baru</h3>

                    <form method="POST" action="{{ route('roles.store') }}" class="flex gap-3">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="name" value="Role name" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="pt-6">
                            <x-primary-button type="submit">Create</x-primary-button>
                        </div>
                    </form>

                    <x-input-error :messages="$errors->get('role')" class="mt-4" />
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Roles</h3>

                    <div class="space-y-4">
                        @foreach ($roles as $role)
                            <div class="border rounded p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="font-medium">
                                            {{ $role->name }}
                                            @if (!$role->is_active)
                                                <span
                                                    class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">inactive</span>
                                            @endif
                                        </div>

                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-sm underline">Rename</summary>
                                            <form method="POST" action="{{ route('roles.update', $role) }}"
                                                class="mt-2 flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-text-input name="name" class="block w-64"
                                                    value="{{ $role->name }}" required />
                                                <x-primary-button type="submit">Update</x-primary-button>
                                            </form>
                                        </details>

                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-sm underline">Permissions</summary>

                                            <form method="POST" action="{{ route('roles.permissions.sync', $role) }}"
                                                class="mt-3 space-y-3">
                                                @csrf

                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                                    @php $assigned = $role->permissions->pluck('id')->all(); @endphp
                                                    @foreach ($permissions as $p)
                                                        <label class="flex items-center gap-2 text-sm">
                                                            <input type="checkbox" name="permissions[]"
                                                                value="{{ $p->id }}"
                                                                class="rounded border-gray-300"
                                                                @checked(in_array($p->id, $assigned, true)) />
                                                            <span class="font-mono">{{ $p->key }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>

                                                <x-primary-button type="submit">Save permissions</x-primary-button>

                                                @if ($role->name === 'Owner')
                                                    <div class="text-xs text-gray-600">
                                                        Catatan: role Owner akan selalu di-sync ke semua permission.
                                                    </div>
                                                @endif
                                            </form>
                                        </details>
                                    </div>

                                    <form method="POST" action="{{ route('roles.toggle', $role) }}">
                                        @csrf
                                        <x-secondary-button type="submit">
                                            {{ $role->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </x-secondary-button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($roles->isEmpty())
                        <div class="text-gray-600">Belum ada role.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
