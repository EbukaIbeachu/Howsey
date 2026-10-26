@extends('layouts.app')

@section('content')
@php
    $dashboardTitle = Auth::user()->isManager() ? 'Manager Dashboard' : 'Admin Dashboard';
@endphp

<style>
    /* Chart.js measures this frame (maintainAspectRatio: false) to size the
       canvas, so the height lives on the wrapper. Layout only — no colours and
       no !important, which is what the old panel overrides used. */
    .dashboard-chart-frame {
        position: relative;
        height: 320px;
    }

    @media (max-width: 767.98px) {
        .dashboard-chart-frame {
            height: 240px;
        }
    }
</style>

<x-ui.page-header :title="$dashboardTitle" :subtitle="Auth::user()->name" id="dashboard-title">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<!-- Stats Cards -->
@php
    // Exact net cash flow with 2 decimal places. The tile steps the number down
    // as the string grows so long amounts never overflow (.ui-stat--narrow /
    // --compact in components.css).
    $formattedRevenue = number_format($stats['net_cash_flow'] ?? 0, 2);
    $revLen = strlen($formattedRevenue);
    $revClass = '';
    if ($revLen > 11) $revClass = 'ui-stat--compact';
    elseif ($revLen > 9) $revClass = 'ui-stat--narrow';
@endphp

<div class="row g-2 mb-3 mb-md-4" id="stats-cards">
    <div class="col-md-3">
        <x-ui.stat-card label="Total Buildings" :value="$stats['total_buildings']" icon="bi bi-building" tone="primary" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Active Tenants" :value="$stats['total_tenants']" icon="bi bi-people-fill" tone="success" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Net Cash Flow" value="₦{{ $formattedRevenue }}" icon="bi bi-cash-stack" tone="info" :class="$revClass" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Overdue Rents" :value="$stats['overdue_count']" icon="bi bi-exclamation-triangle-fill" tone="warning" />
    </div>
</div>

<div class="row g-2 mb-3 mb-md-4" id="revenue-section">
    <!-- Revenue Chart -->
    <div class="col-md-12">
        <x-ui.card title="Revenue Overview" subtitle="Track income movement across the last 6 months." icon="bi bi-graph-up-arrow">
            <x-slot:actions>
                @if(Auth::user()->isAdmin())
                    <x-ui.button variant="outline-primary" size="sm" icon="bi bi-bar-chart-line-fill" :href="route('dashboard.analytics')">View Analytics</x-ui.button>
                @endif
            </x-slot:actions>

            <div class="dashboard-chart-frame">
                <canvas id="revenueChart"></canvas>
            </div>
        </x-ui.card>
    </div>
</div>

@php
    $overdueRentCount = $overdueRents->count();
    $overduePreviewItems = $overdueRents->take(3);
@endphp

<div class="row g-2 dashboard-activity-stack">
    <!-- Recent Payments -->
    <div class="col-12" id="recent-payments-section">
        <x-ui.card title="Recent Payments" :flush="true">
            <x-slot:footer>
                <x-ui.button variant="outline-primary" size="sm" class="ms-auto" :href="route('dashboard.recent-payments')">View All</x-ui.button>
            </x-slot:footer>

            <x-ui.table class="mb-0" caption="Recent payments" :caption-sr="true">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Tenant</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td>{{ $payment->rent->tenant->full_name }}</td>
                        <td class="text-success">₦{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->rent->unit)
                                {{ $payment->rent->unit->unit_number }}
                            @else
                                <span class="text-muted fst-italic">Unit Removed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-ui.table.empty-state :colspan="4" icon="bi bi-cash-stack" title="No recent payments found" />
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>

    <!-- Overdue Rents -->
    <div class="col-12" id="overdue-rents-section">
        <x-ui.card title="Overdue Rent">
            <x-slot:footer>
                <x-ui.button variant="outline-primary" size="sm" class="ms-auto" :href="route('dashboard.overdue-rents')">View All</x-ui.button>
            </x-slot:footer>

            @if($overdueRentCount > 0)
                <x-ui.alert tone="danger" icon="bi bi-exclamation-triangle-fill" class="mb-0" :title="$overdueRentCount.' overdue '.\Illuminate\Support\Str::plural('rent alert', $overdueRentCount)">
                    This alert stays visible until the outstanding payment is recorded and the rent is no longer overdue.
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($overduePreviewItems as $rent)
                            <x-ui.status-badge tone="danger" :label="$rent->tenant->full_name.' - '.$rent->unit->building->name.' / '.$rent->unit->unit_number" />
                        @endforeach
                        @if($overdueRentCount > $overduePreviewItems->count())
                            <x-ui.status-badge tone="neutral" :label="'+'.($overdueRentCount - $overduePreviewItems->count()).' more'" />
                        @endif
                    </div>
                </x-ui.alert>
            @else
                <x-ui.alert tone="success" icon="bi bi-check-circle-fill" class="mb-0" title="No overdue rent alerts">
                    All recorded rent payments are currently up to date.
                </x-ui.alert>
            @endif
        </x-ui.card>
    </div>
</div>

@if(Auth::user()->isAdmin() || Auth::user()->isManager())
<div class="row g-2 mt-3" id="manager-announcements-section">
    <div class="col-md-12">
        <x-ui.card :title="Auth::user()->isAdmin() ? 'All Active Announcements' : 'Announcements For Your Buildings'">
            <ul class="list-group">
                @forelse($dashboardAnnouncements as $ann)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $ann->title }}</strong>
                                <div class="text-muted small">
                                    {{ $ann->created_at->format('M d, Y H:i') }}
                                    — {{ $ann->manager->name }}
                                    — {{ $ann->building->name }}
                                </div>
                                <div>{{ $ann->content }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-2">
                                <a href="{{ route('buildings.show', $ann->building_id) }}#building-announcements-section" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-right-circle"></i> Open
                                </a>

                                @if(Auth::user()->isManager())
                                    <form action="{{ route('buildings.announcements.dismiss', ['building' => $ann->building_id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your view only?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No announcements yet.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset("assets/js/vendor/chart.umd.min.js") }}" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initRevenueChart() {
            if (window.howseyRevenueChartInitialized) {
                return true;
            }

            var canvas = document.getElementById('revenueChart');
            if (!canvas || typeof Chart === 'undefined') {
                return false;
            }

            window.howseyRevenueChartInitialized = true;

            var ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Revenue Collected (â‚¦)',
                        data: @json($chartData),
                        backgroundColor: 'rgba(124, 58, 237, 0.1)',
                        borderColor: '#7c3aed',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#7c3aed',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                family: "'Nunito', sans-serif"
                            },
                            bodyFont: {
                                size: 13,
                                family: "'Nunito', sans-serif"
                            },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [2, 4],
                                color: '#f0f0f0',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    family: "'Nunito', sans-serif"
                                },
                                callback: function(value, index, values) {
                                    if (value >= 1000000) return 'â‚¦' + (value/1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'â‚¦' + (value/1000).toFixed(0) + 'k';
                                    return 'â‚¦' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    family: "'Nunito', sans-serif"
                                }
                            }
                        }
                    }
                }
            });

            return true;
        }

        if (initRevenueChart()) {
            return;
        }

        var fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
        fallbackScript.onload = initRevenueChart;
        fallbackScript.async = true;
        document.head.appendChild(fallbackScript);
    });
</script>
@endpush
