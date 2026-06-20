<template>
    <AuthLayout
        title="Create your account"
        subtitle="Start building and versioning commission formulas"
    >
        <form
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            @submit.prevent="handleSubmit"
        >
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-brand-blue">First name</label>
                    <input
                        id="firstName"
                        v-model="firstName"
                        type="text"
                        autocomplete="given-name"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                    />
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-brand-blue">Last name</label>
                    <input
                        id="lastName"
                        v-model="lastName"
                        type="text"
                        autocomplete="family-name"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                    />
                </div>
            </div>

            <div class="mt-4">
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
                <label for="password" class="block text-sm font-medium text-brand-blue">Password</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    autocomplete="new-password"
                    class="mt-1.5 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                />
                <p class="mt-1 text-xs text-slate-400">Minimum 8 characters</p>
            </div>

            <label class="mt-5 flex cursor-pointer items-start gap-2.5">
                <input
                    v-model="agreedToTerms"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-orange focus:ring-brand-orange/20"
                />
                <span class="text-sm text-slate-500">
                    I agree to the
                    <a href="#" class="font-medium text-brand-orange hover:underline">Terms of Service</a>
                    and
                    <a href="#" class="font-medium text-brand-orange hover:underline">Privacy Policy</a>
                </span>
            </label>

            <button
                type="submit"
                :disabled="!agreedToTerms"
                class="mt-6 w-full rounded-lg bg-brand-orange py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Already have an account?
            <router-link to="/login" class="font-medium text-brand-orange hover:underline">
                Sign in
            </router-link>
        </p>
    </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from '../components/AuthLayout.vue';

const router       = useRouter();
const firstName    = ref('');
const lastName     = ref('');
const email        = ref('');
const password     = ref('');
const agreedToTerms = ref(false);

function handleSubmit() {
    router.push('/formulas');
}
</script>
