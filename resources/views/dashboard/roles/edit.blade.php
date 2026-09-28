@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')
@section('page-description', 'Update this role and the permissions it grants')

@section('content')
    @php
        $grantedIds = old('permissions', $role->permissions->pluck('id')->all());
        $grantedIds = array_map('intval', (array) $grantedIds);
    @endphp

    <form action="{{ route('dashboard.roles.update', $role) }}" method="POST" class="space-y-6 max-w-4xl">
        @csrf
        @method('PUT')

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Role Details</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="space-y-5">
                <div class="space-y-2">
                    <x-ui.label for="name">Role Name <span class="text-destructive">*</span></x-ui.label>
                    <x-ui.input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" required
                        autofocus />
                    @error('name')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <x-ui.label for="description">Description</x-ui.label>
                    <x-ui.textarea name="description" id="description"
                        rows="2">{{ old('description', $role->description) }}</x-ui.textarea>
                    @error('description')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Permissions</x-ui.card-title>
                <x-ui.card-description>
                    Tick the permissions this role should grant. Changes apply to every user with this role.
                </x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content class="space-y-6">
                @foreach ($permissions as $module => $modulePermissions)
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">
                            {{ $module }}
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach ($modulePermissions as $permission)
                                <x-ui.checkbox name="permissions[]" value="{{ $permission->id }}"
                                    id="perm-{{ $permission->id }}" :checked="in_array($permission->id, $grantedIds, true)">
                                    {{ $permission->name }}
                                </x-ui.checkbox>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('permissions')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </x-ui.card-content>
            <x-ui.card-footer class="flex items-center gap-3">
                <x-ui.button type="submit">
                    <i class="fas fa-save mr-2"></i> Save Role
                </x-ui.button>
                <x-ui.button variant="outline" as="a" href="{{ route('dashboard.roles.index') }}">
                    Cancel
                </x-ui.button>
            </x-ui.card-footer>
        </x-ui.card>
    </form>
@endsection
