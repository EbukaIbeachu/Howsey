@extends('layouts.app')

@section('content')
<x-ui.page-header
    title="Companies"
    subtitle="Manage white-label company workspaces, onboarding state, and account coverage."
>
    <x-slot:actions>
        @if(auth()->check() && auth()->user()->isSuperAdmin())
            <x-ui.button variant="outline-primary" icon="bi bi-credit-card-2-front" :href="route('company-applications.index')">Review Applications</x-ui.button>
        @endif
        @can('create', \App\Models\Company::class)
            <x-ui.button variant="primary" icon="bi bi-plus-lg" :href="route('companies.create')">Create Company</x-ui.button>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card flush>
    <x-ui.table
        class="mb-0"
        :headers="['Company', 'Status', 'Users', 'Buildings', 'Demo', ['label' => 'Actions', 'class' => 'text-end']]"
    >
        <tbody>
            @forelse($companies as $company)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $company->name }}</div>
                        <div class="small text-muted">{{ $company->slug }}</div>
                    </td>
                    <td>
                        @if($company->isPlatformInactive())
                            <x-ui.status-badge tone="danger" label="Platform Inactive" />
                        @elseif($company->isAdminInactive())
                            <x-ui.status-badge tone="neutral" label="Admin Inactive" />
                        @else
                            <x-ui.status-badge tone="success" label="Active" />
                        @endif
                    </td>
                    <td>{{ $company->users_count }}</td>
                    <td>{{ $company->buildings_count }}</td>
                    <td>{{ $company->is_demo ? 'Yes' : 'No' }}</td>
                    <td class="text-end">
                        <div class="ui-btn-group">
                            <x-ui.button variant="outline-primary" size="sm" :href="route('companies.edit', $company)">Open</x-ui.button>
                            <x-ui.button variant="outline-secondary" size="sm" :href="route('companies.login', $company->slug)">Branded Login</x-ui.button>
                            <form method="POST" action="{{ route('companies.destroy', $company) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="outline-danger" size="sm">Delete</x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="6" icon="bi bi-buildings" title="No companies found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($companies->hasPages())
        <x-slot:footer>{{ $companies->links() }}</x-slot:footer>
    @endif
</x-ui.card>
@endsection
