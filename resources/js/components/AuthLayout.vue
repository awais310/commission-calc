<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md">

            <!-- Brand mark -->
            <div class="mb-8 flex flex-col items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-blue shadow-sm">
                    <svg class="h-7 w-7 text-brand-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <path d="M9 9h1m5 0h1M9 12h6M9 15h4"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight text-brand-blue">
                    Calc<span class="text-brand-orange">Commission</span>
                </span>
            </div>

            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-brand-blue">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>

            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>
