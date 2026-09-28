@extends('layouts.admin')

@section('title', $role->name)
@section('page-title', 'Role Details')
@section('page-description', 'Users and permissions granted by this role')

@section('content')
    @php
        // Pre-compute granted permission IDs so membership checks stay O(1).
        $grantedIds = $role->permissions->pluck('id')->all();
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">{{ $role->name }}</h2>
                @if ($role->description)
                    <p class="text-sm text-muted-foreground mt-1">{{ $role->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <x-ui.button as="a" href="{{ route('dashboard.roles.edit', $role) }}">
                    <i class="fas fa-pen mr-2"></i> Edit Role
                </x-ui.button>
                <x-ui.button variant="outline" as="a" href="{{ route('dashboard.roles.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Roles
                </x-ui.button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Permissions -->
            <div class="lg:col-span-2">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>
                            Permissions
                            <x-ui.badge variant="secondary" class="ml-2">{{ $role->permissions->count() }}</x-ui.badge>
                        </x-ui.card-title>
                        <x-ui.card-description>
                            Permissions granted to any user holding this role.
                        </x-ui.card-description>
                    </x-ui.card-header>
                    <x-ui.card-content class="space-y-5">
                        @forelse ($allPermissions as $module => $permissions)
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
                                    {{ $module }}
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($permissions as $permission)
                                        @if (in_array($permission->id, $grantedIds, true))
                                            <x-ui.badge variant="outline"
                                                class="text-emerald-700 border-emerald-200 bg-emerald-50">
                                                <i class="fas fa-check mr-1"></i> {{ $permission->name }}
                                            </x-ui.badge>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No permissions defined.</p>
                        @endforelse

                        @if ($role->permissions->isEmpty())
                            <x-ui.alert class="bg-amber-50 text-amber-900 border-amber-200">
                                <i class="fas fa-triangle-exclamation mr-2"></i>
                                <x-ui.alert-description>
                                    This role has no permissions yet.
                                </x-ui.alert-description>
                            </x-ui.alert>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>
            </div>

            <!-- Users -->
            <div>
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>
                            Users
                            <x-ui.badge variant="secondary" class="ml-2">{{ $role->users->count() }}</x-ui.badge>
                        </x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content class="p-0">
                        <div class="divide-y divide-border max-h-96 overflow-y-auto">
                            @forelse ($role->users as $user)
                                <div class="flex items-center gap-3 p-3">
                                    <div
                                        class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-muted-foreground truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-sm text-muted-foreground">
                                    No users have this role.
                                </div>
                            @endforelse
                        </div>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
