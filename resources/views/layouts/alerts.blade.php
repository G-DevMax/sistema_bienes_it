@php
    $alerts = [
        'success' => 'bg-green-50 text-green-800 border-green-300',
        'error' => 'bg-red-50 text-red-800 border-red-300',
        'info' => 'bg-blue-50 text-blue-800 border-blue-300',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-300',
    ];
@endphp

@foreach ($alerts as $type => $classes)
    @if (session()->has($type))
        <div class="flex items-center p-4 mb-4 border rounded-lg {{ $classes }}" role="alert">
            <div class="text-sm font-medium">
                {{ session($type) }}
            </div>
        </div>
    @endif
@endforeach

{{-- Manejo automático de errores de validación de Laravel ($errors) --}}
@if ($errors->any())
    <div class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
