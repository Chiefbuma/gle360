<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanSummaryWidget extends ApexChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected static ?string $chartId = 'loanSummaryChart';
    protected static ?string $heading = 'Loan Performance Summary';

    protected float $totalDisbursed = 0;
    protected float $totalPaid = 0;
    protected float $totalDefaulted = 0;
    protected int $totalDefaults = 0;
    protected float $totalRevenue = 0;
    protected array $cumulativeSummary = [];
    protected array $periodComparison = [];

    protected function getSeries(): array
    {
        // Set default date range to current month
        $maxDate = Loan::max('date_released');
        $currentDate = $maxDate ? Carbon::parse($maxDate) : now();
        $startDate = $this->filters['startDate'] ?? $currentDate->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $this->filters['endDate'] ?? $currentDate->format('Y-m-d');

        // Get overall summary
        $summary = DB::table('loan')
            ->leftJoin(DB::raw('(
                SELECT loan_id, SUM(payment_amount) as total_payments
                FROM payment
                GROUP BY loan_id
            ) as payment'), 'loan.loan_id', '=', 'payment.loan_id')
            ->when($startDate, fn($query) => $query->whereDate('loan.date_released', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('loan.date_released', '<=', $endDate))
            ->selectRaw('
                SUM(CASE WHEN loan.status IN (0, 1) THEN loan.total_loan ELSE 0 END) as total_disbursed,
                SUM(CASE WHEN loan.status < 4 THEN COALESCE(payment.total_payments, 0) ELSE 0 END) as total_paid,
                SUM(CASE WHEN loan.status = 3 THEN (loan.total_loan - COALESCE(payment.total_payments, 0)) ELSE 0 END) as total_defaulted,
                COUNT(CASE WHEN loan.status = 3 THEN 1 END) as total_defaults,
                SUM(CASE WHEN loan.status < 4 THEN (loan.total_loan - loan.amount) ELSE 0 END) as total_revenue
            ')
            ->first();

        $this->totalDisbursed = $summary->total_disbursed ?? 0;
        $this->totalPaid = $summary->total_paid ?? 0;
        $this->totalDefaulted = $summary->total_defaulted ?? 0;
        $this->totalDefaults = $summary->total_defaults ?? 0;
        $this->totalRevenue = $summary->total_revenue ?? 0;

        // Calculate repayment rate for the chart
        $repaymentRate = $this->totalDisbursed > 0
            ? round(($this->totalPaid / $this->totalDisbursed) * 100, 2)
            : 0;

        // Create cumulative summary
        $this->cumulativeSummary = [[
            'total_disbursed' => number_format($this->totalDisbursed, 0),
            'total_paid' => number_format($this->totalPaid, 0),
            'total_defaulted' => number_format($this->totalDefaulted, 0),
            'total_defaults' => number_format($this->totalDefaults, 0),
            'total_revenue' => number_format($this->totalRevenue, 0),
            'repayment_rate' => number_format($repaymentRate, 0),
        ]];

        // Get Period Comparison data
        $filterStartDate = Carbon::parse($startDate);
        $filterEndDate = Carbon::parse($endDate);

        // Current period
        $currentPeriodStart = $filterStartDate;
        $currentPeriodEnd = $filterEndDate;

        // Previous period (same day numbers in previous month)
        $previousPeriodStart = $filterStartDate->copy()->subMonth()->day($filterStartDate->day);
        $previousPeriodEnd = $filterEndDate->copy()->subMonth()->day($filterEndDate->day);

        // Adjust for months with fewer days
        if ($previousPeriodStart->day != $filterStartDate->day) {
            $previousPeriodStart->endOfMonth();
        }
        if ($previousPeriodEnd->day != $filterEndDate->day) {
            $previousPeriodEnd->endOfMonth();
        }

        // Current period data
        $currentPeriodData = DB::table('loan')
            ->leftJoin(DB::raw('(
                SELECT loan_id, SUM(payment_amount) as total_payments
                FROM payment
                GROUP BY loan_id
            ) as payment'), 'loan.loan_id', '=', 'payment.loan_id')
            ->whereBetween('loan.date_released', [$currentPeriodStart, $currentPeriodEnd])
            ->selectRaw('
                SUM(CASE WHEN loan.status IN (0, 1) THEN loan.total_loan ELSE 0 END) as total_disbursed,
                SUM(CASE WHEN loan.status < 4 THEN COALESCE(payment.total_payments, 0) ELSE 0 END) as total_paid,
                SUM(CASE WHEN loan.status = 3 THEN (loan.total_loan - COALESCE(payment.total_payments, 0)) ELSE 0 END) as total_defaulted,
                COUNT(CASE WHEN loan.status = 3 THEN 1 END) as total_defaults,
                SUM(CASE WHEN loan.status < 4 THEN (loan.total_loan - loan.amount) ELSE 0 END) as total_revenue
            ')
            ->first();

        // Previous period data
        $previousPeriodData = DB::table('loan')
            ->leftJoin(DB::raw('(
                SELECT loan_id, SUM(payment_amount) as total_payments
                FROM payment
                GROUP BY loan_id
            ) as payment'), 'loan.loan_id', '=', 'payment.loan_id')
            ->whereBetween('loan.date_released', [$previousPeriodStart, $previousPeriodEnd])
            ->selectRaw('
                SUM(CASE WHEN loan.status IN (2, 3) THEN loan.total_loan ELSE 0 END) as total_disbursed,
                SUM(CASE WHEN loan.status = 3 THEN COALESCE(payment.total_payments, 0) ELSE 0 END) as total_paid,
                SUM(CASE WHEN loan.status = 5 THEN (loan.total_loan - COALESCE(payment.total_payments, 0)) ELSE 0 END) as total_defaulted,
                COUNT(CASE WHEN loan.status = 5 THEN 1 END) as total_defaults,
                SUM(CASE WHEN loan.status = 3 THEN (loan.total_loan - loan.amount) ELSE 0 END) as total_revenue
            ')
            ->first();

        $currentRepaymentRate = ($currentPeriodData->total_disbursed ?? 0) > 0
            ? round(($currentPeriodData->total_paid ?? 0) / ($currentPeriodData->total_disbursed ?? 1) * 100, 2)
            : 0;

        $previousRepaymentRate = ($previousPeriodData->total_disbursed ?? 0) > 0
            ? round(($previousPeriodData->total_paid ?? 0) / ($previousPeriodData->total_disbursed ?? 1) * 100, 2)
            : 0;

        $this->periodComparison = [
            'current_period' => [
                'total_disbursed' => number_format($currentPeriodData->total_disbursed ?? 0, 0),
                'total_paid' => number_format($currentPeriodData->total_paid ?? 0, 0),
                'total_defaulted' => number_format($currentPeriodData->total_defaulted ?? 0, 0),
                'total_defaults' => number_format($currentPeriodData->total_defaults ?? 0, 0),
                'total_revenue' => number_format($currentPeriodData->total_revenue ?? 0, 0),
                'repayment_rate' => number_format($currentRepaymentRate, 0),
                'start_date' => $currentPeriodStart,
                'end_date' => $currentPeriodEnd,
            ],
            'previous_period' => [
                'total_disbursed' => number_format($previousPeriodData->total_disbursed ?? 0, 0),
                'total_paid' => number_format($previousPeriodData->total_paid ?? 0, 2),
                'total_defaulted' => number_format($previousPeriodData->total_defaulted ?? 0,0),
                'total_defaults' => number_format($previousPeriodData->total_defaults ?? 0, 0),
                'total_revenue' => number_format($previousPeriodData->total_revenue ?? 0, 0),
                'repayment_rate' => number_format($previousRepaymentRate, 0),
                'start_date' => $previousPeriodStart,
                'end_date' => $previousPeriodEnd,
            ],
        ];

        return [round($repaymentRate, 0)];
    }

    protected function getOptions(): array
    {
        return [
            'chart' => [
                'type' => 'radialBar',
                'background' => '#000000',
                'height' => 450,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => $this->getSeries(),
            'plotOptions' => [
                'radialBar' => [
                    'startAngle' => -140,
                    'endAngle' => 130,
                    'hollow' => [
                        'size' => '50%',
                        'background' => 'transparent',
                    ],
                    'track' => [
                        'background' => 'transparent',
                        'strokeWidth' => '100%',
                    ],
                    'dataLabels' => [
                        'showOn' => 'always',
                        'name' => [
                            'offsetY' => -10,
                            'show' => true,
                            'color' => 'white',
                            'fontSize' => '13px',
                            'fontWeight' => 800,
                            'fontFamily' => 'inherit',
                        ],
                        'value' => [
                            'color' => 'white',
                            'fontSize' => '40px',
                            'show' => true,
                            'fontWeight' => 600,
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'horizontal',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#f59e0b'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 0.6,
                    'stops' => [30, 70, 100],
                ],
            ],
            'stroke' => [
                'lineCap' => 'round',
                'dashArray' => 10,
            ],
            'labels' => ['Repayment Rate %'],
            'colors' => ['#16a34a'],
        ];
    }

    protected function getFooter(): ?string
    {
        $cumulativeTableRows = '';
        $periodComparisonRows = '';

        // Get date filters
        $maxDate = Loan::max('date_released');
        $currentDate = $maxDate ? Carbon::parse($maxDate) : now();
        $startDate = $this->filters['startDate'] ?? $currentDate->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $this->filters['endDate'] ?? $currentDate->format('Y-m-d');
        $filterStartDate = Carbon::parse($startDate);
        $filterEndDate = Carbon::parse($endDate);

        // Previous period dates
        $previousPeriodStart = $filterStartDate->copy()->subMonth()->day($filterStartDate->day);
        $previousPeriodEnd = $filterEndDate->copy()->subMonth()->day($filterEndDate->day);

        // Adjust for months with fewer days
        if ($previousPeriodStart->day != $filterStartDate->day) {
            $previousPeriodStart->endOfMonth();
        }
        if ($previousPeriodEnd->day != $filterEndDate->day) {
            $previousPeriodEnd->endOfMonth();
        }

        // Generate cumulative performance row
        if (!empty($this->cumulativeSummary)) {
            $record = $this->cumulativeSummary[0];
            $cumulativeTableRows = <<<HTML
            <tr style='cursor: pointer; transition: background-color 0.2s;'>

                <td style='padding: 12px; text-align: right; font-size: 15px; font-weight: 500;'> {$record['total_disbursed']}</td>
                <td style='padding: 12px; text-align: right; font-size: 15px; font-weight: 500;'> {$record['total_paid']}</td>
                <td style='padding: 12px; text-align: right; font-size: 15px; font-weight: 500;'> {$record['total_defaulted']}</td>
                <td style='padding: 12px; text-align: right; font-size: 15px; font-weight: 500;'> {$record['total_revenue']}</td>
                <td style='padding: 12px; text-align: right; font-size: 15px; font-weight: bold; color: orange;'>{$record['repayment_rate']}%</td>
            </tr>
            HTML;
        }

        // Generate Period Comparison table
        if (!empty($this->periodComparison)) {
            $current = $this->periodComparison['current_period'];
            $previous = $this->periodComparison['previous_period'];

            $currentRange = $current['start_date']->format('M') . '(' . $current['start_date']->format('j') . '-' . $current['end_date']->format('j') . ')';
            $previousRange = $previous['start_date']->format('M') . '(' . $previous['start_date']->format('j') . '-' . $previous['end_date']->format('j') . ')';

            $periodComparisonRows = <<<HTML
            <div class="spacer">
                <div class="table-container">
                    <div class="title-container">
                        <h2 style="color: white; margin: 0; font-size: 15px;">Period Comparison</h2>
                        <p style="color: rgba(255,255,255,0.7); margin: 4px 0 0 0; font-size: 15px;">
                            Period: {$filterStartDate->format('M j')} - {$filterEndDate->format('M j, Y')}
                        </p>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="period-comparison-table">
                            <thead>
                                <tr>
                                    <th style="width: 200px; text-align: left; font-size: 15px; padding: 14px 12px;">Metric</th>
                                    <th style="text-align: right; font-size: 15px; padding: 14px 12px;">{$currentRange}</th>
                                    <th style="text-align: right; font-size: 15px; padding: 14px 12px;">{$previousRange}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style='font-weight: 500; font-size: 15px; padding: 14px 12px;'>Loans Disbursed</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$current['total_disbursed']}</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$previous['total_disbursed']}</td>
                                </tr>
                                <tr>
                                    <td style='font-weight: 500; font-size: 15px; padding: 14px 12px;'>Loans Paid</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$current['total_paid']}</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$previous['total_paid']}</td>
                                </tr>
                                <tr>
                                    <td style='font-weight: 500; font-size: 15px; padding: 14px 12px;'>Loans Defaulted</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$current['total_defaulted']}</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$previous['total_defaulted']}</td>
                                </tr>
                                
                                <tr>
                                    <td style='font-weight: 500; font-size: 15px; padding: 14px 12px;'>Revenue</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$current['total_revenue']}</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'> {$previous['total_revenue']}</td>
                                </tr>
                                <tr>
                                    <td style='font-weight: 500; font-size: 15px; padding: 14px 12px;'>Repayment Rate</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'>{$current['repayment_rate']}%</td>
                                    <td style='text-align: right; font-size: 15px; padding: 14px 12px;'>{$previous['repayment_rate']}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap");
    
        .title-container {
            text-align: left;
            font-size: 23px;
            padding: 12px;
            border-radius: 12px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            font-family: "Bai Jamjuree", sans-serif;
            margin-bottom: 16px;
        }
    
        .table-container {
            font-family: "Bai Jamjuree", sans-serif;
            background-color: black;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
    
        .table-wrapper {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 10px;
            &::-webkit-scrollbar {
                display: none;
            }
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    
        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }
    
        th {
            position: sticky;
            top: 0;
            background-color: #1a1a1a;
            text-align: left;
            padding: 12px 10px;
            font-weight: 600;
            font-size: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 10;
        }
    
        td {
            padding: 10px;
            text-align: left;
        }
    
        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
    
        .spacer {
            margin-top: 24px;
        }
        
        .cumulative-table th {
            font-size: 15px;
            padding: 15px 12px;
        }
        
        .period-comparison-table th {
            font-size: 15px;
            padding: 14px 12px;
        }
        
        .period-comparison-table td {
            font-size: 15px;
            padding: 14px 12px;
        }
    </style>
    
    <!-- Cumulative Performance Table -->
    <div class="spacer">
        <div class="table-container">
            <div class="title-container">
                <h2 style="color: white; margin: 0;">Cumulative Loan Performance</h2>
                <p style="color: rgba(255,255,255,0.7); margin: 4px 0 0 0; font-size: 15px;">
                    Period: {$filterStartDate->format('M j')} - {$filterEndDate->format('M j, Y')}
                </p>
            </div>
            
            <div class="table-wrapper">
                <table class="cumulative-table">
                    <thead>
                        <tr>
                           
                            <th style="text-align: right;">Disbursed</th>
                            <th style="text-align: right;">Paid</th>
                            <th style="text-align: right;">Defaulted</th>
                            <th style="text-align: right;">Revenue</th>
                            <th style="text-align: right;">Repayment Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$cumulativeTableRows}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Period Comparison Table -->
    {$periodComparisonRows}
    
    <script>
        function handleRowClick(row, id) {
            // Row click handler logic (can be customized)
        }
    </script>
    HTML;
    }
}