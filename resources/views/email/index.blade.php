@extends('layouts.main')
@section('title', 'Webmail')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Webmail') }}" 
                   subtitle="{{ __('Manage your emails') }}" 
                   icon="ik ik-mail"
                   :breadcrumbs="['Home' => route('dashboard'), 'Webmail' => null]" />

    @if(!isset($connected) || !$connected)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">
            <i class="ik ik-alert-triangle mr-2"></i>
            {{ __('Email settings not configured. Please set up your email account.') }}
            <a href="{{ route('email.settings') }}" class="text-primary-600 hover:underline ml-2">
                {{ __('Configure now') }}
            </a>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <a href="{{ route('email.settings') }}" class="block w-full px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition text-center mb-4">
                    <i class="ik ik-settings mr-2"></i> {{ __('Email Settings') }}
                </a>
                
                <div class="space-y-1">
                    <div class="flex items-center justify-between px-3 py-2 bg-primary-50 rounded-lg cursor-pointer" data-folder="INBOX">
                        <span class="font-medium text-gray-700">{{ __('Inbox') }}</span>
                        <span class="bg-primary-500 text-white text-xs px-2 py-1 rounded-full" id="inbox-count">{{ $unreadCount ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer" data-folder="Sent">
                        <span class="text-gray-600">{{ __('Sent') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer" data-folder="Drafts">
                        <span class="text-gray-600">{{ __('Drafts') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer" data-folder="Trash">
                        <span class="text-gray-600">{{ __('Trash') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer" data-folder="Spam">
                        <span class="text-gray-600">{{ __('Spam') }}</span>
                    </div>
                </div>

                @if(isset($folders) && count($folders) > 0)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Folders') }}</h5>
                        @foreach($folders as $folder)
                            @if($folder['name'] !== 'INBOX')
                            <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer text-sm" data-folder="{{ $folder['name'] }}">
                                <span class="text-gray-600">{{ $folder['name'] }}</span>
                                <span class="text-xs text-gray-400">{{ $folder['unread'] > 0 ? $folder['unread'] : '' }}</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Toolbar -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <button id="refresh-btn" class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="ik ik-refresh-ccw text-gray-500"></i>
                        </button>
                        <button id="select-all-btn" class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="ik ik-check-square text-gray-500"></i>
                        </button>
                        <button id="archive-btn" class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="ik ik-archive text-gray-500"></i>
                        </button>
                        <button id="delete-btn" class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="ik ik-trash-2 text-gray-500"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="compose-btn" class="px-3 py-1.5 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                            <i class="ik ik-plus mr-1"></i> {{ __('Compose') }}
                        </button>
                    </div>
                </div>

                <!-- Email List -->
                <div id="email-list" class="divide-y divide-gray-100">
                    @if(isset($emails) && count($emails) > 0)
                        @foreach($emails as $email)
                            <div class="email-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer {{ $email['is_read'] ? '' : 'bg-primary-50' }}" data-id="{{ $email['id'] }}">
                                <input type="checkbox" class="email-checkbox rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                                <div class="flex items-center gap-3 min-w-0 flex-1" onclick="viewEmail({{ $email['id'] }})">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-semibold text-sm">
                                            {{ strtoupper(substr($email['from_name'], 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-medium text-gray-800 truncate">{{ $email['from_name'] }}</span>
                                            <span class="text-xs text-gray-400 flex-shrink-0">{{ \Carbon\Carbon::parse($email['date'])->diffForHumans() }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-700 truncate">{{ $email['subject'] }}</span>
                                            <span class="text-xs text-gray-400 truncate">{{ $email['body_preview'] ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @if($email['has_attachment'] ?? false)
                                    <i class="ik ik-paperclip text-gray-400 text-sm flex-shrink-0"></i>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div id="empty-state" class="py-20 text-center text-gray-400">
                            <i class="ik ik-inbox text-4xl block mb-4"></i>
                            {{ __('No emails found') }}
                            @if(isset($connected) && !$connected)
                                <p class="text-sm mt-2">{{ __('Connect your email account to see messages.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compose Modal -->
<div id="compose-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">{{ __('Compose Email') }}</h3>
            <button onclick="closeCompose()" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>
        <form id="compose-form">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('To') }}</label>
                    <input type="email" id="compose-to" required 
                           class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Subject') }}</label>
                    <input type="text" id="compose-subject" required 
                           class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Message') }}</label>
                    <textarea id="compose-body" rows="8" required 
                              class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" onclick="closeCompose()" 
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <i class="ik ik-send mr-1"></i> {{ __('Send') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Email Modal -->
<div id="view-email-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 id="view-email-subject" class="text-lg font-semibold text-gray-800">{{ __('Email') }}</h3>
            <button onclick="closeViewEmail()" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-semibold text-sm" id="view-email-avatar">
                    U
                </div>
                <div>
                    <p id="view-email-from" class="text-sm font-medium text-gray-800"></p>
                    <p id="view-email-to" class="text-xs text-gray-500"></p>
                </div>
                <span id="view-email-date" class="text-xs text-gray-400 ml-auto"></span>
            </div>
            <div class="border-t border-gray-100 pt-4">
                <div id="view-email-body" class="prose max-w-none"></div>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="closeViewEmail()" 
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                {{ __('Close') }}
            </button>
        </div>
    </div>
</div>

<script>
// Store current folder
let currentFolder = 'INBOX';
let selectedEmails = [];

// Folder click handlers
document.querySelectorAll('[data-folder]').forEach(el => {
    el.addEventListener('click', function() {
        const folder = this.dataset.folder;
        loadFolder(folder);
    });
});

// Load folder emails
function loadFolder(folder) {
    currentFolder = folder;
    
    // Update active folder styling
    document.querySelectorAll('[data-folder]').forEach(el => {
        el.classList.remove('bg-primary-50');
        el.querySelector('span')?.classList.remove('font-medium', 'text-gray-700');
        el.querySelector('span')?.classList.add('text-gray-600');
    });
    
    // Highlight active folder
    const activeEl = document.querySelector(`[data-folder="${folder}"]`);
    if (activeEl) {
        activeEl.classList.add('bg-primary-50');
        const span = activeEl.querySelector('span:first-child');
        if (span) {
            span.classList.remove('text-gray-600');
            span.classList.add('font-medium', 'text-gray-700');
        }
    }
    
    // Fetch emails for folder
    fetch(`/email/api/folder?folder=${encodeURIComponent(folder)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEmails(data.data);
                // Update unread count
                if (folder === 'INBOX') {
                    document.getElementById('inbox-count').textContent = data.unread;
                }
            }
        })
        .catch(error => {
            console.error('Error loading folder:', error);
        });
}

// Render emails
function renderEmails(emails) {
    const emailList = document.getElementById('email-list');
    const emptyState = document.getElementById('empty-state');
    
    if (!emailList) return;
    
    if (emails.length === 0) {
        emailList.innerHTML = `
            <div id="empty-state" class="py-20 text-center text-gray-400">
                <i class="ik ik-inbox text-4xl block mb-4"></i>
                {{ __('No emails found') }}
            </div>
        `;
        return;
    }
    
    let html = '';
    emails.forEach(email => {
        const isRead = email.is_read ? '' : 'bg-primary-50';
        const fromName = email.from_name || email.from || 'Unknown';
        const initials = fromName.charAt(0).toUpperCase();
        
        html += `
            <div class="email-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer ${isRead}" data-id="${email.id}">
                <input type="checkbox" class="email-checkbox rounded border-gray-300 text-primary-500 focus:ring-primary-500" onchange="toggleEmailSelect(${email.id})">
                <div class="flex items-center gap-3 min-w-0 flex-1" onclick="viewEmail(${email.id})">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-semibold text-sm">
                            ${initials}
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-gray-800 truncate">${fromName}</span>
                            <span class="text-xs text-gray-400 flex-shrink-0">${email.date ? new Date(email.date).toLocaleDateString() : ''}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 truncate">${email.subject}</span>
                            <span class="text-xs text-gray-400 truncate">${email.body_preview || ''}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    emailList.innerHTML = html;
}

// Toggle email selection
function toggleEmailSelect(id) {
    const index = selectedEmails.indexOf(id);
    if (index > -1) {
        selectedEmails.splice(index, 1);
    } else {
        selectedEmails.push(id);
    }
}

// View email
function viewEmail(id) {
    fetch(`/email/api/message?uid=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const email = data.data;
                document.getElementById('view-email-subject').textContent = email.subject;
                document.getElementById('view-email-from').textContent = email.from_name + ' <' + email.from + '>';
                document.getElementById('view-email-to').textContent = 'To: ' + email.to;
                document.getElementById('view-email-date').textContent = new Date(email.date).toLocaleString();
                document.getElementById('view-email-avatar').textContent = email.from_name.charAt(0).toUpperCase();
                document.getElementById('view-email-body').innerHTML = email.body_html || email.body || 'No content';
                document.getElementById('view-email-modal').classList.remove('hidden');
                
                // Update email list to mark as read
                const emailItem = document.querySelector(`.email-item[data-id="${id}"]`);
                if (emailItem) {
                    emailItem.classList.remove('bg-primary-50');
                }
            }
        })
        .catch(error => {
            console.error('Error viewing email:', error);
        });
}

// Close view email
function closeViewEmail() {
    document.getElementById('view-email-modal').classList.add('hidden');
}

// Compose email
document.getElementById('compose-btn')?.addEventListener('click', function() {
    document.getElementById('compose-modal').classList.remove('hidden');
    document.getElementById('compose-form').reset();
});

function closeCompose() {
    document.getElementById('compose-modal').classList.add('hidden');
}

// Send email
document.getElementById('compose-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const to = document.getElementById('compose-to').value;
    const subject = document.getElementById('compose-subject').value;
    const body = document.getElementById('compose-body').value;
    
    fetch('/email/api/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ to, subject, body })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email sent successfully!');
            closeCompose();
            // Refresh current folder
            loadFolder(currentFolder);
        } else {
            alert('Failed to send email: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error sending email: ' + error.message);
    });
});

// Select all emails
document.getElementById('select-all-btn')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.email-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    
    // Update selected emails
    selectedEmails = [];
    document.querySelectorAll('.email-checkbox:checked').forEach(cb => {
        const id = parseInt(cb.closest('.email-item').dataset.id);
        if (id) selectedEmails.push(id);
    });
});

// Delete selected emails
document.getElementById('delete-btn')?.addEventListener('click', function() {
    if (selectedEmails.length === 0) {
        alert('Please select emails to delete.');
        return;
    }
    
    if (!confirm('Are you sure you want to delete ' + selectedEmails.length + ' email(s)?')) {
        return;
    }
    
    // Delete each selected email
    const deletePromises = selectedEmails.map(id => {
        return fetch('/email/api/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ uid: id })
        })
        .then(response => response.json());
    });
    
    Promise.all(deletePromises)
        .then(() => {
            selectedEmails = [];
            loadFolder(currentFolder);
        })
        .catch(error => {
            console.error('Error deleting emails:', error);
            alert('Failed to delete some emails.');
        });
});

// Refresh button
document.getElementById('refresh-btn')?.addEventListener('click', function() {
    loadFolder(currentFolder);
});

// Close modal on outside click
document.getElementById('compose-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCompose();
});

document.getElementById('view-email-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeViewEmail();
});

// Archive button
document.getElementById('archive-btn')?.addEventListener('click', function() {
    if (selectedEmails.length === 0) {
        alert('Please select emails to archive.');
        return;
    }
    
    if (!confirm('Archive ' + selectedEmails.length + ' email(s)?')) {
        return;
    }
    
    const movePromises = selectedEmails.map(id => {
        return fetch('/email/api/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ uid: id, to_folder: 'Archive' })
        })
        .then(response => response.json());
    });
    
    Promise.all(movePromises)
        .then(() => {
            selectedEmails = [];
            loadFolder(currentFolder);
        })
        .catch(error => {
            console.error('Error archiving emails:', error);
            alert('Failed to archive some emails.');
        });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCompose();
        closeViewEmail();
    }
});

// Initialize - load INBOX
document.addEventListener('DOMContentLoaded', function() {
    // Set initial active folder
    const inboxEl = document.querySelector('[data-folder="INBOX"]');
    if (inboxEl) {
        inboxEl.classList.add('bg-primary-50');
        const span = inboxEl.querySelector('span:first-child');
        if (span) {
            span.classList.remove('text-gray-600');
            span.classList.add('font-medium', 'text-gray-700');
        }
    }
});
</script>

@endsection