<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Account Categories
    |--------------------------------------------------------------------------
    */
    'account_categories' => [
        'asset' => [
            'label' => 'Assets',
            'icon' => 'ik ik-pie-chart',
            'color' => 'blue',
        ],
        'liability' => [
            'label' => 'Liabilities',
            'icon' => 'ik ik-credit-card',
            'color' => 'red',
        ],
        'equity' => [
            'label' => 'Equity',
            'icon' => 'ik ik-dollar-sign',
            'color' => 'green',
        ],
        'revenue' => [
            'label' => 'Revenue',
            'icon' => 'ik ik-trending-up',
            'color' => 'green',
        ],
        'expense' => [
            'label' => 'Expenses',
            'icon' => 'ik ik-trending-down',
            'color' => 'red',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Mapping
    |--------------------------------------------------------------------------
    */
    'accounts' => [
        // ============================================
        // ASSETS
        // ============================================
        'cash' => [
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Total cash across all branches',
            'table' => 'branch_balances',
            'fields' => ['amount'],
            'conditions' => "balance_type = 'cash'",
            'group' => 'Current Assets',
            'calculation' => 'sum',
            'visible' => true,
        ],
        'gold_inventory' => [
            'code' => '1020',
            'name' => 'Gold Inventory',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Total gold inventory across all branches',
            'table' => 'branch_balances',
            'fields' => ['amount'],
            'conditions' => "balance_type = 'gold'",
            'group' => 'Current Assets',
            'calculation' => 'sum',
            'visible' => true,
            'is_gold' => true,
        ],
        'accounts_receivable' => [
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Outstanding customer balances',
            'table' => 'customers',
            'fields' => ['outstanding_balance'],
            'conditions' => "outstanding_balance > 0",
            'group' => 'Current Assets',
            'calculation' => 'sum',
            'visible' => true,
        ],

        // ============================================
        // LIABILITIES
        // ============================================
        'loans_payable' => [
            'code' => '2010',
            'name' => 'Loans Payable',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'description' => 'Outstanding loan amounts',
            'table' => 'loans',
            'fields' => ['amount'],
            'conditions' => "status IN ('approved', 'completed')",
            'group' => 'Long-term Liabilities',
            'calculation' => 'sum',
            'visible' => true,
        ],
        'accounts_payable' => [
            'code' => '2100',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'description' => 'Outstanding supplier balances',
            'table' => 'suppliers',
            'fields' => ['outstanding_balance'],
            'conditions' => "outstanding_balance > 0",
            'group' => 'Current Liabilities',
            'calculation' => 'sum',
            'visible' => true,
        ],

        // ============================================
        // EQUITY
        // ============================================
        'owner_capital' => [
            'code' => '3010',
            'name' => "Owner's Capital",
            'type' => 'equity',
            'normal_balance' => 'credit',
            'description' => "Owner's investment in the business",
            'calculation' => 'manual',
            'group' => 'Equity',
            'visible' => true,
            'manual_value' => 150000000,
        ],
        'retained_earnings' => [
            'code' => '3020',
            'name' => 'Retained Earnings',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'description' => 'Accumulated profits',
            'calculation' => 'income_statement',
            'group' => 'Equity',
            'visible' => true,
        ],

        // ============================================
        // REVENUE
        // ============================================
        'gold_sales' => [
            'code' => '4010',
            'name' => 'Gold Sales Revenue',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'description' => 'Revenue from gold sales',
            'table' => 'transactions',
            'fields' => ['amount'],
            'conditions' => "transaction_type = 'gold_sale' AND status = 'completed'",
            'group' => 'Revenue',
            'calculation' => 'sum',
            'visible' => true,
        ],

        // ============================================
        // EXPENSES
        // ============================================
        'gold_purchases' => [
            'code' => '5010',
            'name' => 'Gold Purchases',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'description' => 'Cost of gold purchased',
            'table' => 'transactions',
            'fields' => ['amount'],
            'conditions' => "transaction_type = 'gold_purchase' AND status = 'completed'",
            'group' => 'Cost of Goods Sold',
            'calculation' => 'sum',
            'visible' => true,
        ],
        'operating_expenses' => [
            'code' => '6010',
            'name' => 'Operating Expenses',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'description' => 'All operating expenses',
            'table' => 'expenses',
            'fields' => ['amount'],
            'group' => 'Operating Expenses',
            'calculation' => 'sum',
            'visible' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Groups
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'Current Assets' => [
            'order' => 1,
        ],
        'Fixed Assets' => [
            'order' => 2,
        ],
        'Current Liabilities' => [
            'order' => 3,
        ],
        'Long-term Liabilities' => [
            'order' => 4,
        ],
        'Equity' => [
            'order' => 5,
        ],
        'Revenue' => [
            'order' => 6,
        ],
        'Cost of Goods Sold' => [
            'order' => 7,
        ],
        'Operating Expenses' => [
            'order' => 8,
        ],
        'Other Income' => [
            'order' => 9,
        ],
        'Other Expenses' => [
            'order' => 10,
        ],
    ],
];