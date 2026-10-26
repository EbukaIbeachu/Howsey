@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" id="reportRoot">
    <x-ui.page-header title="Immutable Audit Trail" subtitle="Complete record of all accounting actions and security events.">
        <x-slot:actions>
            <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('accounting.index')">Back to Accounting</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
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
        <x-ui.table>
            <thead class="bg-light">
                <tr>
                    <th scope="col">Timestamp</th>
                    <th scope="col">User</th>
                    <th scope="col" class="d-none d-md-table-cell desktop-header">Role</th>
                    <th scope="col" class="d-none d-md-table-cell desktop-header">Action</th>
                    <th scope="col" class="d-none d-md-table-cell desktop-header">Target Record</th>
                    <th scope="col" class="d-none d-md-table-cell desktop-header">Device</th>
                    <th scope="col" class="d-none d-md-table-cell desktop-header">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $rowTone = match($log->action_type) {
                        'create' => 'ui-row--success',
                        'edit' => 'ui-row--info',
                        'finalize', 'lock' => 'ui-row--primary',
                        'unlock' => 'ui-row--warning',
                        'request_edit' => 'ui-row--neutral',
                        'approve_edit' => 'ui-row--success',
                        'reject_edit' => 'ui-row--danger',
                        default => ''
                    };

                    $badgeTone = match($log->action_type) {
                        'create' => 'success',
                        'edit' => 'info',
                        'finalize', 'lock' => 'primary',
                        'unlock' => 'warning',
                        'request_edit' => 'neutral',
                        'approve_edit' => 'success',
                        'reject_edit' => 'danger',
                        default => 'neutral'
                    };
                @endphp
                <!-- Portrait View (Color Coded, Hidden Cols) -->
                <tr class="d-md-none portrait-row {{ $rowTone }}">
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
                        <x-ui.status-badge tone="neutral" :label="strtoupper($log->role ?? 'N/A')" />
                    </td>
                    <td>
                        <x-ui.status-badge :tone="$badgeTone" :label="str_replace('_', ' ', strtoupper($log->action_type))" />
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
                <x-ui.table.empty-state
                    :colspan="7"
                    icon="bi bi-shield-check"
                    title="No audit logs found."
                />
                @endforelse
            </tbody>
        </x-ui.table>
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
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    </x-ui.card>
</div>
@endsection
