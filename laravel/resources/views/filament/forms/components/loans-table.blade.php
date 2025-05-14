@php
use App\Models\Loan;

$borrowerId = $this->getForm()->getRawState()['borrower_id'] ?? null;
$loans = $borrowerId 
    ? Loan::where('borrower_id', $borrowerId)
        ->orderBy('disbursement_date')
        ->get()
        ->map(function ($loan) {
            $remaining = $loan->total_loan - $loan->paid_amount;
            return [
                'loan_id' => $loan->loan_id,
                'ref_no' => $loan->ref_no,
                'disbursement_date' => $loan->disbursement_date->format('Y-m-d'),
                'total_loan' => number_format($loan->total_loan, 2),
                'paid_amount' => number_format($loan->paid_amount, 2),
                'remaining' => number_format($remaining, 2),
                'status' => $loan->status,
            ];
        })
    : collect();
@endphp

@if($loans->isNotEmpty())
<div class="overflow-x-auto border border-gray-200 rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loan Ref</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disbursement Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Loan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($loans as $loan)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loan['ref_no'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loan['disbursement_date'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${{ $loan['total_loan'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${{ $loan['paid_amount'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${{ $loan['remaining'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    @if($loan['status'] == 3)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                    @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="p-4 bg-gray-50 rounded-lg text-center text-gray-500">
    @if($borrowerId)
        No loans found for this borrower.
    @else
        Please select a borrower to view their loans.
    @endif
</div>
@endif