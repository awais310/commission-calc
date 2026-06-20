<template>
    <!-- Toast notifications -->
    <TransitionGroup
        name="toast"
        tag="div"
        class="fixed bottom-4 right-4 z-50 flex w-80 flex-col gap-2"
    >
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-lg"
            :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        >
            <span class="flex-1">{{ toast.message }}</span>
            <button
                @click="removeToast(toast.id)"
                class="shrink-0 opacity-80 transition hover:opacity-100"
            >✕</button>
        </div>
    </TransitionGroup>

    <!-- Page -->
    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_420px] lg:items-start">

                <!-- ── LEFT COLUMN: builder form ── -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <!-- Section header -->
                    <div class="mb-7">
                        <h1 class="text-2xl font-bold text-brand-blue">Commission formulas</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Create and version formula expressions for energy broker commission.
                        </p>
                    </div>

                    <!-- Name -->
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-brand-blue">
                            Formula name
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Q3 2025 Energy Commission"
                            class="mt-1.5 w-full rounded-lg border px-4 py-2.5 text-brand-blue placeholder:text-slate-400 focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                            :class="frontendErrors.name ? 'border-red-400' : 'border-slate-200'"
                        />
                        <p v-if="frontendErrors.name" class="mt-1 text-xs text-red-500">
                            {{ frontendErrors.name }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-brand-blue">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="What changed from the previous version?"
                            class="mt-1.5 w-full resize-none rounded-lg border border-slate-200 px-4 py-2.5 text-brand-blue placeholder:text-slate-400 focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                        />
                    </div>

                    <!-- Expression builder -->
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-brand-blue">
                            Main expression
                        </label>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Use base variables or sub-variables — e.g. <span class="font-mono">(AnnualUsage * 0.05) + (ContractLength * 100)</span>
                        </p>
                        <textarea
                            ref="expressionRef"
                            v-model="form.expression"
                            rows="3"
                            placeholder="(AnnualUsage * 0.05) + (ContractLength * 100)"
                            class="mt-1.5 w-full resize-none rounded-lg border px-4 py-2.5 font-mono text-sm text-brand-blue placeholder:text-slate-400 focus:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/20"
                            :class="frontendErrors.expression || expressionFieldErrors.length ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-slate-200'"
                            @mouseup="trackCursor"
                            @keyup="trackCursor"
                        />
                        <p v-if="frontendErrors.expression" class="mt-1 text-xs text-red-500">
                            {{ frontendErrors.expression }}
                        </p>
                        <!-- Inline errors targeting the main expression -->
                        <div v-if="expressionFieldErrors.length" class="mt-1.5 space-y-1">
                            <p
                                v-for="(fe, i) in expressionFieldErrors"
                                :key="i"
                                class="flex items-start gap-1.5 text-xs text-red-600"
                            >
                                <span class="mt-px shrink-0 font-bold">⚠</span>
                                <span>{{ fe.message }}</span>
                            </p>
                        </div>

                        <!-- Variable chips — base vars + any defined sub-variables -->
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <button
                                v-for="v in BASE_VARIABLES"
                                :key="v"
                                type="button"
                                @click="appendVariable(v)"
                                class="rounded-md border border-brand-blue/20 bg-brand-blue/5 px-2.5 py-1 font-mono text-xs font-medium text-brand-blue transition hover:border-brand-orange/40 hover:bg-brand-orange/5 hover:text-brand-orange"
                            >{{ v }}</button>

                            <template v-if="definedSubVarNames.length">
                                <span class="text-slate-300">|</span>
                                <button
                                    v-for="v in definedSubVarNames"
                                    :key="v"
                                    type="button"
                                    @click="appendVariable(v)"
                                    class="rounded-md border border-brand-orange/30 bg-brand-orange/5 px-2.5 py-1 font-mono text-xs font-medium text-brand-orange transition hover:border-brand-orange/60 hover:bg-brand-orange/10"
                                >{{ v }}</button>
                            </template>
                        </div>

                        <!-- Char count -->
                        <p class="mt-1 text-right text-xs" :class="form.expression.length > 1900 ? 'text-amber-500' : 'text-slate-400'">
                            {{ form.expression.length }}/2000
                        </p>
                    </div>

                    <!-- Sub-variables -->
                    <div class="mb-6">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="text-sm font-medium text-brand-blue">Sub-variables</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">
                                optional
                            </span>
                        </div>
                        <p class="mb-3 text-xs text-slate-500">
                            Define intermediate values to chain calculations — e.g.
                            <span class="font-mono">BaseCommission = AnnualUsage * 0.05</span>
                        </p>

                        <TransitionGroup name="var-row" tag="div" class="space-y-2">
                            <div
                                v-for="(variable, i) in form.variables"
                                :key="variable._key"
                            >
                                <div class="flex items-center gap-2">
                                    <!-- Variable name -->
                                    <input
                                        v-model="variable.variable_name"
                                        @input="enforceUpperStart(i)"
                                        placeholder="VarName"
                                        class="w-36 shrink-0 rounded-lg border px-3 py-2 font-mono text-sm text-brand-blue placeholder:text-slate-400 focus:outline-none focus:ring-2"
                                        :class="variableFieldErrors.has(variable.variable_name)
                                            ? 'border-red-400 bg-red-50/40 focus:border-red-400 focus:ring-red-400/20'
                                            : 'border-slate-200 focus:border-brand-orange focus:ring-brand-orange/20'"
                                    />
                                    <span class="shrink-0 text-sm text-slate-400">=</span>
                                    <!-- Expression with ⚠ icon overlay when errored -->
                                    <div class="relative min-w-0 flex-1">
                                        <input
                                            :ref="el => { if (el) subExprRefs[i] = el }"
                                            v-model="variable.expression"
                                            placeholder="AnnualUsage * 0.05"
                                            class="w-full rounded-lg border px-3 py-2 font-mono text-sm text-brand-blue placeholder:text-slate-400 focus:outline-none focus:ring-2"
                                            :class="variableFieldErrors.has(variable.variable_name)
                                                ? 'border-red-400 bg-red-50/40 pr-8 focus:border-red-400 focus:ring-red-400/20'
                                                : 'border-slate-200 focus:border-brand-orange focus:ring-brand-orange/20'"
                                            @mouseup="trackSubCursor(i)"
                                            @keyup="trackSubCursor(i)"
                                        />
                                        <!-- ⚠ icon with tooltip -->
                                        <div
                                            v-if="variableFieldErrors.has(variable.variable_name)"
                                            class="group absolute right-2 top-1/2 -translate-y-1/2"
                                        >
                                            <div class="flex h-5 w-5 cursor-help items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                                !
                                            </div>
                                            <!-- Tooltip -->
                                            <div class="tooltip-content pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-64 rounded-lg bg-gray-900 px-3 py-2 text-xs leading-relaxed text-white shadow-xl group-hover:block">
                                                <span class="font-semibold text-red-300">{{ variable.variable_name }}</span><br/>
                                                {{ variableFieldErrors.get(variable.variable_name) }}
                                                <!-- Arrow -->
                                                <div class="absolute right-2 top-full border-4 border-transparent border-t-gray-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeVariable(i)"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-500"
                                        title="Remove variable"
                                    >✕</button>
                                </div>
                                <!-- Reference chips for this sub-variable's expression -->
                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                    <span class="mr-0.5 text-xs text-slate-400">Insert:</span>
                                    <button
                                        v-for="chip in availableVarsFor(i)"
                                        :key="chip"
                                        type="button"
                                        @click="appendToSubVar(i, chip)"
                                        class="rounded border px-2 py-0.5 font-mono text-xs transition"
                                        :class="BASE_VARIABLES.includes(chip)
                                            ? 'border-brand-blue/20 bg-brand-blue/5 text-brand-blue hover:border-brand-orange/40 hover:bg-brand-orange/5 hover:text-brand-orange'
                                            : 'border-brand-orange/30 bg-brand-orange/5 text-brand-orange hover:border-brand-orange/60 hover:bg-brand-orange/10'"
                                    >{{ chip }}</button>
                                </div>

                                <!-- Inline error text below the row -->
                                <p
                                    v-if="variableFieldErrors.has(variable.variable_name)"
                                    class="mt-1 flex items-start gap-1.5 rounded-md bg-red-50 px-2.5 py-1.5 text-xs text-red-700"
                                >
                                    <span class="mt-px shrink-0">⚠</span>
                                    <span>{{ variableFieldErrors.get(variable.variable_name) }}</span>
                                </p>
                            </div>
                        </TransitionGroup>

                        <p v-if="form.variables.length > 0" class="mt-1 text-xs text-slate-400">
                            Names are auto-formatted to camelCase — e.g. "base commission" → <span class="font-mono">BaseCommission</span>
                        </p>

                        <div class="mt-3">
                            <button
                                v-if="form.variables.length < 8"
                                type="button"
                                @click="addVariable"
                                class="rounded-lg border border-dashed border-slate-300 px-4 py-2 text-sm text-slate-500 transition hover:border-brand-orange/50 hover:text-brand-orange"
                            >+ Add sub-variable</button>
                            <p v-else class="text-xs text-slate-400">Maximum 8 sub-variables reached.</p>
                        </div>
                    </div>

                    <!-- Validation result panel -->
                    <Transition name="slide-fade">
                        <div
                            v-if="validationResult"
                            class="mb-5 rounded-xl border p-4"
                            :class="validationResult.valid
                                ? 'border-green-200 bg-green-50'
                                : 'border-red-200 bg-red-50'"
                        >
                            <template v-if="validationResult.valid">
                                <div class="flex items-center gap-2">
                                    <span class="text-base leading-none text-green-600">✓</span>
                                    <p class="font-semibold text-green-800">Expression is valid</p>
                                </div>
                                <p class="mt-2 text-sm text-green-700">
                                    Sample result:
                                    <span class="font-mono font-semibold">
                                        {{ formatCurrency(validationResult.test_result) }}
                                    </span>
                                </p>
                                <p class="mt-1.5 text-xs text-green-600">
                                    Variables detected:
                                    <span class="font-mono">
                                        {{ validationResult.variables_used.join(', ') || '—' }}
                                    </span>
                                </p>
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">!</span>
                                    <p class="font-semibold text-red-800">
                                        {{ validationResult.errors.length }} issue{{ validationResult.errors.length !== 1 ? 's' : '' }} found
                                    </p>
                                </div>
                                <ul class="mt-3 space-y-2">
                                    <li
                                        v-for="(fe, i) in validationResult.field_errors"
                                        :key="i"
                                        class="rounded-lg border border-red-200 bg-white px-3 py-2.5 text-xs"
                                    >
                                        <!-- Location badge -->
                                        <div class="mb-1 flex items-center gap-1.5">
                                            <span class="rounded bg-red-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-red-700">
                                                {{ fe.variable_name ? fe.variable_name : 'Main expression' }}
                                            </span>
                                            <span class="text-slate-400">
                                                {{ fe.field === 'circular' ? '↻ circular' : fe.field === 'expression' ? '→ expression' : '→ sub-variable' }}
                                            </span>
                                        </div>
                                        <!-- Error message -->
                                        <p class="text-red-700">{{ fe.message }}</p>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </Transition>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            @click="handleValidate"
                            :disabled="validating"
                            class="flex items-center gap-2 rounded-lg border border-brand-blue/20 px-5 py-2.5 text-sm font-medium text-brand-blue transition hover:border-brand-blue/40 hover:bg-brand-blue/5 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg v-if="validating" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ validating ? 'Validating…' : 'Validate' }}
                        </button>

                        <button
                            type="button"
                            @click="handleSave"
                            :disabled="saving || !validationResult?.valid"
                            class="flex items-center gap-2 rounded-lg bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ saving ? 'Saving…' : 'Save formula' }}
                        </button>
                    </div>
                </div>

                <!-- ── RIGHT COLUMN: formula list ── -->
                <div
                    ref="formulasColumnRef"
                    class="lg:sticky lg:top-8 lg:max-h-[calc(100vh-4rem)] lg:overflow-y-auto lg:pr-1"
                >
                    <!-- List header -->
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-brand-blue">All versions</h2>
                        <span class="rounded-full bg-brand-blue/10 px-2.5 py-0.5 text-xs font-medium text-brand-blue">
                            {{ formulas.length }}
                        </span>
                    </div>

                    <!-- Loading -->
                    <div
                        v-if="loading"
                        class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400"
                    >
                        Loading formulas…
                    </div>

                    <!-- Empty state -->
                    <div
                        v-else-if="formulas.length === 0"
                        class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center"
                    >
                        <p class="text-3xl">📐</p>
                        <p class="mt-2 text-sm font-medium text-slate-600">No formulas yet</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Build your first commission formula using the editor.
                        </p>
                    </div>

                    <template v-else>

                        <!-- Active formula banner -->
                        <div
                            v-if="activeFormula"
                            class="mb-4 flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="w-1 shrink-0 bg-green-500"></div>
                            <div class="flex-1 p-4">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        Active
                                    </span>
                                    <span class="text-xs text-slate-400">v{{ activeFormula.version }}</span>
                                </div>
                                <p class="mt-1 truncate font-semibold text-brand-blue">
                                    {{ activeFormula.name }}
                                </p>
                                <p class="mt-1.5 truncate rounded-md bg-slate-50 px-2 py-1 font-mono text-xs text-slate-600">
                                    {{ activeFormula.expression }}
                                </p>
                                <p v-if="activeFormula.activated_at" class="mt-1.5 text-xs text-slate-400">
                                    Activated {{ formatDate(activeFormula.activated_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Other formula cards -->
                        <div class="space-y-3">
                            <div
                                v-for="formula in otherFormulas"
                                :key="formula.id"
                                class="flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                            >
                                <!-- Active accent (visible if somehow active and in this list) -->
                                <div
                                    class="w-1 shrink-0 transition-colors"
                                    :class="formula.is_active ? 'bg-green-500' : 'bg-transparent'"
                                ></div>

                                <div class="flex-1 p-4">
                                    <!-- Version + status -->
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">
                                            v{{ formula.version }}
                                        </span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                            :class="statusClass(formula.status)"
                                        >{{ formula.status }}</span>
                                    </div>

                                    <!-- Name -->
                                    <p class="mt-1.5 truncate text-sm font-semibold text-brand-blue">
                                        {{ formula.name }}
                                    </p>

                                    <!-- Expression preview -->
                                    <p class="mt-1 truncate rounded-md bg-slate-50 px-2 py-1 font-mono text-xs text-slate-500">
                                        {{ formula.expression }}
                                    </p>

                                    <!-- Date -->
                                    <p class="mt-1.5 text-xs text-slate-400">
                                        Created {{ formatDate(formula.created_at) }}
                                    </p>

                                    <!-- Activate flow -->
                                    <template v-if="canActivate(formula)">
                                        <!-- Confirmation step -->
                                        <div
                                            v-if="confirmingActivateId === formula.id"
                                            class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                                        >
                                            <p class="text-xs font-medium text-amber-800">
                                                Activate this formula? The current active formula will be archived.
                                            </p>
                                            <div class="mt-2 flex gap-2">
                                                <button
                                                    @click="confirmActivate(formula.id)"
                                                    class="rounded-lg bg-brand-orange px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
                                                >Confirm</button>
                                                <button
                                                    @click="cancelActivate"
                                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                                >Cancel</button>
                                            </div>
                                        </div>

                                        <!-- Activate trigger -->
                                        <button
                                            v-else
                                            @click="promptActivate(formula.id)"
                                            class="mt-3 w-full rounded-lg border border-brand-blue/20 py-1.5 text-xs font-medium text-brand-blue transition hover:border-brand-blue/40 hover:bg-brand-blue/5"
                                        >Activate</button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted } from 'vue';
import useFormulas from '../composables/useFormulas.js';

const BASE_VARIABLES = ['AnnualUsage', 'ContractValue', 'ContractLength', 'RiskScore'];

const {
    formulas,
    loading,
    saving,
    validating,
    validationResult,
    error,
    fetchFormulas,
    validateFormula,
    saveFormula,
    activateFormula,
} = useFormulas();

// ── Form state ──────────────────────────────────────────────
const form = reactive({
    name:        '',
    description: '',
    expression:  '',
    variables:   [],
});

const frontendErrors  = reactive({ name: '', expression: '' });
const expressionRef      = ref(null);
const formulasColumnRef  = ref(null);
const cursorPos          = ref(0);
const confirmingActivateId = ref(null);
const subExprRefs        = ref([]);   // per-row input element refs
const subVarCursors      = ref([]);   // per-row last cursor position

// ── Toasts ───────────────────────────────────────────────────
const toasts = ref([]);

function addToast(type, message) {
    const id = Date.now() + Math.random();
    toasts.value.push({ id, type, message });
    setTimeout(() => removeToast(id), 3000);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

// ── Computed ─────────────────────────────────────────────────
const activeFormula = computed(() => formulas.value.find(f => f.is_active) ?? null);

// Sub-variable names that are fully named — shown as chips in main expression and other sub-var rows
const definedSubVarNames = computed(() =>
    form.variables.map(v => v.variable_name).filter(n => n.trim().length > 0)
);

// Errors that target the main expression textarea
const expressionFieldErrors = computed(() => {
    if (!validationResult.value?.field_errors) return [];
    return validationResult.value.field_errors.filter(e => e.field === 'expression');
});

// Map of variable_name → combined error string for sub-variable rows
const variableFieldErrors = computed(() => {
    const map = new Map();
    if (!validationResult.value?.field_errors) return map;
    for (const e of validationResult.value.field_errors) {
        if ((e.field === 'variable' || e.field === 'circular') && e.variable_name) {
            const prev = map.get(e.variable_name);
            map.set(e.variable_name, prev ? `${prev}; ${e.message}` : e.message);
        }
    }
    return map;
});

const otherFormulas = computed(() =>
    [...formulas.value]
        .filter(f => !f.is_active)
        .sort((a, b) => b.id - a.id)
);

// ── Watchers ─────────────────────────────────────────────────
// Changing the expression or variables invalidates the prior validation
watch(
    [() => form.expression, () => form.variables],
    () => { validationResult.value = null; },
    { deep: true }
);

watch(() => form.name,       () => { frontendErrors.name = ''; });
watch(() => form.expression, () => { frontendErrors.expression = ''; });

// ── Sub-variable management ───────────────────────────────────
function addVariable() {
    if (form.variables.length >= 8) return;
    form.variables.push({ variable_name: '', expression: '', _key: Date.now() });
}

function removeVariable(index) {
    form.variables.splice(index, 1);
}

function enforceUpperStart(index) {
    const v = form.variables[index];
    if (!v.variable_name) return;
    // Convert to camelCase: capitalize letter after each space, strip spaces and disallowed chars
    v.variable_name = v.variable_name
        .replace(/[^a-zA-Z0-9\s]/g, '')          // strip non-alphanumeric (except spaces)
        .replace(/\s+([a-zA-Z0-9])/g, (_, c) => c.toUpperCase())  // space → camelCase
        .replace(/\s+/g, '')                       // strip any remaining spaces
        .replace(/^(.)/, c => c.toUpperCase());    // ensure first char is uppercase
}

// ── Expression chip insert ────────────────────────────────────
function trackCursor() {
    const ta = expressionRef.value;
    if (ta) cursorPos.value = ta.selectionStart;
}

function appendVariable(name) {
    const ta    = expressionRef.value;
    const start = ta ? (ta.selectionStart ?? cursorPos.value) : form.expression.length;
    const end   = ta ? (ta.selectionEnd   ?? start)           : start;

    const before = form.expression.slice(0, start);
    const after  = form.expression.slice(end);

    const prefix = before.length > 0 && !/[\s(]$/.test(before) ? ' ' : '';
    const suffix = after.length  > 0 && !/^[\s)+\-*/]/.test(after) ? ' ' : '';

    form.expression = before + prefix + name + suffix + after;

    nextTick(() => {
        if (ta) {
            ta.focus();
            const pos = start + prefix.length + name.length + suffix.length;
            ta.setSelectionRange(pos, pos);
            cursorPos.value = pos;
        }
    });
}

// ── Sub-variable expression chip insert ──────────────────────
function trackSubCursor(index) {
    const el = subExprRefs.value[index];
    if (el) subVarCursors.value[index] = el.selectionStart;
}

// Chips available inside a sub-variable: base vars + all OTHER named sub-vars
function availableVarsFor(index) {
    const others = form.variables
        .filter((_, i) => i !== index)
        .map(v => v.variable_name)
        .filter(n => n.trim().length > 0);
    return [...BASE_VARIABLES, ...others];
}

function appendToSubVar(index, name) {
    const el       = subExprRefs.value[index];
    const variable = form.variables[index];
    const start    = el ? (el.selectionStart ?? subVarCursors.value[index] ?? variable.expression.length) : variable.expression.length;
    const end      = el ? (el.selectionEnd   ?? start) : start;

    const before = variable.expression.slice(0, start);
    const after  = variable.expression.slice(end);
    const prefix = before.length > 0 && !/[\s(]$/.test(before) ? ' ' : '';
    const suffix = after.length  > 0 && !/^[\s)+\-*/]/.test(after) ? ' ' : '';

    variable.expression = before + prefix + name + suffix + after;

    nextTick(() => {
        if (el) {
            el.focus();
            const pos = start + prefix.length + name.length + suffix.length;
            el.setSelectionRange(pos, pos);
            subVarCursors.value[index] = pos;
        }
    });
}

// ── Validate ─────────────────────────────────────────────────
async function handleValidate() {
    if (!form.expression.trim()) {
        frontendErrors.expression = 'Expression is required.';
        return;
    }

    await validateFormula({
        expression: form.expression,
        variables:  activeSubVariables(),
    });
}

// ── Save ─────────────────────────────────────────────────────
async function handleSave() {
    let ok = true;
    if (!form.name.trim()) {
        frontendErrors.name = 'Formula name is required.';
        ok = false;
    }
    if (!form.expression.trim()) {
        frontendErrors.expression = 'Expression is required.';
        ok = false;
    }
    if (!ok) return;

    if (!validationResult.value?.valid) {
        addToast('error', 'Validate the expression before saving.');
        return;
    }

    const result = await saveFormula({
        name:        form.name.trim(),
        description: form.description.trim(),
        expression:  form.expression,
        variables:   activeSubVariables(),
    });

    if (result) {
        addToast('success', `Formula "${result.name}" saved.`);
        resetForm();
        nextTick(() => {
            formulasColumnRef.value?.scrollTo({ top: 0, behavior: 'smooth' });
        });
    } else {
        addToast('error', error.value || 'Failed to save formula.');
    }
}

function resetForm() {
    form.name        = '';
    form.description = '';
    form.expression  = '';
    form.variables   = [];
    validationResult.value = null;
}

function activeSubVariables() {
    return form.variables.filter(v => v.variable_name?.trim() && v.expression?.trim());
}

// ── Activate ─────────────────────────────────────────────────
function canActivate(formula) {
    return ['validated', 'draft', 'archived'].includes(formula.status) && !formula.is_active;
}

function promptActivate(id) {
    confirmingActivateId.value = id;
}

function cancelActivate() {
    confirmingActivateId.value = null;
}

async function confirmActivate(id) {
    const result = await activateFormula(id);
    confirmingActivateId.value = null;
    if (result) {
        addToast('success', 'Formula activated successfully.');
    } else {
        addToast('error', error.value || 'Failed to activate formula.');
    }
}

// ── Formatting helpers ────────────────────────────────────────
function formatCurrency(value) {
    if (value === null || value === undefined) return '—';
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}

function statusClass(status) {
    return {
        draft:     'bg-slate-100 text-slate-600',
        validated: 'bg-blue-100 text-blue-700',
        active:    'bg-green-100 text-green-700',
        archived:  'bg-slate-100 text-slate-400',
    }[status] ?? 'bg-slate-100 text-slate-600';
}

// ── Lifecycle ─────────────────────────────────────────────────
onMounted(fetchFormulas);
</script>

<style>
/* Tooltip show on group hover (Tailwind v4 safeguard) */
.group:hover .tooltip-content { display: block; }

/* Validation result panel */
.slide-fade-enter-active { transition: opacity 0.2s ease-out, transform 0.2s ease-out; }
.slide-fade-leave-active { transition: opacity 0.15s ease-in, transform 0.15s ease-in; }
.slide-fade-enter-from,
.slide-fade-leave-to    { opacity: 0; transform: translateY(-6px); }

/* Sub-variable rows */
.var-row-enter-active { transition: opacity 0.2s ease-out, transform 0.2s ease-out; }
.var-row-leave-active { transition: opacity 0.15s ease-in, transform 0.15s ease-in; position: absolute; width: 100%; }
.var-row-enter-from,
.var-row-leave-to     { opacity: 0; transform: translateX(-8px); }

/* Toast notifications */
.toast-enter-active { transition: opacity 0.25s ease-out, transform 0.25s ease-out; }
.toast-leave-active { transition: opacity 0.2s ease-in, transform 0.2s ease-in; }
.toast-enter-from,
.toast-leave-to     { opacity: 0; transform: translateX(1rem); }
</style>
