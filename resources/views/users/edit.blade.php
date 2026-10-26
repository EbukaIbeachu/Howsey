@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit User: {{ $user->name }}" />

<div class="row">
    <div class="col-md-8">
        <x-ui.card>
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <x-ui.form.input name="name" label="Full Name" :value="$user->name" required />

                <x-ui.form.input name="email" type="email" label="Account ID" :value="$user->email" required />

                <x-ui.form.input name="phone" label="Phone Number" :value="$user->phone" />

                <x-ui.form.select name="role_id" label="Role" required placeholder="Select Role">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </x-ui.form.select>

                <x-ui.form.checkbox name="is_active" label="Active User" :checked="$user->is_active" />

                <hr>
                <h5 class="mb-3">Change Password <small class="text-muted">(Leave blank to keep current)</small></h5>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="password" type="password" label="New Password" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="password_confirmation" type="password" label="Confirm New Password" />
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <x-ui.button type="submit" variant="primary">Update User</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
