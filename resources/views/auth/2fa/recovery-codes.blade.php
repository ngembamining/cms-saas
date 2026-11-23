@extends('layouts.main')

@section('title', 'Recovery Codes')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <x-slot:header>Save Your Recovery Codes</x-slot:header>

        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <i class="ik ik-alert-triangle mr-2"></i>
            <strong>Save these codes now.</strong> They will not be shown again. Each code can be used once if you lose access to your device.
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach($codes as $code)
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 font-mono text-sm text-center tracking-widest">
                    {{ $code }}
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button onclick="navigator.clipboard.writeText('{{ implode("\n", $codes) }}'); this.innerHTML='<i class=\'ik ik-check\'></i> Copied!';"
                    class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-300">
                <i class="ik ik-copy"></i> Copy all
            </button>
            <a href="{{ route('dashboard') }}" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                I've saved them <i class="ik ik-arrow-right"></i>
            </a>
        </div>
    </x-card>
</div>
@endsection