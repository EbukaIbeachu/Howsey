@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data</h1>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(session('import_report'))
    @php $report = session('import_report'); @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Import results</h5>
        </div>
        <div class="card-body">
            @foreach($report as $section)
                <div class="mb-3">
                    <h6 class="mb-1">
                        {{ $section['type'] }}
                        <span class="badge bg-success">{{ $section['created'] }} created</span>
                        @if($section['skipped'] > 0)
                            <span class="badge bg-secondary">{{ $section['skipped'] }} skipped</span>
                        @endif
                        @if(count($section['errors']) > 0)
                            <span class="badge bg-danger">{{ count($section['errors']) }} failed</span>
                        @endif
                    </h6>
                    @if(!empty($section['skipped_notes']))
                        <ul class="small text-muted mb-1">
                            @foreach($section['skipped_notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if(count($section['errors']) > 0)
                        <ul class="small text-danger mb-0">
                            @foreach($section['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Export your data</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Download everything held in this workspace as a ZIP archive containing one CSV file per
                    table ({{ implode(', ', $exportTables) }}) plus a manifest describing the contents.
                </p>
                <form action="{{ route('data.export') }}" method="GET">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Download full export
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Import from CSV</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Upload one or more CSV files. Records are created in dependency order
                    ({{ implode(' → ', array_map(fn ($type) => $importTypes[$type]['label'], array_keys($importTypes))) }}).
                    Rows that already exist are skipped and listed in the report below. Existing data is never overwritten.
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Record type</th>
                                <th>Columns</th>
                                <th class="text-end">Template</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importTypes as $type => $definition)
                                <tr>
                                    <td class="fw-semibold">{{ $definition['label'] }}</td>
                                    <td class="small text-muted">{{ implode(', ', $definition['columns']) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('data.template', $type) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-file-earmark-arrow-down me-1"></i>CSV
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('data.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @foreach($importTypes as $type => $definition)
                        <div class="mb-3">
                            <label for="{{ $type }}" class="form-label">{{ $definition['label'] }} CSV</label>
                            <input type="file" class="form-control @error($type) is-invalid @enderror" id="{{ $type }}" name="{{ $type }}" accept=".csv,text/csv">
                            @error($type)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
