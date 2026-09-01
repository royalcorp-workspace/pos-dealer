@extends('frontend.layouts.app')

@section('title', $warranty->meta_title ?? 'Klaim Garansi - IMG')
@section('meta_description', $warranty->meta_description ?? 'Panduan dan informasi klaim garansi produk IMG. Dapatkan proses pengajuan garansi yang mudah dan cepat.')
@section('canonical', route('warranty'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            @if($warranty)
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">{{ $warranty->title }}</h1>
                
                @if($warranty->featured_image)
                    <div class="mb-8 rounded-2xl overflow-hidden aspect-video shadow-md">
                        <img src="{{ media_url($warranty->featured_image) }}" alt="{{ $warranty->title }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                    </div>
                @endif
                
                <div class="prose prose-lg max-w-none text-gray-600 space-y-6">
                    <div>
                        {!! $warranty->content !!}
                    </div>
                    
                    @if(!empty($warranty->steps))
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Langkah-langkah Pengajuan</h2>
                            <ol class="list-decimal pl-6 mb-4 space-y-2">
                                @foreach($warranty->steps as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    @if(!empty($warranty->required_documents))
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Dokumen yang Diperlukan</h2>
                            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 mb-4">
                                @if(is_array($warranty->required_documents))
                                    <ul class="list-disc pl-6 space-y-2">
                                        @foreach($warranty->required_documents as $document)
                                            <li class="text-gray-600 text-base">{{ $document }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-600 text-base">{!! nl2br(e($warranty->required_documents)) !!}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($warranty->processing_time_days)
                        <p class="text-sm text-gray-500 mt-4 italic">Waktu proses verifikasi: Sekitar {{ $warranty->processing_time_days }} hari kerja.</p>
                    @endif
                </div>
            @else
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Klaim Garansi</h1>
                
                <div class="prose prose-lg max-w-none text-gray-600">
                    <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Prosedur Klaim Garansi</h2>
                    <ol class="list-decimal pl-6 mb-4 space-y-2">
                        <li>Hubungi layanan pelanggan IMG melalui WhatsApp atau telepon</li>
                        <li>Siapkan bukti pembelian (invoice/struk)</li>
                        <li>Foto produk yang mengalami kerusakan</li>
                        <li>Isi formulir klaim garansi</li>
                        <li>Tim kami akan memverifikasi dan memandu proses selanjutnya</li>
                    </ol>
                    
                    <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Periode Garansi</h2>
                    <p class="mb-4">Produk kami dilengkapi garansi sesuai ketentuan masing-masing brand, biasanya antara 5-10 tahun.</p>
                </div>
            @endif
        </div>
    </div>
@endsection