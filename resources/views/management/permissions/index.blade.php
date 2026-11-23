@extends('layouts.main')

@section('title', 'Permissions')

@section('content')
<x-page-header title="Permissions" subtitle="Manage system permissions" icon="ik ik-lock"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Permissions' => null]" />

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ $totalPermissions }}" 
                 label="Total Permissions" 
                 icon="ik ik-lock" 
                 color="primary" />
    
    <x-stat-card value="{{ count($permissionGroups) }}" 
                 label="Permission Groups" 
                 icon="ik ik-grid" 
                 color="green" />
    
    <x-stat-card value="{{ $usersByRole->count() }}" 
                 label="User Roles" 
                 icon="ik ik-shield" 
                 color="amber" />
</div>

<!-- Permission Groups -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Permission Matrix</h3>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Module</th>
                    <th class="px-5 py-3 font-medium">Permission</th>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-5 py-3 font-medium">Roles with Access</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($permissionGroups as $groupName => $permissions)
                    @php
                        $first = true;
                    @endphp
                    @foreach($permissions as $key => $description)
                    <tr class="hover:bg-gray-50">
                        @if($first)
                            <td class="px-5 py-3 font-semibold text-gray-800" rowspan="{{ count($permissions) }}">
                                {{ $groupName }}
                            </td>
                            @php $first = false; @endphp
                        @endif
                        <td class="px-5 py-3">
                            <span class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">{{ $key }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $description }}</td>
                        <td class="px-5 py-3">
                            @php
                                $roles = [];
                                if (in_array($key, ['*'])) {
                                    $roles = ['Director'];
                                } else {
                                    // Check which roles have this permission
                                    $rolePermissions = [
                                        'director' => ['*'],
                                        'branch_manager' => [
                                            'dashboard.view', 'dashboard.stats',
                                            'transaction.create', 'transaction.view', 'transaction.edit',
                                            'gold_purchase.create', 'gold_purchase.view', 'gold_purchase.edit', 'gold_purchase.receive',
                                            'gold_sale.create', 'gold_sale.view', 'gold_sale.edit', 'gold_sale.complete',
                                            'loan.create', 'loan.view', 'loan.approve', 'loan.payment',
                                            'expense.create', 'expense.view', 'expense.approve',
                                            'reconciliation.create', 'reconciliation.view',
                                            'supplier.view', 'supplier.create', 'supplier.edit',
                                            'customer.view', 'customer.create', 'customer.edit',
                                            'report.view', 'report.branch',
                                            'profile.view', 'profile.edit', 'profile.password',
                                        ],
                                        'accountant' => [
                                            'dashboard.view',
                                            'transaction.view', 'transaction.verify',
                                            'gold_purchase.view', 'gold_sale.view',
                                            'expense.view', 'expense.create', 'expense.edit',
                                            'reconciliation.create', 'reconciliation.view', 'reconciliation.verify',
                                            'report.view', 'report.financial', 'report.export',
                                            'profile.view', 'profile.edit', 'profile.password',
                                        ],
                                        'secretary' => [
                                            'dashboard.view',
                                            'customer.create', 'customer.view', 'customer.edit',
                                            'supplier.create', 'supplier.view', 'supplier.edit',
                                            'communication.sms', 'communication.email', 'communication.whatsapp',
                                            'communication.template',
                                            'notification.send', 'notification.view',
                                            'profile.view', 'profile.edit', 'profile.password',
                                        ],
                                        'hr' => [
                                            'dashboard.view',
                                            'user.view', 'user.create', 'user.edit',
                                            'profile.view', 'profile.edit', 'profile.password',
                                            'report.view',
                                        ],
                                        'staff' => [
                                            'dashboard.view',
                                            'transaction.create', 'transaction.view',
                                            'customer.view', 'customer.create',
                                            'supplier.view',
                                            'profile.view', 'profile.edit', 'profile.password',
                                            'notification.view',
                                        ],
                                    ];
                                    
                                    foreach ($rolePermissions as $role => $perms) {
                                        if (in_array($key, $perms) || in_array('*', $perms)) {
                                            $roles[] = ucfirst(str_replace('_', ' ', $role));
                                        }
                                    }
                                }
                            @endphp
                            @foreach($roles as $role)
                                <span class="badge badge-{{ $role == 'Director' ? 'primary' : 'gray' }} mr-1">{{ $role }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection