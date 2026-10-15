@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Record Building Expense</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('expenses.index', ['building_id' => request('building_id')]) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                Expense Details
            </div>
            <div class="card-body">
                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="building_id" class="form-label">Building <span class="text-danger">*</span></label>
                        <select name="building_id" id="building_id" class="form-select @error('building_id') is-invalid @enderror" required>
                            <option value="">Select a Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" {{ (old('building_id') ?? $selectedBuildingId) == $building->id ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">Select Category</option>
                            @php
                                $categories = [
                                    'Maintenance', 
                                    'Repairs', 
                                    'Cleaning', 
                                    'Security', 
                                    'Utilities', 
                                    'Taxes', 
                                    'Insurance', 
                                    'Management Fee', 
                                    'Legal/Professional', 
                                    'Supplies', 
                                    'Other'
                                ];
                            @endphp
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" id="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        @error('expense_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description / Notes</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Optional details about this expense...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Expense Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm bg-light h-100">
            <div class="card-body">
                <h5 class="card-title text-primary"><i class="bi bi-info-circle me-1"></i> Recording Expenses</h5>
                <p class="card-text text-muted">
                    Recording building-level expenses helps you track the operational costs of your properties. 
                    These records are essential for calculating net income and evaluating property performance.
                </p>
                <ul class="text-muted small">
                    <li><strong>Category:</strong> Classify expenses correctly for better reporting.</li>
                    <li><strong>Building:</strong> Assign expenses to the specific property that incurred them.</li>
                    <li><strong>Amount:</strong> Record the gross amount including taxes where applicable.</li>
                </ul>
                <div class="alert alert-warning small py-2 mt-3">
                    <i class="bi bi-exclamation-triangle me-1"></i> Once saved, these records will impact the overall property performance summary.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
