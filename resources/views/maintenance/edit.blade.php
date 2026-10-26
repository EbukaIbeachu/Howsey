@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Request">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" icon="bi bi-arrow-left" :href="route('maintenance.show', $maintenance)">Cancel</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card>
            <form action="{{ route('maintenance.update', $maintenance) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.select name="status" label="Status" required :value="$maintenance->status" :options="[
                            'PENDING' => 'Pending',
                            'IN_PROGRESS' => 'In Progress',
                            'RESOLVED' => 'Resolved',
                            'CANCELLED' => 'Cancelled',
                        ]" />
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="priority" label="Priority" required :value="$maintenance->priority" :options="[
                            'LOW' => 'Low',
                            'MEDIUM' => 'Medium',
                            'HIGH' => 'High',
                            'EMERGENCY' => 'Emergency',
                        ]" />
                    </div>
                </div>

                <x-ui.form.select name="type" label="Issue Type" required :value="$maintenance->type" :options="[
                    'PLUMBING' => 'Plumbing',
                    'ELECTRICAL' => 'Electrical',
                    'HVAC' => 'HVAC',
                    'STRUCTURAL' => 'Structural',
                    'APPLIANCE' => 'Appliance',
                    'OTHER' => 'Other',
                ]" />

                <x-ui.form.input name="title" label="Title" required :value="$maintenance->title" />

                <x-ui.form.textarea name="description" label="Description" :rows="5" required :value="$maintenance->description" />

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <x-ui.button type="submit" variant="primary">Update Request</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
