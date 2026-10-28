@extends('layouts.app')

@section('content')
@php
    $dashboardTitle = Auth::user()->isManager() ? 'Manager Dashboard' : 'Admin Dashboard';
@endphp

<style>
    .revenue-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid rgba(var(--brand-primary-rgb), 0.12);
        background:
            linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.14), rgba(255, 255, 255, 0.96));
    }

    .revenue-panel-title-block {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .revenue-panel-icon {
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--brand-primary-rgb), 0.14);
        color: var(--primary-color);
        box-shadow: inset 0 0 0 1px rgba(var(--brand-primary-rgb), 0.16);
        flex-shrink: 0;
    }

    .revenue-panel-icon i {
        font-size: 1.2rem;
    }

    .revenue-panel-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #1f2937;
    }

    .revenue-panel-subtitle {
        margin: 0.2rem 0 0;
        color: #6b7280;
        font-size: 0.88rem;
        font-weight: 500;
    }

    .revenue-overview-link {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.85rem;
        border-radius: 999px;
        border: 1px solid rgba(var(--brand-primary-rgb), 0.18);
        background: rgba(var(--brand-primary-rgb), 0.08);
        color: var(--primary-color) !important;
        box-shadow: 0 8px 20px rgba(var(--brand-primary-rgb), 0.12);
        cursor: pointer;
        transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .revenue-overview-link .revenue-overview-meta {
        color: #4b5563;
        font-weight: 600;
    }

    .revenue-overview-link:hover,
    .revenue-overview-link:focus-visible {
        background: rgba(var(--brand-primary-rgb), 0.14);
        box-shadow: 0 10px 22px rgba(var(--brand-primary-rgb), 0.18);
        transform: translateY(-1px);
    }

    .revenue-chart-wrap {
        position: relative;
        min-height: 320px;
    }

    .revenue-chart-wrap canvas {
        width: 100% !important;
        height: 320px !important;
    }

    @media (max-width: 767.98px) {
        .dashboard-mobile-header {
            padding-top: 0.35rem;
            padding-bottom: 0.35rem;
            margin-bottom: 0.75rem !important;
            gap: 0.5rem;
        }

        .dashboard-mobile-title {
            display: block !important;
            font-size: 1rem;
            line-height: 1.2;
            margin-bottom: 0;
            flex: 1 1 auto;
        }

        .dashboard-help-btn {
            margin-left: auto;
            flex-shrink: 0;
            padding: 0.25rem 0.55rem;
        }

        #stats-cards {
            margin-top: 0 !important;
        }

        #stats-cards .card {
            margin-top: 0 !important;
        }

        .revenue-panel-header {
            flex-direction: column;
            align-items: stretch;
        }

        .revenue-panel-title-block {
            align-items: flex-start;
        }

        .revenue-overview-link {
            justify-content: center;
            width: 100%;
            line-height: 1.35;
        }

        .revenue-chart-wrap {
            min-height: 240px;
        }

        .revenue-chart-wrap canvas {
            height: 240px !important;
        }
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-1 mb-2 mb-md-3 border-bottom dashboard-mobile-header">
    <div>
        <h1 class="h2 d-none d-md-block mb-0" id="dashboard-title">{{ $dashboardTitle }}</h1>
        <h1 class="h5 d-md-none dashboard-mobile-title">{{ $dashboardTitle }}</h1>
        <div class="text-muted small fw-semibold">{{ Auth::user()->name }}</div>
    </div>
    <div class="ms-auto">
        <button class="btn btn-sm btn-outline-info dashboard-help-btn" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-3 mb-md-4" id="stats-cards">
    <div class="col-md-3">
        <div class="card text-white bg-gradient-primary mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Total Buildings</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['total_buildings'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-success mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Active Tenants</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['total_tenants'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-info mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Net Cash Flow</h6>
                        @php
                            // Format exact net cash flow with 2 decimal places
                            $formattedRevenue = number_format($stats['net_cash_flow'] ?? 0, 2);
                            $revLen = strlen($formattedRevenue);
                            // Base size display-6, reduces as length increases
                            $revClass = 'display-6';
                            if ($revLen > 11) $revClass = 'fs-3'; // Adjust size for longer exact amounts
                            elseif ($revLen > 9) $revClass = 'fs-2';
                        @endphp
                        <p class="card-text {{ $revClass }} fw-bold mb-0">₦{{ $formattedRevenue }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-cash-stack fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-warning mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Overdue Rents</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['overdue_count'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 mb-3 mb-md-4" id="revenue-section">
    <!-- Revenue Chart -->
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header revenue-panel-header">
                <div class="revenue-panel-title-block">
                    <span class="revenue-panel-icon" aria-hidden="true">
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
                    <div>
                        <h5 class="revenue-panel-title">Revenue Overview</h5>
                        <p class="revenue-panel-subtitle">Track income movement across the last 6 months.</p>
                    </div>
                </div>
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('dashboard.analytics') }}" class="text-decoration-none revenue-overview-link">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>View Analytics</span>
                        <span class="revenue-overview-meta">Last 6 Months</span>
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="revenue-chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $overdueRentCount = $overdueRents->count();
    $overduePreviewItems = $overdueRents->take(3);
@endphp

<div class="row g-2 dashboard-activity-stack">
    <!-- Recent Payments -->
    <div class="col-12" id="recent-payments-section">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <a href="{{ route('dashboard.recent-payments') }}" class="text-decoration-none text-reset">Recent Payments</a>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 overdue-rents-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tenant</th>
                                <th>Amount</th>
                                <th>Unit</th>
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
                            <tr>
                                <td colspan="4" class="text-center py-3">No recent payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('dashboard.recent-payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>

    <!-- Overdue Rents -->
    <div class="col-12" id="overdue-rents-section">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <a href="{{ route('dashboard.overdue-rents') }}" class="text-decoration-none text-reset">Overdue Rent</a>
                </h5>
            </div>
            <div class="card-body">
                @if($overdueRentCount > 0)
                    <div class="dashboard-overdue-alert is-active" role="alert" aria-live="polite">
                        <div class="dashboard-overdue-alert-icon" aria-hidden="true">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="dashboard-overdue-alert-copy">
                            <div class="dashboard-overdue-alert-title">
                                {{ $overdueRentCount }} overdue {{ \Illuminate\Support\Str::plural('rent alert', $overdueRentCount) }}
                            </div>
                            <p class="mb-0">
                                This alert stays visible until the outstanding payment is recorded and the rent is no longer overdue.
                            </p>
                            <div class="dashboard-overdue-alert-preview">
                                @foreach($overduePreviewItems as $rent)
                                    <span class="dashboard-overdue-alert-pill">
                                        {{ $rent->tenant->full_name }} - {{ $rent->unit->building->name }} / {{ $rent->unit->unit_number }}
                                    </span>
                                @endforeach
                                @if($overdueRentCount > $overduePreviewItems->count())
                                    <span class="dashboard-overdue-alert-pill more">+{{ $overdueRentCount - $overduePreviewItems->count() }} more</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="dashboard-overdue-alert is-clear" role="status" aria-live="polite">
                        <div class="dashboard-overdue-alert-icon" aria-hidden="true">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="dashboard-overdue-alert-copy">
                            <div class="dashboard-overdue-alert-title">No overdue rent alerts</div>
                            <p class="mb-0">
                                All recorded rent payments are currently up to date.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('dashboard.overdue-rents') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->isAdmin() || Auth::user()->isManager())
<div class="row g-2 mt-3" id="manager-announcements-section">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    {{ Auth::user()->isAdmin() ? 'All Active Announcements' : 'Announcements For Your Buildings' }}
                </h5>
            </div>
            <div class="card-body">
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
            </div>
        </div>
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
            var series = @json($chartDatasets);
            var palette = [
                { border: '#7c3aed', fill: 'rgba(124, 58, 237, 0.12)' },
                { border: '#2563eb', fill: 'rgba(37, 99, 235, 0.12)' },
                { border: '#059669', fill: 'rgba(5, 150, 105, 0.12)' },
                { border: '#d97706', fill: 'rgba(217, 119, 6, 0.12)' },
                { border: '#dc2626', fill: 'rgba(220, 38, 38, 0.12)' }
            ];
            var multiBuilding = series.length > 1;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: series.map(function (item, index) {
                        var colors = palette[index % palette.length];

                        return {
                            label: item.label,
                            data: item.data,
                            backgroundColor: colors.fill,
                            borderColor: colors.border,
                            borderWidth: 2,
                            tension: 0.3,
                            fill: !multiBuilding,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: colors.border,
                            pointBorderWidth: 2,
                            pointRadius: multiBuilding ? 3 : 4,
                            pointHoverRadius: 6
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: multiBuilding,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 14,
                                font: {
                                    size: 12,
                                    family: "'Nunito', sans-serif"
                                }
                            }
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
                            displayColors: true,
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
                                    if (value >= 1000000) return '₦' + (value/1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return '₦' + (value/1000).toFixed(0) + 'k';
                                    return '₦' + value;
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
