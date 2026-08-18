<div 
    x-show="isAuthOpen"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    x-init="window.initFirebaseGoogleSignIn && window.initFirebaseGoogleSignIn()"
>
    <!-- Backdrop -->
    <div 
        x-show="isAuthOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="isAuthOpen = false"
        class="fixed inset-0 z-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Modal Content wrapper -->
    <div class="relative z-10 flex items-center justify-center min-h-screen p-4">
        <!-- Modal Card -->
        <div 
            x-show="isAuthOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative overflow-hidden font-sans flex flex-col max-h-[90vh] z-55"
            x-data="{ 
                isLoginForm: true, 
                isForgotPassword: false, 
                isOtpStep: false, 
                rememberMe: false, 
                forgotEmail: '', 
                forgotChannel: 'email', 
                showToast: false, 
                toastMessage: '', 
                toastType: 'success', 
                isSubmitting: false,
                errorMessage: ''
            }"
            @click.stop
            @show-auth-toast.window="showToast = true; toastMessage = $event.detail.message; toastType = $event.detail.type || 'success'; setTimeout(() => showToast = false, 3000)"
            x-on:auth-error-login.window="errorMessage = $event.detail.message"
            x-on:auth-error-login-clear.window="errorMessage = ''"
        >
            <!-- Toast Notification -->
            <div 
                x-show="showToast"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                :class="toastType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
                class="absolute top-4 left-1/2 -translate-x-1/2 -translate-y-full px-4 py-3 rounded-xl border shadow-lg flex items-center gap-2 z-20"
            >
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path x-show="toastType === 'success'" d="M6 12.5l3 3 9-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle x-show="toastType !== 'success'" cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                    <path x-show="toastType !== 'success'" d="M12 8v4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <span class="text-sm font-semibold" x-text="toastMessage"></span>
            </div>

            <!-- Close Button -->
            <button 
                @click="isAuthOpen = false"
                class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full transition-colors z-10 focus:outline-none"
                aria-label="Tutup"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <!-- Tabs -->
            <div class="flex w-full pt-2 px-2 border-b border-gray-100">
                <button 
                    @click="isLoginForm = true; isForgotPassword = false"
                    :class="isLoginForm && !isForgotPassword ? 'border-brand-gold text-brand-dark' : 'border-transparent text-gray-400 hover:text-gray-700'"
                    class="flex-1 py-4 text-center font-bold text-sm border-b-2 transition-colors focus:outline-none"
                >
                    Sign In
                </button>
                <button 
                    @click="isLoginForm = false; isForgotPassword = false"
                    x-show="!isForgotPassword"
                    :class="!isLoginForm && !isForgotPassword ? 'border-brand-gold text-brand-dark' : 'border-transparent text-gray-400 hover:text-gray-700'"
                    class="flex-1 py-4 text-center font-bold text-sm border-b-2 transition-colors focus:outline-none"
                >
                    Register
                </button>
            </div>

            <!-- Forms Container -->
            <div class="p-6 sm:p-8 overflow-y-auto">
                <!-- Login Form -->
                <div x-show="isLoginForm && !isForgotPassword" x-cloak>
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ __('Welcome Back') }}</h2>
                        <p class="text-gray-500 text-sm mt-2">{{ __('Masuk untuk mengakses keranjang dan pesanan Anda.') }}</p>
                    </div>

                    <form action="/login" method="POST" class="space-y-4" id="loginModalForm">
                        @csrf
                        <div x-show="errorMessage" x-html="errorMessage" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl" x-cloak></div>
                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">{{ __('Email Address') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 7.5 9 6 9-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <input type="email" name="email" required placeholder="you@example.com" class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"/>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider">{{ __('Password') }}</label>
                                <button type="button" @click="isForgotPassword = true; isLoginForm = false" class="text-xs font-bold text-brand-gold hover:text-brand-dark transition-colors focus:outline-none">{{ __('Lost password?') }}</button>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                </div>
                                <input type="password" name="password" required placeholder="••••••••" class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"/>
                            </div>
                        </div>
                        <div class="flex items-center pt-2">
                            <button type="button" @click="rememberMe = !rememberMe" class="flex items-center gap-2 cursor-pointer w-full text-left focus:outline-none">
                                <div :class="rememberMe ? 'bg-brand-gold border-brand-gold' : 'bg-white border border-brand-muted'" class="w-5 h-5 rounded border flex items-center justify-center transition-colors">
                                    <svg x-show="rememberMe" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 12.5l3 3 9-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-600 hover:text-brand-dark transition-colors">{{ __('Remember me') }}</span>
                            </button>
                        </div>
                        <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 mt-2 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-50">
                            <span x-show="!isSubmitting">{{ __('Sign In') }}</span><span x-show="isSubmitting">{{ __('Memproses...') }}</span>
                        </button>
                    </form>
                </div>

                <!-- Forgot Password Form -->
                <div x-show="isForgotPassword && !isOtpStep" x-cloak>
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ __('Reset Password') }}</h2>
                        <p class="text-gray-500 text-sm mt-2">{{ __('Masukkan email/SMS untuk menerima kode OTP.') }}</p>
                    </div>
                    <form action="/api/auth/forgot-password" method="POST" class="space-y-4" id="forgotPasswordModalForm">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">{{ __('Email atau No. HP') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 7.5 9 6 9-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <input type="text" name="email" x-model="forgotEmail" required placeholder="you@example.com" class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"/>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input
                                    type="radio"
                                    name="forgotChannel"
                                    value="email"
                                    x-model="forgotChannel"
                                    class="hidden"
                                >

                                <div
                                    :class="forgotChannel === 'email'
                                        ? 'bg-brand-dark text-brand-gold border-brand-dark'
                                        : 'bg-white text-gray-500 border-brand-muted'"
                                    class="text-center py-3 px-4 rounded-xl border font-semibold transition-all"
                                >
                                    {{ __('Email') }}
                                </div>
                            </label>

                            <label class="flex-1 cursor-pointer">
                                <input
                                    type="radio"
                                    name="forgotChannel"
                                    value="sms"
                                    x-model="forgotChannel"
                                    class="hidden"
                                >

                                <div
                                    :class="forgotChannel === 'sms'
                                        ? 'bg-brand-dark text-brand-gold border-brand-dark'
                                        : 'bg-white text-gray-500 border-brand-muted'"
                                    class="text-center py-3 px-4 rounded-xl border font-semibold transition-all"
                                >
                                    {{ __('SMS') }}
                                </div>
                            </label>
                        </div>
                        <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-50"><span x-show="!isSubmitting">{{ __('Lanjutkan') }}</span><span x-show="isSubmitting">{{ __('Memproses...') }}</span></button>
                        <button type="button" @click="isForgotPassword = false; isLoginForm = true" class="w-full py-2.5 text-sm font-semibold text-gray-600 hover:text-brand-dark transition-colors focus:outline-none">&larr; {{ __('Kembali ke Sign In') }}</button>
                    </form>
                </div>

                <!-- Register Form -->
                <div x-show="!isLoginForm && !isForgotPassword" x-cloak>
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ __('Create an Account') }}</h2>
                        <p class="text-gray-500 text-sm mt-2">{{ __('Daftar sekarang dan nikmati pengalaman belanja tidur impian.') }}</p>
                    </div>
                    <form action="/register" method="POST" class="space-y-4" id="registerModalForm">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">{{ __('Email Address') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 7.5 9 6 9-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <input type="email" name="email" required placeholder="you@example.com" class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"/>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">{{ __('Password') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                </div>
                                <input type="password" name="password" required placeholder="••••••••" class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"/>
                            </div>
                        </div>
                        <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 mt-4 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-50"><span x-show="!isSubmitting">{{ __('Create Account') }}</span><span x-show="isSubmitting">{{ __('Memproses...') }}</span></button>
                        <button type="button" @click="isLoginForm = true; isForgotPassword = false" class="w-full py-2.5 text-sm font-semibold text-gray-600 hover:text-brand-dark transition-colors focus:outline-none">&larr; {{ __('Kembali ke Sign In') }}</button>
                    </form>
                </div>

                <!-- Social Logins -->
                <div x-show="!isForgotPassword" x-cloak class="py-6 flex items-center text-center">
                    <div class="flex-1 border-t border-gray-100"></div>
                    <span class="px-4 tracking-widest text-[#a1a1aa] uppercase text-[10px] font-bold">Or continue with</span>
                    <div class="flex-1 border-t border-gray-100"></div>
                </div>
                <div x-show="!isForgotPassword" x-cloak class="grid grid-cols-2 gap-4">
                <button type="button" data-google-signin class="flex items-center justify-center gap-2 py-3 px-4 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 rounded-xl font-bold text-gray-700 text-sm transition-colors cursor-pointer focus:outline-none">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.58c2.1-1.92 3.31-4.74 3.31-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-1.02 7.28-2.77l-3.58-2.77c-.99.69-2.26 1.1-3.7 1.1-2.87 0-5.3-1.96-6.16-4.63H2.13v2.85C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 13.93c-.22-.65-.35-1.36-.35-2.09s.13-1.44.35-2.09V6.9H2.13C1.43 8.35 1 9.92 1 11.64c0 1.73.43 3.3 1.13 4.74l3.71-2.45z"/><path fill="#EA4335" d="M12 5.58c1.62 0 3.06.58 4.21 1.66l3.15-3.15C17.45 2.29 14.97 1 12 1 7.7 1 3.99 3.47 2.13 6.9l3.71 2.85c.86-2.67 3.29-4.17 6.16-4.17z"/></svg>
                    Google
                </button>
                    <button type="button" @click="alert('Login dengan Facebook belum diimplementasi');" class="flex items-center justify-center gap-2 py-3 px-4 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 rounded-xl font-bold text-gray-700 text-sm transition-colors cursor-pointer focus:outline-none">
                        <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>