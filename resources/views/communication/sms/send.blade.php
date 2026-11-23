@extends('layouts.main')

@section('title', 'Send SMS')

@section('content')
<x-page-header title="Send SMS" subtitle="Send SMS messages to recipients" icon="fas fa-sms"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.sms.index'), 'Send' => null]" />

<div class="max-w-5xl mx-auto">
    <x-card>
        <x-slot:header>
            <div class="flex items-center justify-between">
                <span>{{ __('Send SMS') }}</span>
                <span class="text-sm text-gray-500">
                    <i class="ik ik-info-circle mr-1"></i>
                    Credits Available: <strong class="text-primary-600">{{ number_format($credits['balance'] ?? 0) }}</strong>
                </span>
            </div>
        </x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="ik ik-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @php
            $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
            $textareaClass = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
        @endphp

        <form method="POST" action="{{ route('communication.sms.send.post') }}" id="smsForm">
            @csrf

            <div class="space-y-4">
                <!-- Recipient Type Selection -->
                <div>
                    <label for="recipient_type" class="block text-sm font-medium text-gray-700 mb-1">Recipient Type *</label>
                    <select name="recipient_type" id="recipient_type" class="{{ $selectClass }}">
                        <option value="custom">Custom Numbers</option>
                        <option value="all_contacts">All Contacts (Suppliers + Customers)</option>
                        <option value="all_suppliers">All Suppliers</option>
                        <option value="specific_supplier">Specific Supplier</option>
                        <option value="all_customers">All Customers</option>
                        <option value="specific_customer">Specific Customer</option>
                        <option value="all_managers">All Branch Managers</option>
                        <option value="specific_manager">Specific Branch Manager</option>
                        <option value="all_users">All Users</option>
                        <option value="specific_user">Specific User</option>
                    </select>
                </div>

                <!-- Supplier Selection -->
                <div id="supplier_select" style="display: none;">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Select Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="{{ $selectClass }}">
                        <option value="">-- Select a supplier --</option>
                        @foreach($suppliers ?? [] as $supplier)
                            <option value="{{ $supplier->id }}" data-phone="{{ $supplier->contact }}">
                                {{ $supplier->name }} - {{ $supplier->contact }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Selection -->
                <div id="customer_select" style="display: none;">
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Select Customer</label>
                    <select name="customer_id" id="customer_id" class="{{ $selectClass }}">
                        <option value="">-- Select a customer --</option>
                        @foreach($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" data-phone="{{ $customer->contact }}">
                                {{ $customer->name }} - {{ $customer->contact }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Manager Selection -->
                <div id="manager_select" style="display: none;">
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Select Branch Manager</label>
                    <select name="manager_id" id="manager_id" class="{{ $selectClass }}">
                        <option value="">-- Select a branch manager --</option>
                        @foreach($managers ?? [] as $manager)
                            <option value="{{ $manager->id }}" data-phone="{{ $manager->phone_number }}">
                                {{ $manager->full_name ?? $manager->username }} - {{ $manager->phone_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Selection -->
                <div id="user_select" style="display: none;">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Select User</label>
                    <select name="user_id" id="user_id" class="{{ $selectClass }}">
                        <option value="">-- Select a user --</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}" data-phone="{{ $user->phone_number }}">
                                {{ $user->full_name ?? $user->username }} - {{ $user->phone_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Recipients Preview -->
                <div id="recipients_preview" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selected Recipients</label>
                    <div id="recipients_list" class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg min-h-[40px]">
                        <span class="text-sm text-gray-500">No recipients selected</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400" id="recipient_count">Total: 0 recipients</p>
                </div>

                <!-- Custom Recipients -->
                <div id="custom_recipients">
                    <label for="recipients" class="block text-sm font-medium text-gray-700 mb-1">Custom Recipients</label>
                    <textarea name="recipients" id="recipients" rows="3" class="{{ $textareaClass }}"
                              placeholder="Enter phone numbers (comma, newline, or semicolon separated)">{{ old('recipients') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Format: 2557XXXXXXXX or 07XXXXXXXX</p>
                </div>

                <!-- Sender ID -->
                <div>
                    <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-1">Sender ID</label>
                    <input type="text" name="sender_id" id="sender_id" 
                           value="{{ old('sender_id', 'NGEMBA MINE') }}" 
                           maxlength="11"
                           class="{{ $selectClass }}">
                    <p class="mt-1 text-xs text-gray-400">Max 11 characters (letters/numbers)</p>
                </div>

                <!-- Templates -->
                @if(isset($templates) && $templates->count() > 0)
                <div>
                    <label for="template" class="block text-sm font-medium text-gray-700 mb-1">Templates</label>
                    <select name="template" id="template" class="{{ $selectClass }}">
                        <option value="">-- Select a template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" data-message="{{ $template->message }}">
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Message -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                    <textarea name="message" id="message" rows="5" class="{{ $textareaClass }}"
                              placeholder="Type your message here...">{{ old('message') }}</textarea>
                    <div class="mt-1 flex justify-between text-xs text-gray-400">
                        <span id="charCount">0 characters</span>
                        <span id="smsCount">0 SMS</span>
                    </div>
                </div>

                <!-- Preview Button -->
                <div>
                    <button type="button" onclick="previewRecipients()" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                        <i class="ik ik-eye"></i> Preview Recipients
                    </button>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('communication.sms.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" id="sendSmsBtn" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="fas fa-sms"></i> Send SMS
                </button>
            </div>
        </form>
    </x-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. RECIPIENT TYPE CHANGE HANDLER
    // ============================================
    const recipientType = document.getElementById('recipient_type');
    const supplierSelect = document.getElementById('supplier_select');
    const customerSelect = document.getElementById('customer_select');
    const managerSelect = document.getElementById('manager_select');
    const userSelect = document.getElementById('user_select');
    const customRecipients = document.getElementById('custom_recipients');
    const recipientsPreview = document.getElementById('recipients_preview');

    if (recipientType) {
        recipientType.addEventListener('change', function() {
            const type = this.value;
            
            // Hide all
            if (supplierSelect) supplierSelect.style.display = 'none';
            if (customerSelect) customerSelect.style.display = 'none';
            if (managerSelect) managerSelect.style.display = 'none';
            if (userSelect) userSelect.style.display = 'none';
            if (customRecipients) customRecipients.style.display = 'none';
            if (recipientsPreview) recipientsPreview.style.display = 'none';
            
            // Show relevant
            if (type === 'specific_supplier') {
                if (supplierSelect) supplierSelect.style.display = 'block';
            } else if (type === 'specific_customer') {
                if (customerSelect) customerSelect.style.display = 'block';
            } else if (type === 'specific_manager') {
                if (managerSelect) managerSelect.style.display = 'block';
            } else if (type === 'specific_user') {
                if (userSelect) userSelect.style.display = 'block';
            } else if (type === 'custom') {
                if (customRecipients) customRecipients.style.display = 'block';
            } else {
                if (recipientsPreview) {
                    recipientsPreview.style.display = 'block';
                    previewRecipients();
                }
            }
        });
        // Trigger initial state
        recipientType.dispatchEvent(new Event('change'));
    }

    // ============================================
    // 2. TEMPLATE SELECTION - POPULATE MESSAGE
    // ============================================
    const templateSelect = document.getElementById('template');
    const messageInput = document.getElementById('message');

    if (templateSelect && messageInput) {
        templateSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value && selected.dataset.message) {
                messageInput.value = selected.dataset.message;
                updateCharCount();
            }
        });
    }

    // ============================================
    // 3. SPECIFIC SELECTIONS - UPDATE TEXTAREA
    // ============================================
    const recipientsTextarea = document.getElementById('recipients');

    document.getElementById('supplier_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            const phone = this.options[this.selectedIndex].dataset.phone;
            recipientsTextarea.value = phone;
        }
    });

    document.getElementById('customer_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            const phone = this.options[this.selectedIndex].dataset.phone;
            recipientsTextarea.value = phone;
        }
    });

    document.getElementById('manager_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            const phone = this.options[this.selectedIndex].dataset.phone;
            recipientsTextarea.value = phone;
        }
    });

    document.getElementById('user_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            const phone = this.options[this.selectedIndex].dataset.phone;
            recipientsTextarea.value = phone;
        }
    });

    // ============================================
    // 4. CHARACTER COUNT
    // ============================================
    function updateCharCount() {
        const text = messageInput?.value || '';
        const charCount = document.getElementById('charCount');
        const smsCount = document.getElementById('smsCount');
        
        if (charCount) charCount.textContent = text.length + ' characters';
        if (smsCount) {
            const smsParts = Math.ceil(text.length / 160);
            smsCount.textContent = smsParts + ' SMS';
        }
    }

    if (messageInput) {
        messageInput.addEventListener('input', updateCharCount);
        messageInput.addEventListener('keyup', updateCharCount);
    }
    updateCharCount();
});

// ============================================
// 5. PREVIEW RECIPIENTS
// ============================================
function previewRecipients() {
    const type = document.getElementById('recipient_type').value;
    const container = document.getElementById('recipients_list');
    const countEl = document.getElementById('recipient_count');
    const recipientsTextarea = document.getElementById('recipients');
    
    if (!container) return;

    const allSuppliers = @json($suppliers ?? []);
    const allCustomers = @json($customers ?? []);
    const allManagers = @json($managers ?? []);
    const allUsers = @json($users ?? []);
    
    let recipients = [];
    let html = '';
    
    switch(type) {
        case 'all_contacts':
            recipients = [...allSuppliers, ...allCustomers];
            break;
        case 'all_suppliers':
            recipients = allSuppliers;
            break;
        case 'all_customers':
            recipients = allCustomers;
            break;
        case 'all_managers':
            recipients = allManagers;
            break;
        case 'all_users':
            recipients = allUsers;
            break;
        default:
            container.innerHTML = '<span class="text-sm text-gray-500">No recipients selected</span>';
            countEl.textContent = 'Total: 0 recipients';
            return;
    }
    
    if (recipients.length === 0) {
        container.innerHTML = '<span class="text-sm text-gray-500">No recipients found</span>';
        countEl.textContent = 'Total: 0 recipients';
        return;
    }
    
    recipients.forEach(r => {
        const phone = r.contact || r.phone_number;
        if (phone) {
            html += `<span class="inline-flex items-center gap-1 px-2 py-1 bg-primary-100 text-primary-700 rounded-full text-xs">${r.name} - ${phone}</span>`;
        }
    });
    
    if (html) {
        container.innerHTML = html;
        countEl.textContent = 'Total: ' + recipients.length + ' recipients';
        document.getElementById('recipients_preview').style.display = 'block';
        
        const phoneNumbers = recipients.map(r => r.contact || r.phone_number).filter(p => p);
        if (phoneNumbers.length > 0 && recipientsTextarea) {
            recipientsTextarea.value = phoneNumbers.join(', ');
        }
    } else {
        container.innerHTML = '<span class="text-sm text-gray-500">No phone numbers found</span>';
        countEl.textContent = 'Total: 0 recipients';
    }
}

// ============================================
// 6. FORM VALIDATION
// ============================================
document.getElementById('smsForm')?.addEventListener('submit', function(e) {
    const message = document.getElementById('message').value.trim();
    if (!message) {
        e.preventDefault();
        alert('Please enter a message.');
        return false;
    }
    
    const recipientType = document.getElementById('recipient_type').value;
    const customRecipients = document.getElementById('recipients').value.trim();
    
    if (recipientType === 'custom' && !customRecipients) {
        e.preventDefault();
        alert('Please enter at least one recipient phone number.');
        return false;
    }
});
</script>

@endsection