@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Building Expenses</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('expenses.create', ['building_id' => request('building_id')]) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Record Expense
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('expenses.index') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="building_id" class="form-label">Filter by Building</label>
                <select name="building_id" id="building_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" {{ request('building_id') == $building->id ? 'selected' : '' }}>
                            {{ $building->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('building_id'))
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary w-100">Clear Filter</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Building</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th class="d-none d-md-table-cell">Description</th>
                        @if(auth()->user()->isAdmin())
                        <th class="d-none d-md-table-cell">Recorded By</th>
                        @endif
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('buildings.show', $expense->building) }}" class="text-decoration-none fw-bold">
                                {{ $expense->building->name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $expense->category }}</span>
                        </td>
                        <td class="fw-bold">₦{{ number_format($expense->amount, 2) }}</td>
                        <td class="d-none d-md-table-cell text-muted small">
                            {{ Str::limit($expense->description, 50) }}
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td class="d-none d-md-table-cell">
                            <small class="text-muted">{{ $expense->creator->name ?? 'System' }}</small>
                        </td>
                        @endif
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                            No expenses recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer bg-white">
        {{ $expenses->links() }}
    </div>
    @endif
</div>
@endsection
