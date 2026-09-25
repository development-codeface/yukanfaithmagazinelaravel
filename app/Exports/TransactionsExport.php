<?php

namespace App\Exports;

use App\Models\Transactions;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $runningBalance = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Transactions::with('dairy');

        // Apply filters
        if (!empty($this->filters['dairy_id'])) {
            $query->where('dairy_id', $this->filters['dairy_id']);
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['reference_no'])) {
            $query->where('reference_no', 'like', "%{$this->filters['reference_no']}%");
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
        }

        // Order ascending to calculate running balance properly
        return $query->orderBy('transaction_date', 'asc')->get();
    }

    /**
     * Map each row for export
     */
    public function map($txn): array
    {
        // Calculate running balance
        if ($txn->type === 'credit') {
            $this->runningBalance += $txn->amount;
        } elseif (in_array($txn->type, ['debit', 'refund', 'hold'])) {
            $this->runningBalance -= $txn->amount;
        }

        return [
            Carbon::parse($txn->transaction_date)->format('d M Y'),
            $txn->dairy->name ?? '-',
            $txn->reference_no ?? '-',
            $txn->description ?? '-',
            in_array($txn->type, ['debit', 'refund', 'hold']) ? $txn->amount : '',
            $txn->type === 'credit' ? $txn->amount : '',
            number_format($this->runningBalance, 2),
            ucfirst($txn->status),
        ];
    }

    /**
     * Excel headings
     */
    public function headings(): array
    {
        return [
            'Date',
            'Dairy',
            'Reference No',
            'Description ',
            'Debit (₹)',
            'Credit (₹)',
            'Balance (₹)',
            'Status',
        ];
    }
}
