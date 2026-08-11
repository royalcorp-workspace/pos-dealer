<div 
    x-data="{
        showReviewForm: false,
        rating: 0,
        hoverRating: 0,
        reviewText: '',
        reviewError: '',
        submitting: false,
        productId: null,
        orderId: null,
        product: null,
        resetForm() {
            showReviewForm = false;
            rating = 0;
            reviewText = '';
            reviewError = '';
        },
        submitReview() {
            reviewError = '';
            if (!rating) { reviewError = 'Pilih rating terlebih dahulu'; return; }
            if (!reviewText || reviewText.trim().length < 10) { reviewError = 'Ulasan minimal 10 karakter'; return; }
            const fd = new FormData(document.getElementById('review-form'));
            submitting = true;
            fetch('{{ route('reviews.store') }}', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resetForm();
                } else {
                    reviewError = data.error || 'Gagal mengirim ulasan';
                }
            })
            .catch(() => reviewError = 'Terjadi kesalahan jaringan')
            .finally(() => submitting = false);
        }
    }"
    x-show="selectedProductForReview !== null"
    x-cloak
    data-review-modal
    class="fixed inset-0 z-50 overflow-y-auto"
    @open-review.window="
        showReviewForm = false;
        product = $event.detail;
        productId = $event.detail.product_id;
        orderId = $event.detail.order_id;
        rating = 0;
        reviewText = '';
        reviewError = '';
    "
>
    <!-- Backdrop -->
    <div 
        x-show="selectedProductForReview !== null"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="selectedProductForReview = null"
        class="fixed inset-0 z-0 bg-brand-dark/40 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Modal Content Wrapper -->
    <div class="relative z-10 flex items-center justify-center min-h-screen p-4">
        <!-- Modal Card -->
        <div 
            x-show="selectedProductForReview !== null"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden font-sans flex flex-col md:flex-row z-55 max-h-[90vh]"
            @click.stop
        >
            <!-- Left: Product Info -->
            <div class="w-full md:w-5/12 bg-brand-muted/30 p-6 sm:p-8 flex flex-col items-center justify-center border-b md:border-b-0 md:border-r border-gray-100 flex-shrink-0">
                <img 
                    :src="product?.image" 
                    :alt="product?.name" 
                    loading="lazy"
                    decoding="async"
                    class="w-full max-w-[200px] md:max-w-full aspect-square object-cover rounded-xl shadow-sm mb-6" 
                />
                <h3 class="text-xl font-bold text-center text-brand-dark mb-2" x-text="product?.name"></h3>
                
                <div class="flex items-center gap-1.5 mb-2 text-brand-gold">
                    <i class="fa-solid fa-star w-5 h-5 fill-current"></i>
                    <span class="font-bold text-brand-dark" x-text="product?.rating"></span>
                    <span class="text-sm text-gray-500" x-text="'(' + (product?.reviewsCount || 0) + ' Ulasan)'"></span>
                </div>

                <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-gray-500 line-through" x-show="product?.originalPrice && product?.originalPrice > product?.price" x-text="'Rp ' + Number(product?.originalPrice).toLocaleString('id-ID')"></span>
                    <span class="text-lg font-bold text-brand-darker bg-white px-4 py-2 rounded-lg shadow-sm border border-brand-muted"
                        x-text="product?.isVariable ? 'Rp ' + Number(product?.minPrice).toLocaleString('id-ID') + ' - ' + Number(product?.maxPrice).toLocaleString('id-ID') : 'Rp ' + Number(product?.price).toLocaleString('id-ID')">
                    </span>
                </div>
            </div>

            <!-- Right: Reviews List / Form -->
            <div class="w-full md:w-7/12 p-6 sm:p-8 flex flex-col h-[50vh] md:h-auto overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-bold text-brand-dark flex items-center gap-2">
                        <i class="fa-solid fa-comments w-5 h-5 text-brand-gold"></i>
                        <span x-text="showReviewForm ? 'Tulis Ulasan' : 'Ulasan Pembeli'"></span>
                    </h4>
                    <button 
                        @click="selectedProductForReview = null"
                        class="p-2 text-gray-400 hover:text-brand-dark bg-gray-50 hover:bg-gray-100 rounded-full transition-colors focus:outline-none"
                        aria-label="Tutup"
                    >
                        <i class="fa-solid fa-xmark w-5 h-5"></i>
                    </button>
                </div>

                <!-- Review Form -->
                <template x-if="showReviewForm">
                    <div class="flex-1 overflow-y-auto">
                        <form id="review-form" @submit.prevent="submitReview">
                            @csrf
                            <input type="hidden" name="product_id" :value="productId">
                            <input type="hidden" name="order_id" :value="orderId">
                            <input type="hidden" name="rating" :value="rating">

                            <div x-show="reviewError" x-cloak class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm" x-text="reviewError"></div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <div class="flex gap-1">
                                    <template x-for="i in 5">
                                        <button type="button" @click="rating = i" @mouseenter="hoverRating = i" @mouseleave="hoverRating = 0" class="text-2xl">
                                            <i :class="i <= (hoverRating || rating) ? 'fa-solid fa-star text-brand-gold' : 'fa-regular fa-star text-gray-300'"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ulasan (min 10 karakter)</label>
                                <textarea name="text" rows="4" minlength="10" required x-model="reviewText"
                                    class="w-full border border-brand-muted rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-gold/50"
                                    placeholder="Tulis ulasan Anda..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Foto/Video (opsional)</label>
                                <input type="file" name="image" accept="image/*" class="w-full border border-brand-muted rounded-xl px-3 py-2">
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" @click="showReviewForm = false" class="px-4 py-2 text-gray-600 hover:text-brand-dark">Kembali</button>
                                <button type="submit" :disabled="submitting" class="px-4 py-2 bg-brand-dark text-white rounded-xl font-bold hover:bg-brand-gold hover:text-brand-dark transition-colors disabled:opacity-50">
                                    <span x-text="submitting ? 'Mengirim...' : 'Kirim Review'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </template>

                <!-- Reviews List -->
                <template x-if="!showReviewForm">
                    <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                        <template x-if="!product?.reviews || product?.reviews.length === 0">
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 space-y-2">
                                <i class="fa-solid fa-comments w-10 h-10 opacity-20"></i>
                                <p class="text-sm">Belum ada ulasan untuk produk ini.</p>
                            </div>
                        </template>
                        <template x-for="review in product?.reviews || []" :key="review.id">
                            <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-brand-light text-brand-gold-dark font-bold flex items-center justify-center rounded-full text-sm" x-text="(review.user || 'P').charAt(0)"></div>
                                        <div>
                                            <h5 class="font-semibold text-sm text-brand-dark" x-text="review.user || 'Pelanggan'"></h5>
                                            <span class="text-[10px] text-gray-400" x-text="review.date"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating Stars -->
                                    <div class="flex items-center gap-0.5">
                                        <template x-for="starIndex in [1, 2, 3, 4, 5]">
                                            <svg 
                                                class="w-3.5 h-3.5"
                                                :class="starIndex <= review.rating ? 'text-yellow-400 fill-current' : 'text-gray-200'"
                                                xmlns="http://www.w3.org/2000/svg" 
                                                viewBox="0 0 24 24" 
                                                fill="none" 
                                                stroke="currentColor" 
                                                stroke-width="2" 
                                                stroke-linecap="round" 
                                                stroke-linejoin="round"
                                            >
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed" x-text="review.text"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Tulis Ulasan Button -->
                <template x-if="!showReviewForm">
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="showReviewForm = true" class="px-4 py-2 bg-brand-dark text-white rounded-xl font-bold hover:bg-brand-gold hover:text-brand-dark transition-colors">Tulis Ulasan</button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
