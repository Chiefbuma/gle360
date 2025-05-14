<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class LoansExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filterData;

    public function __construct(array $filterData)
    {
        $this->filterData = $filterData;
    }

    public function query(): Builder
    {
        return Loan::query()
            ->whereNotNull('date_released')
            ->when(
                $this->filterData['date_from'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('date_released', '>=', $date)
            )
            ->when(
                $this->filterData['date_until'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('date_released', '<=', $date)
            )
            ->with('borrower');
    }

    public function headings(): array
    {
        return [
            'Loan ID',
            'Borrower',
            'Amount (Ksh)',
            'Total Loan (Ksh)',
            'Daily Amount (Ksh)',
            'Date Released',
            'Paid Amount (Ksh)',
            'Remaining Amount (Ksh)',
            'Due Date',
            'Status',
        ];
    }

    public function map($loan): array
    {
        // Determine status using the same logic as the table column
        $status = 'Pending';
        if ($loan->remainingBalance() == 0) {
            $status = 'Completed';
        } else {
            $dueDate = $loan->due_date ? \Carbon\Carbon::parse($loan->due_date) : null;
            if ($loan->remainingBalance() > 0 && $dueDate && \Carbon\Carbon::today()->gt($dueDate)) {
                $status = 'Overdue';
            }
        }

        return [
            $loan->loan_id,
            $loan->borrower->name,
            (int) round($loan->amount), // Convert to integer
            (int) round($loan->total_loan), // Convert to integer
            (int) round($loan->daily_amount), // Convert to integer
            $loan->date_released ? \Carbon\Carbon::parse($loan->date_released)->format('Y-m-d') : '-',
            (int) round($loan->totalPaid()), // Convert to integer
            (int) round($loan->remainingBalance()), // Convert to integer
            $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->format('Y-m-d') : '-',
            $status, // Use the custom status logic
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '0', // Amount (Ksh) - integer format
            'D' => '0', // Total Loan (Ksh) - integer format
            'E' => '0', // Daily Amount (Ksh) - integer format
            'G' => '0', // Paid Amount (Ksh) - integer format
            'H' => '0', // Remaining Amount (Ksh) - integer format
        ];
    }
}