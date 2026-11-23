@extends('layouts.main')

@section('title', 'Send WhatsApp')

@section('content')
<x-page-header title="Send WhatsApp" subtitle="Send WhatsApp messages to recipients" icon="fab fa-whatsapp"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.whatsapp.index'), 'Send' => null]" />

<div class="max-w-5xl mx-auto">
    <x-card>
        <x-slot:header>
            <div class="flex items-center justify-between">
                <span>{{ __('Send WhatsApp') }}</span>
                <span class="text-sm text-gray-500">
                    <i class="ik ik-info-circle mr-1"></i>
                    API Status: 
                    <strong class="{{ ($apiStatus ?? 'ok') === 'ok' ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($apiStatus ?? 'ok') === 'ok' ? 'Connected' : 'Disconnected' }}
                    </strong>
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

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
            $textareaClass = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
        @endphp

        <form method="POST" action="{{ route('communication.whatsapp.send.post') }}" id="whatsappForm">
            @csrf

            <div class="space-y-4">
                <!-- Message Type -->
                <div>
                    <label for="message_type" class="block text-sm font-medium text-gray-700 mb-1">Message Type *</label>
                    <select name="message_type" id="message_type" class="{{ $selectClass }}">
                        <option value="text" {{ old('message_type') == 'text' ? 'selected' : '' }}>
                            Text (only within 24h window)
                        </option>
                        <option value="template" {{ old('message_type') == 'template' ? 'selected' : '' }}>
                            Template (works anytime)
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400" id="message_type_hint">
                        Text messages only work if the recipient messaged you within the last 24 hours.
                    </p>
                </div>

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
                    <label for="recipients" class="block text-sm font-medium text-gray-700 mb-1">Custom Recipients *</label>
                    <textarea name="recipients" id="recipients" rows="3" class="{{ $textareaClass }}"
                              placeholder="Enter phone numbers (comma, newline, or semicolon separated)">{{ old('recipients') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Format: 2557XXXXXXXX (with country code, no + or spaces)</p>
                </div>

                <!-- ============================================ -->
                <!-- TEMPLATE OPTIONS (shown only for templates)  -->
                <!-- ============================================ -->
                <div id="template_options" style="display: none;">
                    <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-700">
                            <i class="ik ik-layers mr-1"></i> Template Configuration
                        </h4>

                        <!-- Template Selector -->
                        <div>
                            <label for="template_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Template Name *
                            </label>
                            @if(isset($whatsappTemplates) && count($whatsappTemplates) > 0)
                                <select name="template_name" id="template_name" class="{{ $selectClass }}">
                                    <option value="">-- Select a template --</option>
                                    @foreach($whatsappTemplates as $tpl)
                                        <option value="{{ $tpl['name'] }}" 
                                                data-language="{{ $tpl['language'] ?? 'en_US' }}"
                                                data-body="{{ $tpl['body'] ?? '' }}">
                                            {{ $tpl['name'] }} ({{ $tpl['language'] ?? 'en_US' }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="template_name" id="template_name" 
                                       value="{{ old('template_name', 'hello_world') }}"
                                       class="{{ $selectClass }}"
                                       placeholder="e.g. hello_world">
                            @endif
                            <p class="mt-1 text-xs text-gray-400">
                                Templates must be pre-approved in Meta Business Suite.
                            </p>
                        </div>

                        <!-- Language Code -->
                        <div>
                            <label for="language_code" class="block text-sm font-medium text-gray-700 mb-1">Language Code</label>
                            <input type="text" name="language_code" id="language_code" 
                                   value="{{ old('language_code', 'en_US') }}"
                                   class="{{ $selectClass }}"
                                   placeholder="en_US">
                        </div>

                        <!-- Template Body Preview -->
                        <div id="template_preview" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Template Body Preview</label>
                            <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-700 whitespace-pre-wrap"
                                 id="template_preview_body"></div>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- TEXT MESSAGE (shown only for text)           -->
                <!-- ============================================ -->
                <div id="text_message_section">
                    <!-- Templates (existing SMS-style templates - optional) -->
                    @if(isset($templates) && $templates->count() > 0)
                    <div>
                        <label for="template" class="block text-sm font-medium text-gray-700 mb-1">Quick Templates</label>
                        <select name="template" id="template" class="{{ $selectClass }}">
                            <option value="">-- Select a quick template --</option>
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
                            <span id="whatsappHint">WhatsApp text limit: 4096 characters</span>
                        </div>
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
                <a href="{{ route('communication.whatsapp.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" id="sendWhatsappBtn" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="fab fa-whatsapp"></i> Send WhatsApp
                </button>
            </div>
        </form>
    </x-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 0. MESSAGE TYPE TOGGLE (text vs template)
    // ============================================
    const messageType = document.getElementById('message_type');
    const templateOptions = document.getElementById('template_options');
    const textMessageSection = document.getElementById('text_message_section');
    const messageTypeHint = document.getElementById('message_type_hint');
    const messageInput = document.getElementById('message');

    function toggleMessageType() {
        const type = messageType?.value;
        if (type === 'template') {
            if (templateOptions) templateOptions.style.display = 'block';
            if (textMessageSection) textMessageSection.style.display = 'none';
            if (messageTypeHint) {
                messageTypeHint.textContent = 'Template messages work anytime, even outside the 24-hour window.';
            }
        } else {
            if (templateOptions) templateOptions.style.display = 'none';
            if (textMessageSection) textMessageSection.style.display = 'block';
            if (messageTypeHint) {
                messageTypeHint.textContent = 'Text messages only work if the recipient messaged you within the last 24 hours.';
            }
        }
    }

    if (messageType) {
        messageType.addEventListener('change', toggleMessageType);
        toggleMessageType();
    }

    // ============================================
    // 0b. TEMPLATE SELECT → PREVIEW
    // ============================================
    const templateNameSelect = document.getElementById('template_name');
    const languageInput = document.getElementById('language_code');
    const templatePreview = document.getElementById('template_preview');
    const templatePreviewBody = document.getElementById('template_preview_body');

    templateNameSelect?.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            if (languageInput && opt.dataset.language) {
                languageInput.value = opt.dataset.language;
            }
            if (opt.dataset.body && templatePreview && templatePreviewBody) {
                templatePreviewBody.textContent = opt.dataset.body;
                templatePreview.style.display = 'block';
            } else if (templatePreview) {
                templatePreview.style.display = 'none';
            }
        } else if (templatePreview) {
            templatePreview.style.display = 'none';
        }
    });

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
            
            if (supplierSelect) supplierSelect.style.display = 'none';
            if (customerSelect) customerSelect.style.display = 'none';
            if (managerSelect) managerSelect.style.display = 'none';
            if (userSelect) userSelect.style.display = 'none';
            if (customRecipients) customRecipients.style.display = 'none';
            if (recipientsPreview) recipientsPreview.style.display = 'none';
            
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
        recipientType.dispatchEvent(new Event('change'));
    }

    // ============================================
    // 2. QUICK TEMPLATE SELECTION - POPULATE MESSAGE
    // ============================================
    const templateSelect = document.getElementById('template');

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
            recipientsTextarea.value = this.options[this.selectedIndex].dataset.phone;
        }
    });

    document.getElementById('customer_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            recipientsTextarea.value = this.options[this.selectedIndex].dataset.phone;
        }
    });

    document.getElementById('manager_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            recipientsTextarea.value = this.options[this.selectedIndex].dataset.phone;
        }
    });

    document.getElementById('user_id')?.addEventListener('change', function() {
        if (this.value && recipientsTextarea) {
            recipientsTextarea.value = this.options[this.selectedIndex].dataset.phone;
        }
    });

    // ============================================
    // 4. CHARACTER COUNT
    // ============================================
    function updateCharCount() {
        const text = messageInput?.value || '';
        const charCount = document.getElementById('charCount');
        
        if (charCount) charCount.textContent = text.length + ' characters';
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
document.getElementById('whatsappForm')?.addEventListener('submit', function(e) {
    const messageType = document.getElementById('message_type').value;
    const recipientType = document.getElementById('recipient_type').value;
    const customRecipients = document.getElementById('recipients').value.trim();
    
    // Validate recipients
    if (recipientType === 'custom' && !customRecipients) {
        e.preventDefault();
        alert('Please enter at least one recipient phone number.');
        return false;
    }
    
    // Validate based on message type
    if (messageType === 'template') {
        const templateName = document.getElementById('template_name').value.trim();
        if (!templateName) {
            e.preventDefault();
            alert('Please select or enter a template name.');
            return false;
        }
    } else {
        const message = document.getElementById('message').value.trim();
        if (!message) {
            e.preventDefault();
            alert('Please enter a message.');
            return false;
        }
    }
});
</script>

@endsection