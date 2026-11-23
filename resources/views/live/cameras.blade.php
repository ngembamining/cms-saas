@extends('layouts.main')

@section('title', 'Live Cameras')

@section('content')
    <x-page-header 
        title="{{ __('Live Cameras') }}" 
        subtitle="{{ __('Real-time CCTV monitoring across all branches with live video feeds and camera controls') }}" 
        icon="fas fa-video"
        :breadcrumbs="['Home' => route('dashboard'), 'Live View' => route('live.index'), 'Cameras' => null]" 
    />

    <div class="grid grid-cols-1 gap-6">
        {{-- Camera Grid --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium">{{ __('Camera Feeds') }}</span>
                        <x-badge color="success" class="text-xs">
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                {{ $onlineCameras ?? 0 }} Online
                            </span>
                        </x-badge>
                        <x-badge color="danger" class="text-xs">
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                {{ $offlineCameras ?? 0 }} {{ __('Not Configured') }}
                            </span>
                        </x-badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="refreshCameras(event)" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-600 transition hover:bg-primary-100">
                            <i class="fas fa-sync-alt text-xs"></i> Refresh
                        </button>
                        <button type="button" onclick="toggleGridView()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" onclick="toggleFullscreen()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </x-slot:header>

            {{-- Branch Filter --}}
            <div class="mb-6 flex flex-wrap items-center gap-3 border-b border-gray-100 pb-4">
                <span class="text-sm font-medium text-gray-500">{{ __('Branches:') }}</span>
                <button type="button" class="branch-filter active rounded-full bg-primary-500 px-4 py-1 text-sm font-medium text-white transition hover:bg-primary-600" data-branch="all">
                    {{ __('All') }}
                </button>
                @foreach($branches as $branch)
                    <button type="button" class="branch-filter rounded-full bg-gray-100 px-4 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-200" data-branch="{{ $branch->id }}">
                        {{ $branch->name }}
                    </button>
                @endforeach
            </div>

            {{-- Camera Grid --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" id="cameraGrid">
                @foreach($cameras as $camera)
                    <div class="camera-item group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-lg" data-branch="{{ $camera['branch_id'] }}">
                        {{-- Camera Feed --}}
                        <div class="relative aspect-video overflow-hidden bg-gray-900">
                            @if($camera['status'] === 'online')
                                {{-- Live Stream Placeholder --}}
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                                    <div class="relative">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="h-16 w-16 rounded-full border-4 border-primary-500/30 bg-primary-500/10 backdrop-blur-sm">
                                                <div class="flex h-full w-full items-center justify-center">
                                                    <i class="fas fa-video text-2xl text-primary-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="absolute -top-2 -right-2">
                                            <span class="flex h-3 w-3">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                                            </span>
                                        </div>
                                        <div class="absolute bottom-2 left-2 rounded-md bg-black/70 px-2 py-0.5 text-[10px] font-medium text-white/80 backdrop-blur-sm">
                                            <i class="fas fa-circle text-green-500 text-[6px] mr-1"></i>
                                            LIVE
                                        </div>
                                        <div class="absolute bottom-2 right-2 rounded-md bg-black/70 px-2 py-0.5 text-[10px] font-medium text-white/80 backdrop-blur-sm">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{ $camera['last_activity']->format('H:i:s') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Not Configured Placeholder --}}
                                <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-gray-700 to-gray-800">
                                    <div class="relative">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="h-16 w-16 rounded-full border-4 border-yellow-500/30 bg-yellow-500/10 backdrop-blur-sm">
                                                <div class="flex h-full w-full items-center justify-center">
                                                    <i class="fas fa-cog text-2xl text-yellow-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="absolute -top-2 -right-2">
                                            <span class="flex h-3 w-3">
                                                <span class="relative inline-flex h-3 w-3 rounded-full bg-yellow-500"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-center">
                                        <span class="text-sm font-medium text-gray-400">{{ __('Camera Not Configured') }}</span>
                                        <div class="mt-2 flex items-center justify-center gap-2">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                            <span class="text-xs text-gray-500">{{ __('Setup Required') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Hover Controls Overlay --}}
                            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                @if($camera['status'] === 'online')
                                    <button type="button" onclick="viewCamera({{ $camera['id'] }})" class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                                        <i class="fas fa-expand text-sm"></i>
                                    </button>
                                    <button type="button" onclick="takeSnapshot({{ $camera['id'] }})" class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                                        <i class="fas fa-camera text-sm"></i>
                                    </button>
                                    <button type="button" onclick="recordCamera({{ $camera['id'] }})" class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                                        <i class="fas fa-circle text-sm text-red-400"></i>
                                    </button>
                                @else
                                    <button type="button" onclick="configureCamera({{ $camera['id'] }})" class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                                        <i class="fas fa-plus-circle text-sm"></i>
                                    </button>
                                    <button type="button" onclick="retryCamera({{ $camera['id'] }})" class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                                        <i class="fas fa-sync-alt text-sm"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Camera Info --}}
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h6 class="font-semibold text-gray-700">{{ $camera['name'] }}</h6>
                                    <p class="text-xs text-gray-400">{{ $camera['branch_name'] ?? 'Main Branch' }}</p>
                                </div>
                                @if($camera['status'] === 'online')
                                    <x-badge color="success" size="sm">
                                        <span class="flex items-center gap-1">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Online') }}
                                        </span>
                                    </x-badge>
                                @else
                                    <x-badge color="warning" size="sm">
                                        <span class="flex items-center gap-1">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                            {{ __('Not Configured') }}
                                        </span>
                                    </x-badge>
                                @endif
                            </div>
                            <div class="mt-3 flex items-center gap-4 text-xs text-gray-400">
                                @if($camera['status'] === 'online')
                                    <span><i class="far fa-clock mr-1"></i> {{ $camera['last_activity']->diffForHumans() }}</span>
                                    <span><i class="fas fa-signal mr-1"></i> {{ 'Good' }}</span>
                                @else
                                    <span><i class="far fa-clock mr-1"></i> {{ __('Not Available') }}</span>
                                    <span><i class="fas fa-exclamation-triangle mr-1 text-yellow-500"></i> {{ __('Needs Setup') }}</span>
                                @endif
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                @if($camera['status'] === 'online')
                                    <button type="button" onclick="viewCamera({{ $camera['id'] }})" class="flex-1 rounded-lg bg-primary-50 px-3 py-1.5 text-center text-sm font-medium text-primary-600 transition hover:bg-primary-100">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                    <button type="button" onclick="openCameraSettings({{ $camera['id'] }})" class="rounded-lg bg-gray-50 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-100">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                @else
                                    <button type="button" onclick="configureCamera({{ $camera['id'] }})" class="flex-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-center text-sm font-medium text-yellow-700 transition hover:bg-yellow-100">
                                        <i class="fas fa-plus-circle mr-1"></i> {{ __('Configure Camera') }}
                                    </button>
                                    <button type="button" onclick="retryCamera({{ $camera['id'] }})" class="rounded-lg bg-gray-50 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-100">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Empty State --}}
            @if(count($cameras) === 0)
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="rounded-full bg-gray-100 p-6">
                        <i class="fas fa-video-slash text-4xl text-gray-400"></i>
                    </div>
                    <h4 class="mt-4 text-lg font-semibold text-gray-600">{{ __('No Cameras Found') }}</h4>
                    <p class="mt-1 text-sm text-gray-400">{{ __('No CCTV cameras are currently configured in the system.') }}</p>
                    <x-button variant="primary" class="mt-4">
                        <i class="fas fa-plus mr-2"></i> {{ __('Add Camera') }}
                    </x-button>
                </div>
            @endif

            {{-- Pagination (if needed) --}}
            <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-sm text-gray-400">{{ __('Showing') }} 1-{{ count($cameras) }} {{ __('of') }} {{ count($cameras) }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="rounded-lg bg-primary-500 px-3 py-1.5 text-sm font-medium text-white">1</button>
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-50">2</button>
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-50">3</button>
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </x-card>

        {{-- Quick Stats Row --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card icon="fas fa-video" :value="$totalCameras ?? 0" :label="__('Total Cameras')" color="primary" />
            <x-stat-card icon="fas fa-check-circle" :value="$onlineCameras ?? 0" :label="__('Online')" color="success" />
            <x-stat-card icon="fas fa-exclamation-circle" :value="$offlineCameras ?? 0" :label="__('Not Configured')" color="warning" />
            <x-stat-card icon="fas fa-clock" :value="$avgUptime ?? '0%'" :label="__('Uptime')" color="info" />
        </div>

        {{-- Setup Guide for Offline Cameras --}}
        @if(($offlineCameras ?? 0) > 0)
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <i class="fas fa-info-circle text-yellow-500"></i>
                    </div>
                    <div>
                        <h5 class="font-semibold text-yellow-800">{{ __('Camera Setup Required') }}</h5>
                        <p class="text-sm text-yellow-700">
                            {{ $offlineCameras }} {{ __('camera(s) are not configured. Click the "Configure Camera" button on each camera card to set up the camera feed.') }}
                        </p>
                        <button type="button" onclick="showSetupGuide()" class="mt-2 text-sm font-medium text-yellow-700 hover:underline">
                            {{ __('View Setup Guide →') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Camera Modal --}}
    <div id="cameraModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/70 backdrop-blur-sm" onclick="closeModal(event)">
        <div class="relative max-h-[90vh] max-w-4xl w-full rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-gray-100 p-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-700">{{ __('Camera View') }}</h3>
                <button type="button" onclick="closeModal()" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="aspect-video overflow-hidden rounded-xl bg-gray-900">
                    <div id="modalFeed" class="flex h-full w-full items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-video text-6xl text-gray-600"></i>
                            <p class="mt-3 text-gray-400">{{ __('Loading camera feed...') }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-600 transition hover:bg-primary-100">
                            <i class="fas fa-play mr-1"></i> Play
                        </button>
                        <button type="button" class="rounded-lg bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                            <i class="fas fa-pause mr-1"></i> Pause
                        </button>
                        <button type="button" onclick="takeSnapshotFromModal()" class="rounded-lg bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                            <i class="fas fa-camera mr-1"></i> Snapshot
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="toggleModalFullscreen()" class="rounded-lg bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button type="button" class="rounded-lg bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                            <i class="fas fa-record-vinyl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .camera-item {
            transition: all 0.3s ease;
        }
        .camera-item:hover {
            transform: translateY(-2px);
        }
        .branch-filter.active {
            background-color: #2563eb;
            color: white;
        }
        .branch-filter:not(.active):hover {
            background-color: #e5e7eb;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Branch filter
            document.querySelectorAll('.branch-filter').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.branch-filter').forEach(function(b) {
                        b.classList.remove('active');
                        b.classList.remove('bg-primary-500');
                        b.classList.remove('text-white');
                        b.classList.add('bg-gray-100');
                        b.classList.add('text-gray-600');
                    });
                    this.classList.add('active');
                    this.classList.remove('bg-gray-100');
                    this.classList.remove('text-gray-600');
                    this.classList.add('bg-primary-500');
                    this.classList.add('text-white');
                    
                    var branchId = this.dataset.branch;
                    var cameras = document.querySelectorAll('.camera-item');
                    
                    cameras.forEach(function(camera) {
                        if (branchId === 'all' || camera.dataset.branch === branchId) {
                            camera.style.display = 'block';
                        } else {
                            camera.style.display = 'none';
                        }
                    });
                });
            });
        });

        // Refresh cameras
        function refreshCameras(event) {
            var btn = event ? event.target.closest('button') : null;
            if (btn) {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Refreshing...';
                btn.disabled = true;
                setTimeout(function() {
                    btn.innerHTML = original;
                    btn.disabled = false;
                    window.location.reload();
                }, 500);
            } else {
                window.location.reload();
            }
        }

        // Toggle grid view
        function toggleGridView() {
            var grid = document.getElementById('cameraGrid');
            if (grid) {
                grid.classList.toggle('grid-cols-1');
                grid.classList.toggle('sm:grid-cols-2');
                grid.classList.toggle('xl:grid-cols-3');
                grid.classList.toggle('2xl:grid-cols-4');
                grid.classList.toggle('grid-cols-2');
            }
        }

        // Toggle fullscreen
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function(err) {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // View camera
        function viewCamera(id) {
            var modal = document.getElementById('cameraModal');
            var title = document.getElementById('modalTitle');
            var feed = document.getElementById('modalFeed');
            
            if (modal && title && feed) {
                title.textContent = 'Camera #' + id + ' - Live Feed';
                feed.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin text-4xl text-primary-400"></i><p class="mt-3 text-gray-400">Loading camera #' + id + '...</p></div>';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                setTimeout(function() {
                    feed.innerHTML = '<div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900"><div class="text-center"><i class="fas fa-video text-6xl text-primary-400"></i><p class="mt-3 text-sm text-gray-400">Camera #' + id + ' - Live Stream</p><span class="mt-2 inline-flex items-center gap-2 rounded-full bg-green-500/20 px-3 py-1 text-xs text-green-400"><span class="inline-block h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>LIVE</span></div></div>';
                }, 1000);
            }
        }

        // Take snapshot
        function takeSnapshot(id) {
            showToast('Snapshot captured from camera #' + id, 'success');
        }

        // Take snapshot from modal
        function takeSnapshotFromModal() {
            showToast('Snapshot captured!', 'success');
        }

        // Record camera
        function recordCamera(id) {
            showToast('Recording started for camera #' + id, 'info');
        }

        // Open camera settings
        function openCameraSettings(id) {
            showToast('Opening settings for camera #' + id, 'info');
        }

        // Configure camera (for offline cameras)
        function configureCamera(id) {
            showToast('Opening camera configuration wizard for camera #' + id, 'info');
        }

        // Retry camera connection
        function retryCamera(id) {
            var btn = event ? event.target.closest('button') : null;
            if (btn) {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i>';
                btn.disabled = true;
            }
            
            showToast('Attempting to reconnect camera #' + id + '...', 'warning');
            
            setTimeout(function() {
                if (btn) {
                    btn.innerHTML = original;
                    btn.disabled = false;
                }
                showToast('Camera #' + id + ' reconnection attempt completed', 'info');
            }, 3000);
        }

        // Show setup guide
        function showSetupGuide() {
            showToast('Opening camera setup guide...', 'info');
        }

        // Close modal
        function closeModal(event) {
            var modal = document.getElementById('cameraModal');
            if (modal) {
                if (event && event.target !== modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // Toggle modal fullscreen
        function toggleModalFullscreen() {
            var modalContent = document.querySelector('#cameraModal .relative');
            if (modalContent) {
                modalContent.classList.toggle('max-w-4xl');
                modalContent.classList.toggle('max-w-full');
                modalContent.classList.toggle('max-h-[90vh]');
                modalContent.classList.toggle('max-h-full');
                modalContent.classList.toggle('rounded-2xl');
                modalContent.classList.toggle('rounded-none');
            }
        }

        // Toast notification
        function showToast(message, type) {
            type = type || 'info';
            var colors = {
                success: 'bg-green-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500',
                danger: 'bg-red-500'
            };
            
            var toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 z-50 rounded-lg ' + (colors[type] || 'bg-gray-800') + ' px-6 py-3 text-white shadow-lg transition-all transform translate-y-0 opacity-100';
            var icon = type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle';
            toast.innerHTML = '<div class="flex items-center gap-3"><i class="fas fa-' + icon + '"></i><span>' + message + '</span></div>';
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.style.transform = 'translateY(100%)';
                toast.style.opacity = '0';
                setTimeout(function() { toast.remove(); }, 300);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
            if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            }
        });

        console.log('🎥 Live Cameras loaded successfully!');
    </script>
    @endpush
@endsection