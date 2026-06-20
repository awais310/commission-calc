import { ref } from 'vue';
import axios from 'axios';

export default function useFormulas() {
    const formulas        = ref([]);
    const loading         = ref(false);
    const saving          = ref(false);
    const validating      = ref(false);
    const validationResult = ref(null);
    const error           = ref(null);

    function extractError(err) {
        return (
            err?.response?.data?.message ||
            err?.response?.data?.error   ||
            err?.message                 ||
            'An unexpected error occurred.'
        );
    }

    async function fetchFormulas() {
        loading.value = true;
        error.value   = null;
        try {
            const { data } = await axios.get('/api/formulas');
            formulas.value = data;
        } catch (err) {
            error.value = extractError(err);
        } finally {
            loading.value = false;
        }
    }

    async function validateFormula(payload) {
        validating.value      = true;
        validationResult.value = null;
        error.value           = null;
        try {
            const { data } = await axios.post('/api/formulas/validate', {
                expression: payload.expression,
                variables:  payload.variables ?? [],
            });
            validationResult.value = data;
        } catch (err) {
            error.value = extractError(err);
        } finally {
            validating.value = false;
        }
    }

    async function saveFormula(payload) {
        saving.value = true;
        error.value  = null;
        try {
            const { data } = await axios.post('/api/formulas', payload);
            formulas.value.unshift(data);
            return data;
        } catch (err) {
            error.value = extractError(err);
            return null;
        } finally {
            saving.value = false;
        }
    }

    async function activateFormula(id) {
        error.value = null;
        try {
            const { data } = await axios.post(`/api/formulas/${id}/activate`);
            formulas.value = formulas.value.map(f => ({
                ...f,
                is_active: f.id === id,
                status:    f.id === id ? 'active' : (f.is_active ? 'archived' : f.status),
            }));
            return data;
        } catch (err) {
            error.value = extractError(err);
            return null;
        }
    }

    return {
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
    };
}
