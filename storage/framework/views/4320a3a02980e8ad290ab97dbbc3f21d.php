<?php $__env->startSection('title', 'Sign In — '.get_system_setting('app_name', config('app.name'))); ?>
<?php $__env->startSection('heading', 'Welcome to Plantiq'); ?>
<?php $__env->startSection('subheading', 'Sign in to your workspace to continue'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(tenant_url('admin')); ?>" novalidate class="space-y-5" x-data="{ showPwd: false }">
        <?php echo csrf_field(); ?>

        
        <div class="space-y-1.5">
            <label for="email" class="font-600 block text-xs tracking-wider text-gray-600 uppercase">
                Email Address
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
                <input
                    id="email"
                    type="text"
                    name="email"
                    value="<?php echo e(old('email')); ?>"
                    placeholder="Enter your email"
                    required
                    autofocus
                    autocomplete="username"
                    class="w-full rounded-xl text-sm pl-10 pr-4 py-3 border outline-none transition-all
                    <?php echo e($errors->has('email') ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10'); ?>"
                />
            </div>
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <?php echo e($message); ?>

                </p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="space-y-1.5">
            <label for="password" class="font-600 block text-xs tracking-wider text-gray-600 uppercase">
                Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input
                    id="password"
                    :type="showPwd ? 'text' : 'password'"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl text-sm pl-10 pr-10 py-3 border outline-none transition-all
                    <?php echo e($errors->has('password') ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10'); ?>"
                />
                <button
                    type="button"
                    @click="showPwd = !showPwd"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors hover:text-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path
                            x-show="!showPwd"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                        <path
                            x-show="showPwd"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                        />
                    </svg>
                </button>
            </div>
            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <?php echo e($message); ?>

                </p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="flex items-center justify-between pt-0.5 text-sm">
            <label class="flex cursor-pointer items-center gap-2 select-none">
                <input
                    type="checkbox"
                    name="remember"
                    class="text-brand-600 focus:ring-brand-500/20 h-4 w-4 cursor-pointer rounded border-gray-300"
                />
                <span class="font-500 text-xs text-gray-600">Remember me</span>
            </label>
            <a
                href="<?php echo e(route('password.request')); ?>"
                class="font-600 text-brand-600 hover:text-brand-700 text-xs transition-colors hover:underline"
            >
                Forgot password?
            </a>
        </div>

        
        <button
            type="submit"
            class="bg-brand-600 hover:bg-brand-700 active:bg-brand-800 font-600 mt-1 w-full rounded-xl py-3 text-sm text-white shadow-sm transition-colors"
        >
            Sign In
        </button>

        
        <div class="mt-8 border-t border-gray-200/60 pt-6">
            <p class="text-center text-sm font-medium text-gray-600">
                Need an account?
                <a
                    href="<?php echo e(route('welcome')); ?>#contact"
                    class="text-brand-600 hover:text-brand-700 ml-1 font-bold transition-colors hover:underline"
                    >Contact us</>
            </p>
        </div>
    </form>

    
    <div x-data="pwaInstall()" x-cloak>
        
        <div
            x-show="installable && !installed && !promptDismissed"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed right-4 bottom-4 left-4 z-50 flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white py-2.5 pr-2 pl-3.5 shadow-lg ring-1 ring-black/5 md:right-6 md:bottom-6 md:left-auto md:w-[350px]"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <div class="bg-brand-50 text-brand-600 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                    </svg>
                </div>
                <div class="truncate">
                    <h4 class="text-xs font-bold text-gray-900">Install Plantiq App</h4>
                    <p class="truncate text-[10px] text-gray-500">For quick & optimal access.</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <button
                    type="button"
                    x-on:click="install()"
                    class="bg-brand-600 hover:bg-brand-700 rounded-lg px-2.5 py-1 text-[11px] font-bold text-white transition-colors"
                >
                    Install
                </button>
                <button
                    type="button"
                    @click="promptDismissed = true"
                    class="rounded p-1 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        
        <div
            x-show="isIOS && !installed && !promptDismissed"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed right-4 bottom-4 left-4 z-50 flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white py-2.5 pr-2 pl-3.5 shadow-lg ring-1 ring-black/5 md:right-6 md:bottom-6 md:left-auto md:w-[340px]"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
                <div class="text-[11px] leading-tight text-gray-500">
                    Tap <span class="font-bold text-gray-800">Share icon</span> then
                    <span class="font-bold text-gray-800">"Add to Home Screen"</span>.
                </div>
            </div>
            <button
                type="button"
                @click="promptDismissed = true"
                class="shrink-0 rounded p-1 text-gray-400 hover:text-gray-600"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        
        <div
            x-show="installed && !dismissed"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed right-4 bottom-4 left-4 z-50 flex items-center justify-between gap-3 rounded-xl border border-green-100 bg-green-50 py-2.5 pr-2 pl-3.5 shadow-lg md:right-6 md:bottom-6 md:left-auto md:w-[340px]"
        >
            <div class="flex items-center gap-2.5">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-500 text-white">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-green-900">App successfully installed!</p>
                </div>
            </div>
            <button
                type="button"
                @click="dismissInstalledPopup()"
                class="rounded-lg p-1 text-green-500 transition-colors hover:text-green-700"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('pwa'); ?>
    <link rel="manifest" href="<?php echo e(tenant_url('manifest.json')); ?>" />
    <meta name="theme-color" content="#82cd47" />
    <link class="apple-touch-icon" href="<?php echo e(asset('assets/icons/favicon.png')); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        function pwaInstall() {
            return {
                installed: false,
                installable: false,
                isIOS: false,
                deferredPrompt: null,
                dismissed: localStorage.getItem("plantiq_pwa_dismissed") === "true",
                promptDismissed: false, // Component memory only, resets on window reload

                init() {
                    const standalone =
                        window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;

                    if (standalone) {
                        this.installed = true;
                    }

                    const ua = window.navigator.userAgent;
                    this.isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;

                    window.addEventListener("beforeinstallprompt", (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        this.installable = true;
                    });

                    window.addEventListener("appinstalled", () => {
                        this.installed = true;
                        this.installable = false;
                        this.deferredPrompt = null;
                    });

                    if ("serviceWorker" in navigator) {
                        navigator.serviceWorker
                            .register("/sw.js", {
                                scope: "<?php echo e(tenant_mode() === 'slug' && tenant() ? '/' . tenant()->slug . '/' : '/'); ?>",
                            })
                            .catch(() => {});
                    }
                },

                async install() {
                    if (!this.deferredPrompt) return;
                    this.deferredPrompt.prompt();
                    await this.deferredPrompt.userChoice;
                    this.deferredPrompt = null;
                    this.installable = false;
                },

                dismissInstalledPopup() {
                    localStorage.setItem("plantiq_pwa_dismissed", "true");
                    this.dismissed = true;
                },
            };
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/auth/login.blade.php ENDPATH**/ ?>