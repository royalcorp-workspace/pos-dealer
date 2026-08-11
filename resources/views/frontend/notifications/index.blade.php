@extends('frontend.layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-brand-dark">Notifikasi</h1>
            <button onclick="markAllRead()" class="text-sm text-brand-gold hover:text-brand-gold-dark font-medium">
                Tandai Semua Dibaca
            </button>
        </div>

        @if($notifications->isEmpty())
            <div class="text-center py-12">
                <i class="fa-solid fa-bell-slash w-12 h-12 text-gray-300 mb-4"></i>
                <p class="text-gray-500">Belum ada notifikasi.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="p-4 border border-gray-100 rounded-xl bg-white shadow-sm">
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800 {{ $notification->is_read ? '' : 'text-brand-gold-dark' }}">
                                    {{ $notification->title ?? 'Notifikasi' }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $notification->message ?? '' }}
                                </p>
                                @if($notification->action_url)
                                    <a href="{{ $notification->action_url }}" class="text-xs text-brand-gold hover:text-brand-gold-dark font-medium mt-2 inline-block">
                                        Lihat Detail
                                    </a>
                                @endif
                                @if($notification->published_at)
                                    <span class="text-xs text-gray-400 mt-1 block">
                                        {{ \Carbon\Carbon::parse($notification->published_at)->format('d M Y H:i') }}
                                    </span>
                                @endif
                            </div>
                            @if(!$notification->is_read)
                                <span class="w-2 h-2 bg-brand-gold rounded-full flex-shrink-0 mt-0.5"></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
