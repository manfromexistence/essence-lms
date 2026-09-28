@extends('layouts.admin')

@section('title', $user->name)
@section('page-title', 'User Details')
@section('page-description', 'Roles and account status for this user')

@section('content')
    <div class="max-w-2xl space-y-6">
        <x-ui.card>
            <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div
                        class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <x-ui.card-title>{{ $user->name }}</x-ui.card-title>
                        <x-ui.card-description>{{ $user->email }}</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>

            <x-ui.card-content>
                <dl class="divide-y divide-gray-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Roles</dt>
                        <dd class="flex flex-wrap gap-1.5">
                            @forelse ($user->roles as $role)
                                <x-ui.badge
                                    variant="{{ $role->slug === 'super-admin' ? 'destructive' : ($role->slug === 'teacher' ? 'default' : 'secondary') }}">
                                    {{ $role->name }}
                                </x-ui.badge>
                            @empty
                                <span class="text-sm text-muted-foreground">No role assigned</span>
                            @endforelse
                        </dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Status</dt>
                        <dd>
                            @if ($user->email_verified_at)
                                <x-ui.badge variant="outline" class="text-emerald-600 border-emerald-200">Verified</x-ui.badge>
                            @else
                                <x-ui.badge variant="outline" class="text-amber-600 border-amber-200">Pending</x-ui.badge>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Joined</dt>
                        <dd class="text-sm">{{ $user->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </x-ui.card-content>

            <x-ui.card-footer class="flex items-center gap-3">
                <x-ui.button as="a" href="{{ route('dashboard.users.edit', $user) }}">
                    <i class="fas fa-pen mr-2"></i> Edit User
                </x-ui.button>
                <x-ui.button variant="outline" as="a" href="{{ route('dashboard.users.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Users
                </x-ui.button>
            </x-ui.card-footer>
        </x-ui.card>
    </div>
@endsection
