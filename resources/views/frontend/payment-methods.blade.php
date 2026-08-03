@extends('frontend.layouts.app')

@section('title', 'Metode Pembayaran - IMG')
@section('meta_description', 'Berbagai metode pembayaran yang tersedia di IMG untuk kemudahan transaksi Anda.')
@section('canonical', route('payment-methods'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Metode Pembayaran</h1>
            
            @foreach(\App\Models\PaymentMethod::typeOptions() as $typeId => $typeName)
                @php $typeMethods = $methods->get($typeId, collect()); @endphp
                @if($typeMethods->isNotEmpty())
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-brand-dark mb-4">{{ $typeName }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($typeMethods as $method)
                                <div class="border border-brand-muted rounded-lg p-4 bg-white flex items-center gap-4">
                                    @if($method->image)
                                        <img src="{{ $method->image }}" alt="{{ $method->name }}" loading="lazy" decoding="async" class="w-12 h-12 object-contain">
                                    @else
                                        <div class="w-12 h-12 bg-brand-light rounded flex items-center justify-center">
                                            <i class="fa-solid fa-money-bill-wave text-brand-gold"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="font-bold text-brand-dark">{{ $method->name }}</h3>
                                        @if($method->has_charge)
                                            <p class="text-sm text-gray-500">
                                                Biaya tambahan: {{ $method->charge_type === 1 ? $method->charge_value . '%' ' : 'Rp ' . number_format($method->charge_value, 0, ',', '.') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection