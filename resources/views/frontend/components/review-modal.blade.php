<div 
    x-show="selectedProductForReview !== null"
    x-cloak
    data-review-modal
    class="fixed inset-0 z-50 overflow-y-auto"
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
                    :src="selectedProductForReview?.image" 
                    :alt="selectedProductForReview?.name" 
                    class="w-full max-w-[200px] md:max-w-full aspect-square object-cover rounded-xl shadow-sm mb-6" 
                />
                <h3 class="text-xl font-bold text-center text-brand-dark mb-2" x-text="selectedProductForReview?.name"></h3>
                
                <div class="flex items-center gap-1.5 mb-2 text-brand-gold">
                    <i class="fa-solid fa-star w-5 h-5 fill-current"></i>
                    <span class="font-bold text-brand-dark" x-text="selectedProductForReview?.rating"></span>
                    <span class="text-sm text-gray-500" x-text="'(' + selectedProductForReview?.reviewsCount + ' Ulasan)'"></span>
                </div>
                
                <div class="text-lg font-bold text-brand-darker bg-white px-4 py-2 rounded-lg shadow-sm border border-brand-muted mt-2">
                    <span x-text="selectedProductForReview?.isVariable ? 'Rp ' + Number(selectedProductForReview?.minPrice).toLocaleString('id-ID') + ' - Rp ' + Number(selectedProductForReview?.maxPrice).toLocaleString('id-ID') : 'Rp ' + Number(selectedProductForReview?.price).toLocaleString('id-ID')"></span>
                </div>
            </div>

            <!-- Right: Reviews List -->
            <div class="w-full md:w-7/12 p-6 sm:p-8 flex flex-col h-[50vh] md:h-auto overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-bold text-brand-dark flex items-center gap-2">
                        <i class="fa-solid fa-comments w-5 h-5 text-brand-gold"></i>
                        Ulasan Pembeli
                    </h4>
                    <button 
                        @click="selectedProductForReview = null"
                        class="p-2 text-gray-400 hover:text-brand-dark bg-gray-50 hover:bg-gray-100 rounded-full transition-colors focus:outline-none"
                    >
                        <i class="fa-solid fa-xmark w-5 h-5"></i>
                    </button>
                </div>

                <!-- Scrollable Reviews -->
                <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                    <template x-if="selectedProductForReview?.reviews && selectedProductForReview?.reviews.length > 0">
                        <div class="space-y-4">
                            <template x-for="review in selectedProductForReview.reviews" :key="review.id">
                                <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-brand-light text-brand-gold-dark font-bold flex items-center justify-center rounded-full text-sm" x-text="review.user.charAt(0)"></div>
                                            <div>
                                                <h5 class="font-semibold text-sm text-brand-dark" x-text="review.user"></h5>
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
                    
                    <template x-if="!selectedProductForReview?.reviews || selectedProductForReview?.reviews.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 space-y-2">
                            <i class="fa-solid fa-comments w-10 h-10 opacity-20"></i>
                            <p class="text-sm">Belum ada ulasan untuk produk ini.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
