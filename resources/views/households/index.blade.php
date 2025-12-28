<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Households
            </h2>
            <a class="text-sm underline" href="{{ route('dashboard') }}">Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Household Anda</h3>

                    <div class="space-y-3">
                        @foreach ($households as $h)
                            <div class="flex items-center justify-between border rounded p-3">
                                <div>
                                    <div class="font-medium">{{ $h->name }}</div>
                                    <div class="text-sm text-gray-600">Currency: {{ $h->currency }}</div>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if ($activeHouseholdId === $h->id)
                                        <span
                                            class="text-sm px-2 py-1 rounded bg-green-100 text-green-800">Active</span>
                                    @else
                                        <form method="POST" action="{{ route('households.switch', $h) }}">
                                            @csrf
                                            <x-primary-button type="submit">Switch</x-primary-button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($households->isEmpty())
                        <div class="text-gray-600">Belum ada household.</div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Buat Household Baru</h3>

                    <form method="POST" action="{{ route('households.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="name" value="Nama Household" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">Buat</x-primary-button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
