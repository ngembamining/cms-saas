@extends('layouts.main')

@section('title', 'SMS Templates')

@section('content')
<x-page-header title="SMS Templates" subtitle="Manage SMS message templates" icon="ik ik-file-text"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.sms.index'), 'Templates' => null]">
    <button onclick="document.getElementById('createTemplateForm').classList.toggle('hidden')" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Template
    </button>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ $templates->count() }}" 
                 label="Total Templates" 
                 icon="ik ik-file-text" 
                 color="primary" />
    
    <x-stat-card value="{{ $templates->where('branch_id', 0)->count() }}" 
                 label="Global Templates" 
                 icon="ik ik-globe" 
                 color="green" />
    
    <x-stat-card value="{{ $templates->where('branch_id', '>', 0)->count() }}" 
                 label="Branch Templates" 
                 icon="fas fa-store" 
                 color="amber" />
</div>

<!-- Create Template Form -->
<div id="createTemplateForm" class="hidden mb-6">
    <x-card>
        <x-slot:header>{{ __('Create New Template') }}</x-slot:header>
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif
        <form method="POST" action="{{ route('communication.sms.templates.store') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-form.input name="name" label="Template Name" placeholder="Enter template name" :required="true" />
                </div>
                <div>
                    <x-form.textarea name="message" label="Message" rows="4" placeholder="Enter template message" :required="true" />
                    <p class="mt-1 text-xs text-gray-400" id="templateCharCount">0 characters</p>
                </div>
                <div>
                    <x-form.textarea name="description" label="Description" rows="2" placeholder="Enter description (optional)" />
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('createTemplateForm').classList.add('hidden')" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                    Create Template
                </button>
            </div>
        </form>
    </x-card>
</div>

<!-- Templates List -->
<div class="bg-white rounded-xl shadow-sm p-6">
    @forelse($templates as $template)
    <div class="border-b border-gray-100 py-4 last:border-0 hover:bg-gray-50 -mx-6 px-6 transition">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-gray-800">{{ $template->name }}</h4>
                    @if($template->branch_id == 0)
                        <span class="badge badge-success text-xs">Global</span>
                    @else
                        <span class="badge badge-amber text-xs">Branch</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($template->message, 150) }}</p>
                @if($template->description)
                    <p class="text-xs text-gray-400 mt-1">{{ $template->description }}</p>
                @endif
                <div class="mt-2 flex items-center gap-4 text-xs text-gray-400">
                    <span><i class="ik ik-message-circle mr-1"></i>{{ Str::wordCount($template->message) }} words</span>
                    <span><i class="ik ik-clock mr-1"></i>{{ $template->created_at ? \Carbon\Carbon::parse($template->created_at)->format('d/m/Y') : 'N/A' }}</span>
                </div>
            </div>
            <div class="flex gap-2 ml-4">
                <button onclick="editTemplate({{ $template->id }}, '{{ addslashes($template->name) }}', '{{ addslashes($template->message) }}', '{{ addslashes($template->description) }}')" 
                        class="text-green-500 hover:text-green-600 p-1">
                    <i class="ik ik-edit"></i>
                </button>
                <button onclick="useTemplate('{{ addslashes($template->message) }}')" 
                        class="text-blue-500 hover:text-blue-600 p-1" title="Use this template">
                    <i class="ik ik-copy"></i>
                </button>
                <form action="{{ route('communication.sms.templates.delete', $template->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-600 p-1" onclick="return confirm('Delete this template?')">
                        <i class="ik ik-trash-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-12 text-gray-400">
        <i class="ik ik-file-text text-5xl block mb-4 text-gray-300"></i>
        <p class="text-lg font-medium text-gray-600">No templates created yet</p>
        <p class="text-sm mt-1">Create your first SMS template to save time when sending messages.</p>
        <button onclick="document.getElementById('createTemplateForm').classList.toggle('hidden')" class="mt-4 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            <i class="ik ik-plus"></i> Create Template
        </button>
    </div>
    @endforelse
</div>

<!-- Edit Template Modal -->
<div id="editTemplateModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Edit Template</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>
        <form id="editTemplateForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                    <input type="text" id="edit_name" name="name" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea id="edit_message" name="message" rows="4" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="edit_description" name="description" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                    Update Template
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Edit template
function editTemplate(id, name, message, description) {
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_message').value = message;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editTemplateForm').action = '/communication/sms/templates/' + id;
    document.getElementById('editTemplateModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editTemplateModal').style.display = 'none';
}

// Use template - copy to message field
function useTemplate(message) {
    // Check if we're on the send page
    const messageInput = document.getElementById('message');
    if (messageInput) {
        messageInput.value = message;
        // Trigger the input event to update character count
        const event = new Event('input');
        messageInput.dispatchEvent(event);
        alert('Template copied to message field!');
    } else {
        // If not on send page, copy to clipboard
        navigator.clipboard.writeText(message).then(() => {
            alert('Template copied to clipboard!');
        }).catch(() => {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = message;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Template copied to clipboard!');
        });
    }
}

// Close modal when clicking outside
document.getElementById('editTemplateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Character count for template message
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.querySelector('#createTemplateForm textarea[name="message"]');
    if (messageInput) {
        const charCount = document.getElementById('templateCharCount');
        messageInput.addEventListener('input', function() {
            charCount.textContent = this.value.length + ' characters';
        });
    }
});
</script>
@endsection