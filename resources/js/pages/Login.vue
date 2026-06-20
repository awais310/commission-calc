<template>
    <AuthLayout
        title="Welcome back"
        subtitle="Sign in to manage your commission formulas"
    >
        <form
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            @submit.prevent="handleSubmit"
        >
            <div>
                <label for="email" class="block text-sm font-medium text-brand-blue">Email address</label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="mt-1.5 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue placeholder:text-slate-400 focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                />
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-brand-blue">Password</label>
                    <a href="#" class="text-sm font-medium text-brand-orange hover:underline">Forgot password?</a>
                </div>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-1.5 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                />
            </div>

            <button
                type="submit"
                class="mt-6 w-full rounded-lg bg-brand-orange py-3 text-sm font-semibold text-white transition hover:opacity-90"
            >
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Don't have an account?
            <router-link to="/signup" class="font-medium text-brand-orange hover:underline">
                Create one free
            </router-link>
        </p>
    </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from '../components/AuthLayout.vue';

const router   = useRouter();
const email    = ref('');
const password = ref('');

function handleSubmit() {
    router.push('/formulas');
}
</script>
