<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormulaRequest;
use App\Http\Requests\ValidateFormulaRequest;
use App\Models\CommissionFormula;
use App\Services\FormulaService;
use Illuminate\Http\JsonResponse;

class FormulaController extends Controller
{
    public function __construct(private readonly FormulaService $service) {}

    public function index(): JsonResponse
    {
        $formulas = CommissionFormula::with('variables')
            ->latest()
            ->get()
            ->map(fn($f) => $this->formatFormula($f));

        return response()->json($formulas);
    }

    public function show(CommissionFormula $formula): JsonResponse
    {
        return response()->json($this->formatFormula($formula->load('variables')));
    }

    public function validateFormula(ValidateFormulaRequest $request): JsonResponse
    {
        $result = $this->service->validate($request->validated());

        return response()->json([
            'valid'          => $result['valid'],
            'errors'         => $result['errors'],
            'field_errors'   => $result['field_errors'],
            'variables_used' => $result['variables_used'],
            'test_result'    => $result['test_result'],
        ]);
    }

    public function store(StoreFormulaRequest $request): JsonResponse
    {
        $formula = $this->service->store($request->validated(), auth()->id());

        return response()->json($this->formatFormula($formula), 201);
    }

    public function activate(CommissionFormula $formula): JsonResponse
    {
        $formula = $this->service->activate($formula);

        return response()->json($this->formatFormula($formula));
    }

    private function formatFormula(CommissionFormula $formula): array
    {
        return [
            'id'             => $formula->id,
            'name'           => $formula->name,
            'description'    => $formula->description,
            'version'        => $formula->version,
            'expression'     => $formula->expression,
            'variables_used' => $formula->variables_used,
            'is_active'      => $formula->is_active,
            'status'         => $formula->status,
            'activated_at'   => $formula->activated_at?->toISOString(),
            'created_at'     => $formula->created_at?->toISOString(),
            'variables'      => $formula->relationLoaded('variables')
                ? $formula->variables->map(fn($v) => [
                    'id'              => $v->id,
                    'variable_name'   => $v->variable_name,
                    'expression'      => $v->expression,
                    'variable_type'   => $v->variable_type,
                    'depends_on'      => $v->depends_on,
                    'execution_order' => $v->execution_order,
                ])->values()
                : [],
        ];
    }
}
