<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Main Navigation Menu
    |--------------------------------------------------------------------------
    */
    'main' => [
        // ============================================
        // DASHBOARD
        // ============================================
        [
            'label' => 'Dashboard',
            'icon' => 'ik ik-bar-chart-2',
            'route' => 'dashboard',
            'active' => ['dashboard'],
            'permission' => null,
        ],


        // ============================================
        // TRANSACTIONS
        // ============================================
        [
            'label' => 'Transactions',
            'icon' => 'ik ik-repeat',
            'route' => 'transactions.index',
            'active' => ['transactions.*'],
            'permission' => ['director', 'branch_manager', 'staff'],
            'children' => [
                [
                    'label' => 'Gold Purchases',
                    'icon' => 'ik ik-shopping-bag',
                    'route' => 'transactions.gold.purchases.index',
                    'active' => ['transactions.gold.purchases.*'],
                    'permission' => ['director', 'branch_manager', 'accountant', 'staff'],
                ],
                [
                    'label' => 'Gold Sales',
                    'icon' => 'ik ik-trending-up',
                    'route' => 'transactions.gold.sales.index',
                    'active' => ['transactions.gold.sales.*'],
                    'permission' => ['director', 'branch_manager', 'accountant', 'staff'],
                ],
                [
                    'label' => 'Cash Transactions',
                    'icon' => 'ik ik-dollar-sign',
                    'route' => 'transactions.cash.index',
                    'active' => ['transactions.cash.*'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
            ],
        ],

        // ============================================
        // LOANS
        // ============================================
        [
            'label' => 'Loans',
            'icon' => 'ik ik-file-text',
            'route' => 'loans.index',
            'active' => ['loans.*'],
            'permission' => ['director', 'branch_manager', 'accountant'],
            'children' => [
                [
                    'label' => 'Applications',
                    'icon' => 'ik ik-file-plus',
                    'route' => 'loans.applications.index',
                    'active' => ['loans.applications.*'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Approvals',
                    'icon' => 'ik ik-check-circle',
                    'route' => 'loans.approvals.index',
                    'active' => ['loans.approvals.*'],
                    'permission' => ['director', 'branch_manager'],
                ],
                [
                    'label' => 'Payments',
                    'icon' => 'ik ik-credit-card',
                    'route' => 'loans.payments.index',
                    'active' => ['loans.payments.*'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
            ],
        ],

        // ============================================
        // EXPENSES
        // ============================================
        [
            'label' => 'Expenses',
            'icon' => 'ik ik-dollar-sign',
            'route' => 'finance.expenses.index',
            'active' => ['finance.expenses.*'],
            'permission' => ['director', 'branch_manager', 'accountant'],
            'children' => [
                [
                    'label' => 'All Expenses',
                    'icon' => 'ik ik-list',
                    'route' => 'finance.expenses.index',
                    'active' => ['finance.expenses.index'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Add Expense',
                    'icon' => 'ik ik-plus',
                    'route' => 'finance.expenses.create',
                    'active' => ['finance.expenses.create', 'finance.expenses.edit'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Approvals',
                    'icon' => 'ik ik-check-square',
                    'route' => 'finance.expenses.approvals.index',
                    'active' => ['finance.expenses.approvals.*'],
                    'permission' => ['director', 'branch_manager'],
                ],
                [
                    'label' => 'Categories',
                    'icon' => 'ik ik-grid',
                    'route' => 'finance.expenses.categories.index',
                    'active' => ['finance.expenses.categories.*'],
                    'permission' => ['director', 'accountant'],
                ],
            ],
        ],
        
        
        /*
        [
            'label' => 'Live View',
            'icon' => 'fas fa-video',
            'route' => 'live.index',  // This should be 'live.index'
            'active' => ['live.*'],
            'permission' => ['director', 'branch_manager', 'staff'],
            'children' => [
                [
                    'label' => 'Live Cameras',
                    'icon' => 'fas fa-camera',
                    'route' => 'live.cameras.index',  // CHANGE THIS: was 'live.cameras'
                    'active' => ['live.cameras.*'],
                    'permission' => ['director', 'branch_manager', 'staff'],
                ],
                [
                    'label' => 'Live Charts',
                    'icon' => 'fas fa-chart-line',
                    'route' => 'live.charts.index',  // CHANGE THIS: was 'live.charts'
                    'active' => ['live.charts.*'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
            ],
        ],
        
       */

        // ============================================
        // FINANCE
        // ============================================
        [
            'label' => 'Finance',
            'icon' => 'ik ik-credit-card',
            'route' => 'finance.balances.index',
            'active' => ['finance.balances.*', 'finance.reconciliations.*', 'finance.reports.*'],
            'permission' => ['director', 'accountant'],
            'children' => [
                [
                    'label' => 'Branch Balances',
                    'icon' => 'ik ik-dollar-sign',
                    'route' => 'finance.balances.index',
                    'active' => ['finance.balances.*'],
                    'permission' => ['director', 'accountant'],
                ],
                [
                    'label' => 'Reconciliation',
                    'icon' => 'ik ik-check-square',
                    'route' => 'finance.reconciliations.index',
                    'active' => ['finance.reconciliations.*'],
                    'permission' => ['director', 'accountant'],
                ],
                [
                    'label' => 'Financial Reports',
                    'icon' => 'ik ik-pie-chart',
                    'route' => 'finance.reports.index',
                    'active' => ['finance.reports.*'],
                    'permission' => ['director', 'accountant'],
                ],
            ],
        ],

        // ============================================
        // TRIAL BALANCE
        // ============================================
        [
            'label' => 'Trial Balance',
            'icon' => 'ik ik-bar-chart-2',
            'route' => 'finance.trial-balance.index',
            'active' => ['finance.trial-balance.*'],
            'permission' => ['director', 'accountant'],
        ],

        // ============================================
        // ASSETS
        // ============================================
        [
            'label' => 'Assets',
            'icon' => 'ik ik-box',
            'route' => 'finance.assets.index',
            'active' => ['finance.assets.*'],
            'permission' => ['director', 'accountant'],
            'children' => [
                [
                    'label' => 'All Assets',
                    'icon' => 'ik ik-list',
                    'route' => 'finance.assets.index',
                    'active' => ['finance.assets.index'],
                    'permission' => ['director', 'accountant'],
                ],
                [
                    'label' => 'Add Asset',
                    'icon' => 'ik ik-plus',
                    'route' => 'finance.assets.create',
                    'active' => ['finance.assets.create', 'finance.assets.edit'],
                    'permission' => ['director', 'accountant'],
                ],
                [
                    'label' => 'Print Report',
                    'icon' => 'ik ik-printer',
                    'route' => 'finance.assets.print',
                    'active' => ['finance.assets.print'],
                    'permission' => ['director', 'accountant'],
                ],
                [
                    'label' => 'Export CSV',
                    'icon' => 'ik ik-download',
                    'route' => 'finance.assets.export',
                    'active' => ['finance.assets.export'],
                    'permission' => ['director', 'accountant'],
                ],
            ],
        ],

        // ============================================
        // COMMUNICATION
        // ============================================
        [
            'label' => 'Communication',
            'icon' => 'ik ik-message-circle',
            'route' => 'communication.sms.index',
            'active' => ['communication.*'],
            'permission' => ['director', 'branch_manager', 'secretary'],
            'children' => [
                [
                    'label' => 'SMS Dashboard',
                    'icon' => 'fas fa-sms',
                    'route' => 'communication.sms.index',
                    'active' => ['communication.sms.index'],
                    'permission' => ['director', 'branch_manager', 'secretary'],
                ],
                [
                    'label' => 'Send SMS',
                    'icon' => 'ik ik-plus',
                    'route' => 'communication.sms.send',
                    'active' => ['communication.sms.send'],
                    'permission' => ['director', 'branch_manager', 'secretary'],
                ],
                [
                    'label' => 'SMS Logs',
                    'icon' => 'ik ik-list',
                    'route' => 'communication.sms.logs',
                    'active' => ['communication.sms.logs'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'SMS Templates',
                    'icon' => 'ik ik-file-text',
                    'route' => 'communication.sms.templates',
                    'active' => ['communication.sms.templates'],
                    'permission' => ['director', 'branch_manager', 'secretary'],
                ],
                [
                    'label' => 'Notifications',
                    'icon' => 'ik ik-bell',
                    'route' => 'communication.notifications.index',
                    'active' => ['communication.notifications.*'],
                    'permission' => ['director', 'branch_manager', 'secretary', 'staff'],
                ],
            ],
        ],

        // ============================================
        // CONTACTS
        // ============================================
        [
            'label' => 'Contacts',
            'icon' => 'fas fa-address-book',
            'route' => 'contacts.index',
            'active' => ['contacts.*', 'suppliers.*', 'customers.*'],
            'permission' => ['director', 'branch_manager', 'secretary', 'staff'],
            'children' => [
                [
                    'label' => 'Suppliers',
                    'icon' => 'ik ik-truck',
                    'route' => 'suppliers.index',
                    'active' => ['suppliers.*'],
                    'permission' => ['director', 'branch_manager', 'secretary', 'staff'],
                ],
                [
                    'label' => 'Customers',
                    'icon' => 'ik ik-users',
                    'route' => 'customers.index',
                    'active' => ['customers.*'],
                    'permission' => ['director', 'branch_manager', 'secretary', 'staff'],
                ],
            ],
        ],

        // ============================================
        // REPORTS
        // ============================================
        [
            'label' => 'Reports(Fixed)',
            'icon' => 'ik ik-bar-chart-2',
            'route' => 'reports.index',
            'active' => ['reports.*'],
            'permission' => ['director', 'branch_manager', 'accountant'],
            'children' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'ik ik-home',
                    'route' => 'reports.index',
                    'active' => ['reports.index'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Sales Report',
                    'icon' => 'ik ik-trending-up',
                    'route' => 'reports.sales',
                    'active' => ['reports.sales'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Purchases Report',
                    'icon' => 'ik ik-shopping-bag',
                    'route' => 'reports.purchases',
                    'active' => ['reports.purchases'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Expenses Report',
                    'icon' => 'ik ik-dollar-sign',
                    'route' => 'reports.expenses',
                    'active' => ['reports.expenses'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'Loans Report',
                    'icon' => 'ik ik-file-text',
                    'route' => 'reports.loans',
                    'active' => ['reports.loans'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
                [
                    'label' => 'General Report',
                    'icon' => 'ik ik-pie-chart',
                    'route' => 'reports.general',
                    'active' => ['reports.general'],
                    'permission' => ['director', 'branch_manager', 'accountant'],
                ],
            ],
        ],

        // ============================================
        // MY BRANCH
        // ============================================
        [
            'label' => 'My Branch',
            'icon' => 'fas fa-store',
            'route' => 'branch.dashboard',
            'active' => ['branch.*'],
            'permission' => ['branch_manager'],
            'children' => [
                [
                    'label' => 'Branch Overview',
                    'icon' => 'ik ik-home',
                    'route' => 'branch.dashboard',
                    'active' => ['branch.dashboard'],
                    'permission' => ['branch_manager'],
                ],
                [
                    'label' => 'Branch Balance',
                    'icon' => 'ik ik-dollar-sign',
                    'route' => 'branch.balance',
                    'active' => ['branch.balance'],
                    'permission' => ['branch_manager'],
                ],
                [
                    'label' => 'Branch Transactions',
                    'icon' => 'ik ik-repeat',
                    'route' => 'branch.transactions',
                    'active' => ['branch.transactions'],
                    'permission' => ['branch_manager'],
                ],
                [
                    'label' => 'Branch Staff',
                    'icon' => 'ik ik-users',
                    'route' => 'branch.staff',
                    'active' => ['branch.staff'],
                    'permission' => ['branch_manager'],
                ],
                [
                    'label' => 'Branch Reports',
                    'icon' => 'ik ik-pie-chart',
                    'route' => 'branch.reports',
                    'active' => ['branch.reports'],
                    'permission' => ['branch_manager'],
                ],
            ],
        ],

        // ============================================
        // LUNA AI
        // ============================================
        [
            'label' => 'LUNA AI(Learning )',
            'icon' => 'fas fa-brain',
            'route' => 'luna.index',
            'active' => ['luna.*'],
            'permission' => ['director', 'branch_manager', 'accountant', 'secretary', 'staff'],
        ],

        // ============================================
        // ✅ HR (Human Resources) - Simplified
        // ============================================
        [
            'label' => 'HR(Coming soon)',
            'icon' => 'ik ik-users',
            'route' => 'hr.employees',
            'active' => ['hr.*'],
            'permission' => ['director', 'hr'],
            'children' => [
                [
                    'label' => 'Employees',
                    'icon' => 'ik ik-users',
                    'route' => 'hr.employees',
                    'active' => ['hr.employees'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Attendance',
                    'icon' => 'ik ik-clock',
                    'route' => 'hr.attendance',
                    'active' => ['hr.attendance'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Leave Management',
                    'icon' => 'ik ik-calendar',
                    'route' => 'hr.leaves',
                    'active' => ['hr.leaves'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Payroll',
                    'icon' => 'ik ik-dollar-sign',
                    'route' => 'hr.payroll',
                    'active' => ['hr.payroll'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Announcements',
                    'icon' => 'ik ik-bell',
                    'route' => 'hr.announcements',
                    'active' => ['hr.announcements'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Reports',
                    'icon' => 'ik ik-pie-chart',
                    'route' => 'hr.reports',
                    'active' => ['hr.reports'],
                    'permission' => ['director', 'hr'],
                ],
            ],
        ],

        // ============================================
        // MANAGEMENT
        // ============================================
        [
            'label' => 'Management',
            'icon' => 'ik ik-settings',
            'route' => 'management.users.index',
            'active' => ['management.*'],
            'permission' => ['director', 'hr'],
            'children' => [
                [
                    'label' => 'Users',
                    'icon' => 'ik ik-users',
                    'route' => 'management.users.index',
                    'active' => ['management.users.*'],
                    'permission' => ['director', 'hr'],
                ],
                [
                    'label' => 'Roles',
                    'icon' => 'ik ik-shield',
                    'route' => 'management.roles.index',
                    'active' => ['management.roles.*'],
                    'permission' => ['director'],
                ],
                [
                    'label' => 'Permissions',
                    'icon' => 'ik ik-lock',
                    'route' => 'management.permissions.index',
                    'active' => ['management.permissions.*'],
                    'permission' => ['director'],
                ],
                [
                    'label' => 'Branches',
                    'icon' => 'fas fa-store',
                    'route' => 'management.branches.index',
                    'active' => ['management.branches.*'],
                    'permission' => ['director'],
                ],
            ],
        ],
    ],
];