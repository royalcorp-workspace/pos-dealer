@extends('frontend.layouts.app')

@section('title', 'Tentang Kami - IMG')
@section('meta_description', 'IMG International Mattress Gallery - Toko kasur dan perlengkapan tidur premium dengan koleksi lengkap springbed, bantal, dan aksesori tidur berkualitas.')
@section('canonical', route('about'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            @if($about)
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-2">{{ $about->company_name }}</h1>
                @if($about->tagline)
                    <p class="text-lg text-brand-gold font-medium italic mb-6">{{ $about->tagline }}</p>
                @endif

                @if($about->cover_image)
                    <div class="mb-8 rounded-2xl overflow-hidden aspect-video shadow-md">
                        <img src="{{ asset('storage/' . $about->cover_image) }}" alt="{{ $about->company_name }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="prose prose-lg max-w-none text-gray-600 space-y-6">
                    <div>
                        {!! $about->description !!}
                    </div>

                    @if($about->vision)
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-8 mb-3">Visi</h2>
                            <p class="mb-4">{!! nl2br(e($about->vision)) !!}</p>
                        </div>
                    @endif

                    @if($about->mission)
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-4 mb-3">Misi</h2>
                            <p class="mb-4">{!! nl2br(e($about->mission)) !!}</p>
                        </div>
                    @endif

                    @if($about->values)
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-4 mb-3">Nilai-Nilai Kami</h2>
                            <p class="mb-4">{!! nl2br(e($about->values)) !!}</p>
                        </div>
                    @endif

                    @if($about->address || $about->phone || $about->email)
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-6 mt-8">
                            <h3 class="text-lg font-bold text-brand-dark mb-4">Informasi Kontak</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                @if($about->address)
                                    <li class="flex items-start gap-2">
                                        <span class="font-semibold text-brand-dark min-w-[80px]">Alamat:</span>
                                        <span>{{ $about->address }}</span>
                                    </li>
                                @endif
                                @if($about->phone)
                                    <li class="flex items-start gap-2">
                                        <span class="font-semibold text-brand-dark min-w-[80px]">Telepon:</span>
                                        <span>{{ $about->phone }}</span>
                                    </li>
                                @endif
                                @if($about->email)
                                    <li class="flex items-start gap-2">
                                        <span class="font-semibold text-brand-dark min-w-[80px]">Email:</span>
                                        <span>{{ $about->email }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            @else
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Tentang Kami</h1>
                
                <div class="prose prose-lg max-w-none text-gray-600">
                    <p class="mb-4">IMG International Mattress Gallery adalah toko kasur dan perlengkapan tidur premium yang berkomitmen menyediakan kenyamanan tidur terbaik untuk Anda.</p>
                    
                    <p class="mb-4">Kami menampilkan koleksi produk dari berbagai brand matras dan perlengkapan tidur terkemuka dunia dengan standar kualitas internasional.</p>
                    
                    <h2 class="text-xl font-bold text-brand-dark mt-8 mb-3">Visi & Misi</h2>
                    <p class="mb-4">Visi kami adalah menjadi mitra utama Anda dalam menciptakan kualitas tidur yang optimal. Kami menyediakan produk dengan garansi resmi, layanan konsultasi gratis, dan program cicilan 0%.</p>
                </div>
            @endif
        </div>
    </div>
@endsection