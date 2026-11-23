@extends('layouts.main')

@section('title', 'Expense Categories')

@section('content')
<x-page-header title="Expense Categories" subtitle="Manage expense categories" icon="ik ik-grid"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Categories' => null]">
    <a href="{{ route('finance.expenses.categories.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Category
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ $categories->count() }}" 
                 label="Total Categories" 
                 icon="ik ik-grid" 
                 color="primary" />
    
    <x-stat-card value="{{ $categories->where('is_active', true)->count() }}" 
                 label="Active" 
                 icon="ik ik-check-circle" 
                 color="green" />
    
    <x-stat-card value="{{ $categories->where('is_active', false)->count() }}" 
                 label="Inactive" 
                 icon="ik ik-x-circle" 
                 color="danger" />
</div>

<!-- Categories Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Expenses Count</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $category->id }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $category->name }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $category->description ?? 'N/A' }}</td>
                    <td class="px-5 py-3">
                        @if($category->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-gray">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $category->expenses()->count() }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('finance.expenses.categories.edit', $category->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <a href="{{ route('finance.expenses.categories.toggle', $category->id) }}" 
                           class="text-amber-500 hover:text-amber-600 mr-2">
                            <i class="ik {{ $category->is_active ? 'ik-pause-circle' : 'ik-play-circle' }}"></i>
                        </a>
                        <form action="{{ route('finance.expenses.categories.destroy', $category->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this category?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-grid text-3xl block mb-2"></i>
                        No expense categories found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection