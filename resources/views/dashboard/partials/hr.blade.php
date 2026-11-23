<!-- HR Stats -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $totalStaff }}" 
                 label="{{ __('Total Staff') }}" 
                 icon="ik ik-users" 
                 color="primary" />
    
    <x-stat-card value="{{ $branchManagers }}" 
                 label="{{ __('Branch Managers') }}" 
                 icon="ik ik-user-check" 
                 color="green" />
    
    <x-stat-card value="{{ $accountants }}" 
                 label="{{ __('Accountants') }}" 
                 icon="ik ik-dollar-sign" 
                 color="blue" />
    
    <x-stat-card value="{{ $regularStaff }}" 
                 label="{{ __('Regular Staff') }}" 
                 icon="ik ik-user" 
                 color="accent" />
</div>

<!-- Staff Details -->
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-card hover>
        <x-slot:header>{{ __('Staff by Branch') }}</x-slot:header>
        <div class="space-y-4">
            @forelse($staffByBranch as $branchId => $count)
                @php
                    $branch = \App\Models\Branch::find($branchId);
                @endphp
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0">
                    <span class="text-sm font-medium text-gray-700">{{ $branch->name ?? 'Unassigned' }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ $count }} staff</span>
                </div>
            @empty
                <p class="text-center text-gray-400 py-4">No staff assigned</p>
            @endforelse
        </div>
    </x-card>

    <x-card hover>
        <x-slot:header>{{ __('Recent Hires') }}</x-slot:header>
        <div class="flex items-center justify-center flex-col py-8">
            <div class="text-5xl font-bold text-primary-600">{{ $newHires }}</div>
            <p class="text-sm text-gray-500 mt-2">New staff members in the last 30 days</p>
            <a href="{{ route('management.users.create') }}" 
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 transition">
                <i class="ik ik-user-plus"></i> Add New Staff
            </a>
        </div>
    </x-card>
</div>