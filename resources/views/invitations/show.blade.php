<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invitation
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    @if (session('status'))
                        <div class="text-sm text-gray-700">{{ session('status') }}</div>
                    @endif

                    <div>
                        <div class="text-gray-600 text-sm">Household</div>
                        <div class="font-semibold">{{ $invitation->household->name ?? '#' . $invitation->household_id }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-gray-600 text-sm">Invited email</div>
                            <div class="font-mono">{{ $invitation->email }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 text-sm">Role</div>
                            <div class="font-semibold">{{ $invitation->role->name ?? $invitation->role_id }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-600 text-sm">Status</div>
                        <div class="font-semibold">{{ $invitation->status }}</div>
                    </div>

                    @if ($invitation->status === 'pending')
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
                                @csrf
                                <x-primary-button type="submit">Accept</x-primary-button>
                            </form>

                            <form method="POST" action="{{ route('invitations.reject', $invitation->token) }}">
                                @csrf
                                <x-secondary-button type="submit">Reject</x-secondary-button>
                            </form>
                        </div>
                    @else
                        <a class="underline text-sm" href="{{ route('dashboard') }}">Back to dashboard</a>
                    @endif

                    <div class="text-xs text-gray-500">
                        Expires at: {{ optional($invitation->expires_at)->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
