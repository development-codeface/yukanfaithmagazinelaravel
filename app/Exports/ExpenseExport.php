<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $totalAmount = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Expense::with(['dairy', 'category']);

        // Apply filters
        if (!empty($this->filters['dairy_id'])) {
            $query->where('dairy_id', $this->filters['dairy_id']);
        }

        if (!empty($this->filters['expensecategory_id'])) {
            $query->where('expensecategory_id', $this->filters['expensecategory_id']);
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [$this->filters['start_date'], $this->filters['end_date']]);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Map each row for export
     */
    public function map($expense): array
    {
        // Add amount to total for summing
        $this->totalAmount += $expense->amount;

        return [
            Carbon::parse($expense->created_at)->format('d M Y'),
            $expense->dairy->name ?? '-',
            $expense->category->name ?? '-',
            $expense->expense_item ?? '-',
            number_format($expense->amount, 2),
            $expense->description ?? '-',
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
            'Expense Category',
            'Expense Item',
            'Amount (₹)',
            'Description',
        ];
    }

    /**
     * Append total row for export
     */
    public function getTotalAmount(): string
    {
        return number_format($this->totalAmount, 2);
    }
}
