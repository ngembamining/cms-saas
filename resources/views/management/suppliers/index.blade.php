@extends('layouts.main')

@section('title', 'Suppliers')

@section('content')
<x-page-header title="Suppliers" subtitle="Manage all suppliers" icon="ik ik-truck"
    :breadcrumbs="['Home' => url('dashboard'), 'Suppliers' => null]">
    <div class="flex gap-2">
        <button onclick="showImportModal()" class="btn btn-success">
            <i class="ik ik-upload"></i> Import
        </button>
        <a href="{{ route('suppliers.export') }}" class="btn btn-secondary">
            <i class="ik ik-download"></i> Export
        </a>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Supplier
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ $suppliers->total() }}" 
                 label="Total Suppliers" 
                 icon="ik ik-truck" 
                 color="primary" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterSuppliers()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Suppliers
        </h4>
        <div class="flex gap-2">
            <button @click="resetFilters()" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="ik ik-rotate-ccw"></i> Reset
            </button>
            <button @click="applyFilters()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="ik ik-search"></i> Apply Filters
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" x-model="filters.search" placeholder="Name, contact or email..." 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select x-model="filters.branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Contact</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $supplier->id }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $supplier->name }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $supplier->contact }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $supplier->email ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $supplier->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('suppliers.show', $supplier->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('suppliers.edit', $supplier->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('suppliers.destroy', $supplier->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this supplier?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-truck text-3xl block mb-2"></i>
                        No suppliers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>
</div>

<!-- ============================================
     IMPORT MODAL
     ============================================ -->
<div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="ik ik-upload text-primary-500 mr-2"></i> Import Suppliers
            </h3>
            <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-blue-700">
                <i class="ik ik-info mr-2"></i>
                Upload a CSV file with supplier data.
            </p>
            <div class="mt-2 text-sm text-blue-700">
                <strong>Required columns:</strong> <code>name</code>, <code>contact</code>
                <br>
                <strong>Optional columns:</strong> <code>email</code>, <code>address</code>, <code>branch_id</code>
                <br>
                <strong>Accepted alternatives:</strong> 
                <code>name</code> (or supplier_name, full_name), 
                <code>contact</code> (or phone, phone_number, mobile)
            </div>
            <div class="flex gap-3 mt-3">
                <a href="{{ route('suppliers.import.template') }}" class="text-sm text-blue-600 hover:text-blue-800 inline-block">
                    <i class="ik ik-download mr-1"></i> Download Template
                </a>
                <span class="text-sm text-gray-400">|</span>
                <a href="{{ route('suppliers.import.sample') }}" class="text-sm text-blue-600 hover:text-blue-800 inline-block">
                    <i class="ik ik-file-text mr-1"></i> Sample File
                </a>
            </div>
        </div>

        <form id="importForm" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Choose CSV File *</label>
                    <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls,.txt"
                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <p class="mt-1 text-xs text-gray-400">Supported formats: .csv, .xlsx, .xls (Max 5MB)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Import Options</label>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="radio" name="duplicate_action" value="skip" checked
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                            Skip Duplicates
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="radio" name="duplicate_action" value="update"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                            Update Existing
                        </label>
                    </div>
                </div>

                <div id="importProgress" style="display: none;">
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                        <span>Importing...</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progressBar" class="bg-primary-500 rounded-full h-2 transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <div id="importResult" style="display: none;"></div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <button type="button" onclick="closeImportModal()" 
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button type="submit" id="importBtn"
                        class="px-6 py-2 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition disabled:opacity-50">
                    <i class="ik ik-upload mr-2"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ============================================
// FILTERS
// ============================================
function filterSuppliers() {
    return {
        filters: {
            search: '',
            branch_id: ''
        },
        
        resetFilters() {
            this.filters = {
                search: '',
                branch_id: ''
            };
            this.applyFilters();
        },
        
        applyFilters() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });
            
            const url = window.location.pathname + '?' + params.toString();
            window.location.href = url;
        }
    }
}

// ============================================
// IMPORT MODAL
// ============================================
function showImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
    document.getElementById('importForm').reset();
    document.getElementById('importProgress').style.display = 'none';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importBtn').disabled = false;
    document.getElementById('importBtn').innerHTML = '<i class="ik ik-upload mr-2"></i> Import';
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

document.getElementById('importModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportModal();
    }
});

// ============================================
// IMPORT FORM SUBMISSION
// ============================================
document.getElementById('importForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const fileInput = document.getElementById('file');
    const file = fileInput.files[0];

    if (!file) {
        alert('Please select a file to import.');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB.');
        return;
    }

    const validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'];
    const validExtensions = ['csv', 'xlsx', 'xls', 'txt'];
    const fileExt = file.name.split('.').pop().toLowerCase();

    if (!validTypes.includes(file.type) && !validExtensions.includes(fileExt)) {
        alert('Please upload a CSV, XLSX, or XLS file.');
        return;
    }

    const formData = new FormData(this);
    const importBtn = document.getElementById('importBtn');
    const progressDiv = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const resultDiv = document.getElementById('importResult');

    importBtn.disabled = true;
    importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';
    progressDiv.style.display = 'block';
    resultDiv.style.display = 'none';
    progressBar.style.width = '0%';
    progressText.textContent = '0%';

    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15 + 5;
        if (progress >= 90) {
            clearInterval(interval);
            progress = 90;
        }
        progressBar.style.width = Math.min(progress, 95) + '%';
        progressText.textContent = Math.min(progress, 95) + '%';
    }, 300);

    fetch('{{ route("suppliers.import") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(interval);
        progressBar.style.width = '100%';
        progressText.textContent = '100%';

        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="ik ik-upload mr-2"></i> Import';

        if (data.success) {
            resultDiv.className = 'mt-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700';
            
            let html = `
                <div class="flex items-center gap-2">
                    <i class="ik ik-check-circle text-xl"></i>
                    <span class="font-medium">Import Completed!</span>
                </div>
                <div class="mt-2 text-sm grid grid-cols-2 gap-2">
                    <div>✅ Imported: ${data.imported || 0}</div>
                    <div>⏭️ Skipped: ${data.skipped || 0}</div>
                    <div>❌ Failed: ${data.failed || 0}</div>
                    <div>📊 Total: ${data.total || 0}</div>
                </div>
            `;
            
            if (data.errors && Array.isArray(data.errors) && data.errors.length > 0) {
                html += `<div class="mt-2 text-sm text-red-600">${data.errors.join('<br>')}</div>`;
            }
            
            html += `
                <div class="mt-3">
                    <button onclick="closeImportModal(); location.reload();" 
                            class="px-4 py-1.5 bg-primary-500 text-white rounded-lg hover:bg-primary-600 text-sm">
                        Refresh List
                    </button>
                </div>
            `;
            
            resultDiv.innerHTML = html;
            resultDiv.style.display = 'block';
        } else {
            resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700';
            
            let html = `
                <div class="flex items-center gap-2">
                    <i class="ik ik-alert-circle text-xl"></i>
                    <span class="font-medium">Import Failed</span>
                </div>
                <div class="mt-2 text-sm">${data.message || 'An error occurred during import.'}</div>
            `;
            
            if (data.errors && Array.isArray(data.errors) && data.errors.length > 0) {
                html += `<div class="mt-2 text-sm text-red-600">${data.errors.join('<br>')}</div>`;
            }
            
            resultDiv.innerHTML = html;
            resultDiv.style.display = 'block';
            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="ik ik-upload mr-2"></i> Import';
        }
    })
    .catch(error => {
        clearInterval(interval);
        progressBar.style.width = '100%';
        progressText.textContent = 'Error';

        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="ik ik-upload mr-2"></i> Import';

        resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700';
        resultDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="ik ik-alert-circle text-xl"></i>
                <span class="font-medium">Import Failed</span>
            </div>
            <div class="mt-2 text-sm">${error.message || 'An unexpected error occurred. Please try again.'}</div>
        `;
        resultDiv.style.display = 'block';
    });
});
</script>

<style>
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
}
.btn-primary {
    background-color: #4F46E5;
    color: white;
}
.btn-primary:hover {
    background-color: #4338CA;
}
.btn-success {
    background-color: #10B981;
    color: white;
}
.btn-success:hover {
    background-color: #059669;
}
.btn-secondary {
    background-color: #6B7280;
    color: white;
}
.btn-secondary:hover {
    background-color: #4B5563;
}
</style>

@endsection