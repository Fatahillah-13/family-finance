<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Members - {{ $household->name }}
            </h2>
            <a class="text-sm underline" href="{{ route('households.index') }}">Households</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Tambah Member (A1: by email)</h3>

                    <form method="POST" action="{{ route('households.members.store') }}" class="space-y-3">
                        @csrf

                        <div>
                            <x-input-label for="email" value="Email user yang sudah terdaftar" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role_id" value="Role" />
                            <select id="role_id" name="role_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">Tambah</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Daftar Member</h3>

                    <div class="space-y-3">
                        @foreach ($memberships as $m)
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('households.members.role.update', $m) }}"
                                    class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" @selected($m->role_id === $role->id)>
                                                {{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-secondary-button type="submit">Set</x-secondary-button>
                                </form>

                                <form method="POST" action="{{ route('households.members.destroy', $m) }}"
                                    onsubmit="return confirm('Hapus member ini dari household?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button type="submit">Remove</x-danger-button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if ($memberships->isEmpty())
                        <div class="text-gray-600">Belum ada member.</div>
                    @endif

                    <x-input-error :messages="$errors->get('member')" class="mt-4" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
