<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Household Members') }}
            </h2>
            <a class="text-sm underline" href="{{ route('households.index') }}">{{ __('Back to Households') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-red-700">
                        <div class="font-semibold mb-2">Errors</div>
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Invite via Email -->
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg mb-4">{{ __('Invite Member (Email)') }}</h3>

                    <form method="POST" action="{{ route('households.invitations.store') }}"
                        class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        @csrf

                        <div class="md:col-span-6">
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                value="{{ old('email') }}" required autocomplete="email" />
                        </div>

                        <div class="md:col-span-4">
                            <x-input-label for="role_id" value="Role" />
                            <select id="role_id" name="role_id"
                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="" disabled @selected(old('role_id') === null)>(Select role)</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2 flex gap-2">
                            <x-primary-button type="submit">{{ __('Send Invite') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Members -->
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg mb-4">{{ __('Current Members') }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 pr-4">User</th>
                                    <th class="py-2 pr-4">Email</th>
                                    <th class="py-2 pr-4">Role</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($memberships as $m)
                                    <tr class="border-b align-top">
                                        <td class="py-2 pr-4">
                                            {{ $m->user?->name ?? '—' }}
                                        </td>
                                        <td class="py-2 pr-4 font-mono">
                                            {{ $m->user?->email ?? '—' }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $m->role?->name ?? $m->role_id }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $m->status ?? '—' }}
                                        </td>
                                        <td class="py-2 pr-4 text-right">
                                            <!-- tetap boleh update role & remove jika Anda masih pakai fitur ini -->
                                            @if (isset($roles) && $roles->count() > 0)
                                                <form method="POST"
                                                    action="{{ route('households.members.role.update', $m->id) }}"
                                                    class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')

                                                    <select name="role_id"
                                                        class="border-gray-300 rounded-md shadow-sm text-sm">
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role->id }}"
                                                                @selected((string) $m->role_id === (string) $role->id)>{{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <x-secondary-button
                                                        type="submit">{{ __('Update Role') }}</x-secondary-button>
                                                </form>
                                            @endif

                                            <form method="POST"
                                                action="{{ route('households.members.destroy', $m->id) }}"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button type="submit"
                                                    onclick="return confirm('Remove this member?')">
                                                    {{ __('Remove') }}
                                                </x-danger-button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-gray-600">No members found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invitations (requires controller to pass $invitations) -->
            @isset($invitations)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg mb-4">{{ __('Invitations') }}</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left">
                                        <th class="py-2 pr-4">Email</th>
                                        <th class="py-2 pr-4">Role</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Sent</th>
                                        <th class="py-2 pr-4">Expires</th>
                                        <th class="py-2 pr-4">Responded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invitations as $inv)
                                        <tr class="border-b">
                                            <td class="py-2 pr-4 font-mono">{{ $inv->email }}</td>
                                            <td class="py-2 pr-4">{{ $inv->role?->name ?? $inv->role_id }}</td>
                                            <td class="py-2 pr-4">
                                                <span
                                                    class="px-2 py-0.5 rounded text-xs
                                                    {{ $inv->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $inv->status === 'accepted' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $inv->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $inv->status === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}
                                                ">
                                                    {{ $inv->status }}
                                                </span>
                                            </td>
                                            <td class="py-2 pr-4">{{ optional($inv->sent_at)->format('Y-m-d H:i') }}</td>
                                            <td class="py-2 pr-4">{{ optional($inv->expires_at)->format('Y-m-d H:i') }}
                                            </td>
                                            <td class="py-2 pr-4">{{ optional($inv->responded_at)->format('Y-m-d H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-4 text-gray-600">No invitations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endisset

        </div>
    </div>
</x-app-layout>
