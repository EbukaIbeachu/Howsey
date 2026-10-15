@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Companies</h1>
        <p class="text-muted mb-0">Manage white-label company workspaces, onboarding state, and account coverage.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(auth()->check() && auth()->user()->isSuperAdmin())
            <a href="{{ route('company-applications.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-credit-card-2-front me-1"></i> Review Applications
            </a>
        @endif
        @can('create', \App\Models\Company::class)
            <a href="{{ route('companies.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Company
            </a>
        @endcan
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Users</th>
                    <th>Buildings</th>
                    <th>Demo</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $company->name }}</div>
                            <div class="small text-muted">{{ $company->slug }}</div>
                        </td>
                        <td>
                            @if($company->isPlatformInactive())
                                <span class="badge bg-danger">Platform Inactive</span>
                            @elseif($company->isAdminInactive())
                                <span class="badge bg-secondary">Admin Inactive</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td>{{ $company->users_count }}</td>
                        <td>{{ $company->buildings_count }}</td>
                        <td>{{ $company->is_demo ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            <a href="{{ route('companies.login', $company->slug) }}" class="btn btn-sm btn-outline-secondary">Branded Login</a>
                            <form method="POST" action="{{ route('companies.destroy', $company) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No companies found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $companies->links() }}
</div>
@endsection
