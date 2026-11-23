<!-- Secretary Stats -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $smsSent }}" 
                 label="{{ __('SMS Sent') }}" 
                 icon="fas fa-sms" 
                 color="blue"
                 subtitle="Last 30 days" />
    
    <x-stat-card value="{{ $whatsappSent }}" 
                 label="{{ __('WhatsApp Sent') }}" 
                 icon="fab fa-whatsapp" 
                 color="green"
                 subtitle="Last 30 days" />
    
    <x-stat-card value="{{ $newCustomers }}" 
                 label="{{ __('New Customers') }}" 
                 icon="ik ik-user-plus" 
                 color="primary"
                 subtitle="Last 30 days" />
    
    <x-stat-card value="{{ $newSuppliers }}" 
                 label="{{ __('New Suppliers') }}" 
                 icon="ik ik-truck" 
                 color="amber"
                 subtitle="Last 30 days" />
</div>

<!-- Messages & Notifications -->
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-card hover>
        <x-slot:header>
            {{ __('Recent Messages') }}
            @if($unreadNotifications > 0)
                <span class="ml-2 rounded-full bg-accent-500 px-2 py-0.5 text-xs text-white">{{ $unreadNotifications }}</span>
            @endif
        </x-slot:header>
        <div class="space-y-4 max-h-80 overflow-y-auto">
            @forelse($recentMessages as $message)
                <div class="flex items-center gap-3 border-b border-gray-100 pb-3 last:border-0">
                    <span class="relative shrink-0">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                            <i class="ik ik-mail text-sm"></i>
                        </span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h6 class="truncate text-sm font-semibold text-gray-700">{{ $message->subject }}</h6>
                        <p class="truncate text-xs text-gray-400">{{ $message->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="whitespace-nowrap text-xs text-gray-400">
                        <i class="far fa-clock mr-1"></i>{{ $message->created_at->format('H:i') }}
                    </span>
                </div>
            @empty
                <p class="text-center text-gray-400 py-4">No messages</p>
            @endforelse
        </div>
    </x-card>

    <x-card hover>
        <x-slot:header>{{ __('Quick Actions') }}</x-slot:header>
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('communication.sms.send') }}" 
               class="flex flex-col items-center justify-center p-6 bg-blue-50 hover:bg-blue-100 rounded-xl transition">
                <i class="fas fa-sms text-3xl text-blue-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Send SMS</span>
            </a>
            
            <a href="{{ route('communication.whatsapp.send') }}" 
               class="flex flex-col items-center justify-center p-6 bg-green-50 hover:bg-green-100 rounded-xl transition">
                <i class="fab fa-whatsapp text-3xl text-green-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Send WhatsApp</span>
            </a>
            
            <a href="{{ route('communication.email.compose') }}" 
               class="flex flex-col items-center justify-center p-6 bg-red-50 hover:bg-red-100 rounded-xl transition">
                <i class="ik ik-mail text-3xl text-red-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Compose Email</span>
            </a>
            
            <a href="{{ route('customers.create') }}" 
               class="flex flex-col items-center justify-center p-6 bg-amber-50 hover:bg-amber-100 rounded-xl transition">
                <i class="ik ik-user-plus text-3xl text-amber-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Add Customer</span>
            </a>
        </div>
    </x-card>
</div>