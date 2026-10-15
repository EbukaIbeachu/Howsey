@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" id="reportRoot">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Immutable Audit Trail</h1>
            <p class="text-muted small">Complete record of all accounting actions and security events.</p>
        </div>
        <div class="btn-toolbar">
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Accounting
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <!-- Mobile Portrait Hint -->
            <div class="d-md-none d-lg-none d-xl-none d-xxl-none bg-light p-2 text-center text-muted small border-bottom landscape-hide">
                <i class="bi bi-phone-landscape me-1"></i> Rotate device for full details
            </div>
            <style>
                @media (orientation: landscape) {
                    .landscape-hide { display: none !important; }
                    .portrait-row { display: none !important; }
                    .desktop-row { display: table-row !important; }
                    .desktop-header { display: table-cell !important; }
                }
            </style>
            <style>
                .report-protect-overlay{position:fixed;inset:0;background:rgba(0,0,0,.25);backdrop-filter:blur(4px);display:none;z-index:9999}
                .report-blurred #reportRoot{filter:blur(6px);pointer-events:none}
            </style>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th class="d-none d-md-table-cell desktop-header">Role</th>
                            <th class="d-none d-md-table-cell desktop-header">Action</th>
                            <th class="d-none d-md-table-cell desktop-header">Target Record</th>
                            <th class="d-none d-md-table-cell desktop-header">Device</th>
                            <th class="d-none d-md-table-cell desktop-header">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $rowClass = match($log->action_type) {
                                'create' => 'table-success',
                                'edit' => 'table-info',
                                'finalize', 'lock' => 'table-primary',
                                'unlock' => 'table-warning',
                                'request_edit' => 'table-secondary',
                                'approve_edit' => 'table-success',
                                'reject_edit' => 'table-danger',
                                default => ''
                            };
                            
                            $badgeClass = match($log->action_type) {
                                'create' => 'bg-success',
                                'edit' => 'bg-info text-dark',
                                'finalize', 'lock' => 'bg-primary',
                                'unlock' => 'bg-warning text-dark',
                                'request_edit' => 'bg-secondary',
                                'approve_edit' => 'bg-success',
                                'reject_edit' => 'bg-danger',
                                default => 'bg-dark'
                            };
                        @endphp
                        <!-- Portrait View (Color Coded, Hidden Cols) -->
                        <tr class="d-md-none portrait-row {{ $rowClass }}">
                            <td>{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                            <td>
                                <div class="fw-bold">{{ $log->user->name ?? 'System' }}</div>
                                <div class="text-muted small">{{ $log->user->email ?? '' }}</div>
                            </td>
                            <td class="d-none"></td>
                            <td class="d-none"></td>
                            <td class="d-none"></td>
                            <td class="d-none"></td>
                            <td class="d-none"></td>
                        </tr>
                        
                        <!-- Desktop/Landscape View (Full Detail, White Rows) -->
                        <tr class="d-none d-md-table-row desktop-row">
                            <td>{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                            <td>
                                <div class="fw-bold">{{ $log->user->name ?? 'System' }}</div>
                                <div class="text-muted small">{{ $log->user->email ?? '' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ strtoupper($log->role ?? 'N/A') }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ str_replace('_', ' ', strtoupper($log->action_type)) }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <strong>{{ $log->record_type }}</strong> ID: {{ $log->record_id }}<br>
                                    @if($log->building_id)
                                        <strong>Building ID:</strong> {{ $log->building_id }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span title="{{ $log->user_agent }}">
                                    <i class="bi {{ $log->device_type === 'mobile' ? 'bi-smartphone' : 'bi-pc-display' }} me-1"></i>
                                    {{ ucfirst($log->device_type) }}
                                </span>
                            </td>
                            <td><code>{{ $log->ip_address }}</code></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check fs-1 d-block mb-3"></i>
                                No audit logs found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded',function(){
                    const root=document.getElementById('reportRoot')||document.querySelector('.container-fluid');
                    const overlay=document.createElement('div');overlay.className='report-protect-overlay';document.body.appendChild(overlay);
                    function applyBlur(){document.body.classList.add('report-blurred');overlay.style.display='block'}
                    function removeBlur(){document.body.classList.remove('report-blurred');overlay.style.display='none'}
                    window.addEventListener('blur',applyBlur);
                    document.addEventListener('visibilitychange',function(){if(document.hidden){applyBlur()}else{removeBlur()}});
                    window.addEventListener('focus',removeBlur);
                    document.addEventListener('contextmenu',function(e){if(root&&root.contains(e.target)) e.preventDefault()});
                    document.addEventListener('keydown',function(e){
                        const k=e.key.toLowerCase();
                        if(k==='printscreen') e.preventDefault();
                        if(e.ctrlKey&&k==='p') e.preventDefault();
                        if(e.ctrlKey&&e.shiftKey&&(k==='s'||k==='i')) e.preventDefault();
                    });
                });
            </script>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
