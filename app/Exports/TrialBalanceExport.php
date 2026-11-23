<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class TrialBalanceExport implements FromArray, WithStyles, WithColumnWidths, WithEvents
{
    protected $data;
    protected $totals;
    protected $period;
    protected $branchName;

    public function __construct($data, $totals, $period, $branchName = 'All Branches')
    {
        $this->data = $data;
        $this->totals = $totals;
        $this->period = $period;
        $this->branchName = $branchName;
    }

    public function array(): array
    {
        $rows = [];

        // Title Row
        $rows[] = ['TRIAL BALANCE AS AT ' . strtoupper(\Carbon\Carbon::parse($this->period['end_date'])->format('d M Y'))];
        $rows[] = []; // Empty row

        // Add branch name if specific branch
        if ($this->branchName != 'All Branches') {
            $rows[] = ['Branch: ' . $this->branchName];
            $rows[] = [];
        }

        // Headers
        $rows[] = ['S/NO', 'DETAILS', 'FOLIO', 'DR', 'CR'];

        $rowNumber = 1;
        $folioNumber = 1;

        // Opening Stock
        $rows[] = [
            $rowNumber++,
            'OPENING STOCK (Gold)',
            'GL',
            number_format($this->totals['gold_stock']['opening_value'] ?? 0, 0),
            ''
        ];

        // Cash
        $rows[] = [
            $rowNumber++,
            'CASH',
            'GL',
            number_format($this->totals['cash']['closing'] ?? 0, 0),
            ''
        ];

        // Sales (Credit)
        $rows[] = [
            $rowNumber++,
            'SALES',
            'SL',
            '',
            number_format($this->totals['transactions']['sales_value'] ?? 0, 0)
        ];

        // Purchases (Debit)
        $rows[] = [
            $rowNumber++,
            'PURCHASES',
            'PL',
            number_format($this->totals['transactions']['purchases_value'] ?? 0, 0),
            ''
        ];

        // Loans Issued
        $rows[] = [
            $rowNumber++,
            'LOANS ISSUED',
            'GL',
            number_format($this->totals['loans']['total_issued'] ?? 0, 0),
            ''
        ];

        // Loans Repaid (Credit)
        $rows[] = [
            $rowNumber++,
            'LOANS REPAID',
            'GL',
            '',
            number_format($this->totals['loans']['total_repaid'] ?? 0, 0)
        ];

        // Customer Outstanding (Accounts Receivable)
        $rows[] = [
            $rowNumber++,
            'ACCOUNTS RECEIVABLE',
            'SL',
            number_format($this->totals['customers']['total_outstanding'] ?? 0, 0),
            ''
        ];

        // Supplier Outstanding (Accounts Payable)
        $rows[] = [
            $rowNumber++,
            'ACCOUNTS PAYABLE',
            'PL',
            '',
            number_format($this->totals['suppliers']['total_outstanding'] ?? 0, 0)
        ];

        // Expenses
        $rows[] = [
            $rowNumber++,
            'EXPENSES',
            'GL',
            number_format($this->totals['expenses']['total_expenses'] ?? 0, 0),
            ''
        ];

        // Add a blank row before total
        $rows[] = [];

        // Total row
        $totalDebit = $this->calculateTotalDebit();
        $totalCredit = $this->calculateTotalCredit();
        
        $rows[] = ['TOTAL', '', '', number_format($totalDebit, 0), number_format($totalCredit, 0)];

        return $rows;
    }

    private function calculateTotalDebit()
    {
        $total = 0;
        $total += $this->totals['gold_stock']['opening_value'] ?? 0;
        $total += $this->totals['cash']['closing'] ?? 0;
        $total += $this->totals['transactions']['purchases_value'] ?? 0;
        $total += $this->totals['loans']['total_issued'] ?? 0;
        $total += $this->totals['customers']['total_outstanding'] ?? 0;
        $total += $this->totals['expenses']['total_expenses'] ?? 0;
        return $total;
    }

    private function calculateTotalCredit()
    {
        $total = 0;
        $total += $this->totals['transactions']['sales_value'] ?? 0;
        $total += $this->totals['loans']['total_repaid'] ?? 0;
        $total += $this->totals['suppliers']['total_outstanding'] ?? 0;
        return $total;
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 35,
            'C' => 12,
            'D' => 25,
            'E' => 25,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the highest row
                $highestRow = $sheet->getHighestRow();

                // Style the title
                $sheet->mergeCells('A1:E1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Style the headers (row 4 if no branch, row 5 if branch is shown)
                $headerRow = ($this->branchName != 'All Branches') ? 5 : 4;
                
                // Headers style
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1a237e');
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Find total row
                $totalRow = $highestRow;
                for ($i = $highestRow; $i >= 1; $i--) {
                    $cellValue = $sheet->getCell('A' . $i)->getValue();
                    if ($cellValue == 'TOTAL') {
                        $totalRow = $i;
                        break;
                    }
                }

                // Style total row
                if ($totalRow) {
                    $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFe8eaf6');
                    $sheet->getStyle('D' . $totalRow . ':E' . $totalRow)->getFont()->setBold(true);
                }

                // Format debit and credit columns (D and E) as numbers
                $sheet->getStyle('D2:D' . $highestRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('E2:E' . $highestRow)->getNumberFormat()->setFormatCode('#,##0');

                // Add borders
                $sheet->getStyle('A' . $headerRow . ':E' . $highestRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Style folio column as text
                $sheet->getStyle('C' . $headerRow . ':C' . $highestRow)->getFont()->setName('Arial');
            },
        ];
    }
}