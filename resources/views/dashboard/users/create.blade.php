@extends('layouts.admin')

@section('title', 'Add User')
@section('page-title', 'Add User')
@section('page-description', 'Create a new user account and assign a role')

@section('content')
    <div class="max-w-2xl">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>User Details</x-ui.card-title>
                <x-ui.card-description>
                    The account is created immediately and can sign in with the password you set here.
                </x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form action="{{ route('dashboard.users.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <x-ui.label for="name">Full Name <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus />
                        @error('name')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="email">Email Address <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.input type="email" name="email" id="email" value="{{ old('email') }}" required />
                        @error('email')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="role">Role <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.select-native name="role" id="role" required>
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role') == $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </x-ui.select-native>
                        @error('role')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui.password-input name="password" label="Password" placeholder="Minimum 12 characters" required
                            autocomplete="new-password" />
                        <x-ui.password-input name="password_confirmation" label="Confirm Password"
                            placeholder="Re-enter password" required autocomplete="new-password" />
                    </div>
                    @error('password')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit">
                            <i class="fas fa-user-plus mr-2"></i> Create User
                        </x-ui.button>
                        <x-ui.button variant="outline" as="a" href="{{ route('dashboard.users.index') }}">
                            Cancel
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
