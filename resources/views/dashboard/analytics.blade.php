@extends('layouts.app')

@section('content')
<style>
    .analytics-canvas-wrap {
        position: relative;
        height: 220px;
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

<x-ui.page-header
    id="analytics-header"
    title="Dashboard Analytics"
    :subtitle="'Scope: '.$analyticsSummary['scope_label']"
>
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('dashboard')">Back To Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div id="analytics-summary-cards" class="row g-2 mb-3">
    <div class="col-md-3">
        <x-ui.stat-card label="Buildings In Scope" :value="$analyticsSummary['building_count']" icon="bi bi-buildings" tone="primary" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Completed Payments" :value="number_format($analyticsSummary['payment_count'])" icon="bi bi-cash-stack" tone="info" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Income Last 6 Months" :value="'₦'.number_format($analyticsSummary['last_six_months_income_total'], 2)" icon="bi bi-graph-up-arrow" tone="success" />
    </div>
    <div class="col-md-3">
        <x-ui.stat-card label="Expenses Last 6 Months" :value="'₦'.number_format($analyticsSummary['last_six_months_expense_total'], 2)" icon="bi bi-graph-down-arrow" tone="danger" />
    </div>
</div>

<x-ui.card id="analytics-notes" title="Analyst Notes" class="mb-3">
    <ul class="analytics-note-list">
        @foreach($analyticsInsights as $insight)
            <li>{{ $insight }}</li>
        @endforeach
    </ul>
</x-ui.card>

<div class="row g-2">
    <div class="col-lg-6">
        <x-ui.card id="analytics-building-revenue" title="Revenue By Building" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="buildingRevenueChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-monthly-trend" title="Monthly Revenue Trend" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-yearly-trend" title="Yearly Revenue Trend" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="yearlyTrendChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-payment-methods" title="Payments By Method" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="small text-muted mt-2">Tooltip shows both amount and number of payments.</div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-overdue-balances" title="Overdue Balances By Building" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="overdueBalanceChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-occupancy" title="Occupancy Rate By Building" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="occupancyChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-tenant-distribution" title="Tenant Distribution" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="tenantDistributionChart"></canvas>
            </div>
        </x-ui.card>
    </div>
    <div class="col-lg-6">
        <x-ui.card id="analytics-income-expenses" title="Income Vs Expenses" class="h-100">
            <div class="analytics-canvas-wrap">
                <canvas id="incomeExpenseChart"></canvas>
            </div>
            <div class="small text-muted mt-2">Income combines rent payments and accounting income entries.</div>
        </x-ui.card>
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
