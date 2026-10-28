@extends('layouts.app')

@section('content')
<style>
    .analytics-page .card {
        border-radius: 0.75rem;
    }

    .analytics-page .card-header {
        padding: 0.75rem 1rem;
    }

    .analytics-page .card-body {
        padding: 0.9rem 1rem;
    }

    .analytics-canvas-wrap {
        position: relative;
        height: 220px;
    }

    .analytics-summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .analytics-note-list {
        padding-left: 1rem;
        margin-bottom: 0;
    }

    .analytics-note-list li + li {
        margin-top: 0.35rem;
    }

    @media (max-width: 767.98px) {
        .analytics-canvas-wrap {
            height: 190px;
        }
    }
</style>

<div class="analytics-page">
<div id="analytics-header" class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-1 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Dashboard Analytics</h1>
        <div class="text-muted small">Scope: {{ $analyticsSummary['scope_label'] }}</div>
    </div>
    <div class="ms-auto">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Back To Dashboard</a>
    </div>
</div>

<div id="analytics-summary-cards" class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold">Buildings In Scope</div>
                <div class="analytics-summary-value">{{ $analyticsSummary['building_count'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold">Completed Payments</div>
                <div class="analytics-summary-value">{{ number_format($analyticsSummary['payment_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold">Income Last 6 Months</div>
                <div class="analytics-summary-value text-success">₦{{ number_format($analyticsSummary['last_six_months_income_total'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold">Expenses Last 6 Months</div>
                <div class="analytics-summary-value text-danger">₦{{ number_format($analyticsSummary['last_six_months_expense_total'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div id="analytics-notes" class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white">
        <h5 class="mb-0">Analyst Notes</h5>
    </div>
    <div class="card-body">
        <ul class="analytics-note-list">
            @foreach($analyticsInsights as $insight)
                <li>{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row g-2">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-building-revenue" class="card-header bg-white">
                <h5 class="mb-0">Revenue By Building</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="buildingRevenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-monthly-trend" class="card-header bg-white">
                <h5 class="mb-0">Monthly Revenue Trend</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-yearly-trend" class="card-header bg-white">
                <h5 class="mb-0">Yearly Revenue Trend</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="yearlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-payment-methods" class="card-header bg-white">
                <h5 class="mb-0">Payments By Method</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
                <div class="small text-muted mt-2">Tooltip shows both amount and number of payments.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-overdue-balances" class="card-header bg-white">
                <h5 class="mb-0">Overdue Balances By Building</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="overdueBalanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-occupancy" class="card-header bg-white">
                <h5 class="mb-0">Occupancy Rate By Building</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="occupancyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-tenant-distribution" class="card-header bg-white">
                <h5 class="mb-0">Tenant Distribution</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="tenantDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div id="analytics-income-expenses" class="card-header bg-white">
                <h5 class="mb-0">Income Vs Expenses</h5>
            </div>
            <div class="card-body">
                <div class="analytics-canvas-wrap">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
                <div class="small text-muted mt-2">Income combines rent payments and accounting income entries.</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currencyFormatter = new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            maximumFractionDigits: 2,
        });

        const amountTick = (value) => {
            if (value >= 1000000) return '₦' + (value / 1000000).toFixed(1) + 'M';
            if (value >= 1000) return '₦' + (value / 1000).toFixed(0) + 'k';
            return '₦' + value;
        };

        const palette = ['#7c3aed', '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316'];

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: {
                            family: "'Nunito', sans-serif"
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.92)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        family: "'Nunito', sans-serif"
                    },
                    bodyFont: {
                        size: 13,
                        family: "'Nunito', sans-serif"
                    }
                }
            }
        };

        new Chart(document.getElementById('buildingRevenueChart'), {
            type: 'bar',
            data: {
                labels: @json($buildingChart['labels']),
                datasets: [{
                    label: 'Revenue Collected',
                    data: @json($buildingChart['data']),
                    backgroundColor: palette[0],
                    borderRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false },
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return currencyFormatter.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: amountTick
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('monthlyTrendChart'), {
            type: 'line',
            data: {
                labels: @json($monthlyTrendChart['labels']),
                datasets: [{
                    label: 'Monthly Revenue',
                    data: @json($monthlyTrendChart['data']),
                    borderColor: palette[1],
                    backgroundColor: 'rgba(37, 99, 235, 0.14)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return currencyFormatter.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: amountTick
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('yearlyTrendChart'), {
            type: 'bar',
            data: {
                labels: @json($yearlyTrendChart['labels']),
                datasets: [{
                    label: 'Yearly Revenue',
                    data: @json($yearlyTrendChart['data']),
                    backgroundColor: palette[2],
                    borderRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false },
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return currencyFormatter.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: amountTick
                        }
                    }
                }
            }
        });

        const paymentMethodCounts = @json($paymentMethodChart['counts']);
        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: @json($paymentMethodChart['labels']),
                datasets: [{
                    label: 'Payment Method Value',
                    data: @json($paymentMethodChart['amounts']),
                    backgroundColor: palette,
                    hoverOffset: 10
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                const count = paymentMethodCounts[context.dataIndex] ?? 0;
                                return currencyFormatter.format(context.parsed) + ' across ' + count + ' payment(s)';
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('overdueBalanceChart'), {
            type: 'bar',
            data: {
                labels: @json($overdueChart['labels']),
                datasets: [{
                    label: 'Overdue Balance',
                    data: @json($overdueChart['data']),
                    backgroundColor: palette[4],
                    borderRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false },
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return currencyFormatter.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: amountTick
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('occupancyChart'), {
            type: 'bar',
            data: {
                labels: @json($occupancyChart['labels']),
                datasets: [{
                    label: 'Occupancy Rate (%)',
                    data: @json($occupancyChart['data']),
                    backgroundColor: palette[5],
                    borderRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false },
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + '% occupied';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('tenantDistributionChart'), {
            type: 'polarArea',
            data: {
                labels: @json($tenantDistributionChart['labels']),
                datasets: [{
                    label: 'Active Tenants',
                    data: @json($tenantDistributionChart['data']),
                    backgroundColor: palette.map((color) => color + 'cc'),
                    borderWidth: 1
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return (context.raw ?? 0) + ' active tenant(s)';
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('incomeExpenseChart'), {
            type: 'line',
            data: {
                labels: @json($incomeExpenseChart['labels']),
                datasets: [{
                    label: 'Income',
                    data: @json($incomeExpenseChart['income']),
                    borderColor: palette[2],
                    backgroundColor: 'rgba(16, 185, 129, 0.14)',
                    fill: true,
                    tension: 0.25
                }, {
                    label: 'Expenses',
                    data: @json($incomeExpenseChart['expenses']),
                    borderColor: palette[4],
                    backgroundColor: 'rgba(239, 68, 68, 0.10)',
                    fill: true,
                    tension: 0.25
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + currencyFormatter.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: amountTick
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
