<?php

namespace App\Services;

use App\Models\CommissionFormula;
use App\Models\FormulaVariable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormulaService
{
    private const BASE_VARIABLES = ['AnnualUsage', 'ContractValue', 'ContractLength', 'RiskScore'];

    private const SAFE_EXPR_PATTERN = '/^[a-zA-Z0-9\s\+\-\*\/\(\)\._]+$/';

    private const VARIABLE_PATTERN = '/\b([A-Z][a-zA-Z0-9]*)\b/';

    private const TEST_VALUES = [
        'AnnualUsage'    => 1000,
        'ContractValue'  => 5000,
        'ContractLength' => 12,
        'RiskScore'      => 20,
    ];

    public function validate(array $data): array
    {
        $errors      = [];
        $fieldErrors = [];   // structured: [{field, variable_name, message}]
        $expression   = $data['expression'];
        $subVariables = $data['variables'] ?? [];

        $addError = function (string $field, ?string $variable, string $message) use (&$errors, &$fieldErrors) {
            $errors[]      = $message;
            $fieldErrors[] = ['field' => $field, 'variable_name' => $variable, 'message' => $message];
        };

        // Index sub-variables by name for easy lookup
        $subVarMap = [];
        foreach ($subVariables as $v) {
            $subVarMap[$v['variable_name']] = $v['expression'];
        }

        // 1. Check main expression contains only safe characters
        if (!preg_match(self::SAFE_EXPR_PATTERN, $expression)) {
            $addError('expression', null, 'Main expression contains invalid characters. Only alphanumeric, spaces, and operators (+, -, *, /, (, ), ., _) are allowed.');
        }

        // 1b. Bracket balance check on main expression
        $bracketErr = $this->checkBrackets($expression);
        if ($bracketErr !== null) {
            $addError('expression', null, $bracketErr);
        }

        // 1c. Basic syntax checks on main expression
        foreach ($this->syntaxChecks($expression) as $syntaxErr) {
            $addError('expression', null, $syntaxErr);
        }

        // 2. Extract variable names referenced in main expression
        preg_match_all(self::VARIABLE_PATTERN, $expression, $matches);
        $variablesUsed = array_unique($matches[1]);

        // 3. Check all referenced names are in base set or defined as sub-variable
        foreach ($variablesUsed as $varName) {
            if (!in_array($varName, self::BASE_VARIABLES) && !isset($subVarMap[$varName])) {
                $addError('expression', null, "'{$varName}' is used in the main expression but is not a base variable and not defined as a sub-variable.");
            }
        }

        // 4. Validate each sub-variable expression and build dependency graph
        $depGraph = [];
        foreach ($subVarMap as $name => $expr) {
            if (!preg_match(self::SAFE_EXPR_PATTERN, $expr)) {
                $addError('variable', $name, "Invalid characters in the expression. Only alphanumeric, spaces, and operators are allowed.");
            }

            // Bracket check per sub-variable
            $subBracketErr = $this->checkBrackets($expr);
            if ($subBracketErr !== null) {
                $addError('variable', $name, $subBracketErr);
            }

            // Basic syntax per sub-variable
            foreach ($this->syntaxChecks($expr) as $syntaxErr) {
                $addError('variable', $name, $syntaxErr);
            }

            preg_match_all(self::VARIABLE_PATTERN, $expr, $subMatches);
            $deps = array_unique($subMatches[1]);
            foreach ($deps as $dep) {
                if (!in_array($dep, self::BASE_VARIABLES) && !isset($subVarMap[$dep])) {
                    $addError('variable', $name, "References '{$dep}' which is not defined. Check the name and casing (e.g. BaseCommission not Basecommission).");
                }
            }
            $depGraph[$name] = array_values(array_filter($deps, fn($d) => isset($subVarMap[$d])));
        }

        // 5. Topological sort + cycle detection
        $executionOrder = [];

        if (!empty($depGraph)) {
            $result = $this->topologicalSort($depGraph);
            if ($result['cycle']) {
                $msg = "Circular dependency between '{$result['x']}' and '{$result['y']}' — they reference each other.";
                $errors[]      = $msg;
                $fieldErrors[] = ['field' => 'circular', 'variable_name' => $result['x'], 'message' => "Circular dependency with '{$result['y']}'."];
                $fieldErrors[] = ['field' => 'circular', 'variable_name' => $result['y'], 'message' => "Circular dependency with '{$result['x']}'."];
            } else {
                $executionOrder = $result['sorted'];
            }
        }

        // 6. Evaluate with test values only when all structural checks passed
        $testResult = null;
        if (empty($errors)) {
            try {
                $testVars = self::TEST_VALUES;
                foreach ($executionOrder as $varName) {
                    $testVars[$varName] = $this->evaluateExpression($subVarMap[$varName], $testVars);
                }
                foreach ($subVarMap as $name => $expr) {
                    if (!isset($testVars[$name])) {
                        $testVars[$name] = $this->evaluateExpression($expr, $testVars);
                    }
                }
                $testResult = $this->evaluateExpression($expression, $testVars);
            } catch (\DivisionByZeroError $e) {
                $addError('expression', null, 'Division by zero — check your divisors with the sample values (AnnualUsage=1000, ContractValue=5000, ContractLength=12, RiskScore=20).');
            } catch (\Throwable $e) {
                $addError('expression', null, $this->friendlyEvalError($e->getMessage()));
            }
        }

        return [
            'valid'           => empty($errors),
            'errors'          => $errors,
            'field_errors'    => $fieldErrors,
            'variables_used'  => array_values($variablesUsed),
            'execution_order' => $executionOrder,
            'test_result'     => $testResult,
        ];
    }

    public function store(array $data, ?int $userId): CommissionFormula
    {
        $validation = $this->validate($data);

        if (!$validation['valid']) {
            throw ValidationException::withMessages([
                'expression' => $validation['errors'],
            ]);
        }

        return DB::transaction(function () use ($data, $userId, $validation) {
            $nextVersion = (CommissionFormula::max('version') ?? 0) + 1;

            $subVarMap = [];
            foreach ($data['variables'] ?? [] as $v) {
                $subVarMap[$v['variable_name']] = $v['expression'];
            }

            $formula = CommissionFormula::create([
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'version'        => $nextVersion,
                'expression'     => $data['expression'],
                'variables_used' => $validation['variables_used'],
                'is_active'      => false,
                'status'         => 'validated',
                'created_by'     => $userId,
            ]);

            $executionOrder = $validation['execution_order'];

            // Create variables in topological order; append any that had no deps
            $orderedVars = $executionOrder;
            foreach (array_keys($subVarMap) as $name) {
                if (!in_array($name, $orderedVars)) {
                    $orderedVars[] = $name;
                }
            }

            foreach ($orderedVars as $position => $varName) {
                $expr = $subVarMap[$varName];

                // Build depends_on for this variable's expression
                preg_match_all(self::VARIABLE_PATTERN, $expr, $matches);
                $deps = array_values(array_filter(
                    array_unique($matches[1]),
                    fn($d) => isset($subVarMap[$d])
                ));

                FormulaVariable::create([
                    'formula_id'      => $formula->id,
                    'variable_name'   => $varName,
                    'expression'      => $expr,
                    'variable_type'   => 'calculated',
                    'depends_on'      => $deps ?: null,
                    'execution_order' => $position,
                ]);
            }

            return $formula->load('variables');
        });
    }

    public function activate(CommissionFormula $formula): CommissionFormula
    {
        return DB::transaction(function () use ($formula) {
            CommissionFormula::where('id', '!=', $formula->id)->update([
                'is_active'   => false,
                'status'      => 'archived',
                'archived_at' => now(),
            ]);

            $formula->update([
                'is_active'    => true,
                'status'       => 'active',
                'activated_at' => now(),
            ]);

            return $formula->fresh('variables');
        });
    }

    private function topologicalSort(array $depGraph): array
    {
        $inDegree = array_fill_keys(array_keys($depGraph), 0);
        $adjList  = array_fill_keys(array_keys($depGraph), []);

        foreach ($depGraph as $node => $deps) {
            foreach ($deps as $dep) {
                if (isset($adjList[$dep])) {
                    $adjList[$dep][] = $node;
                    $inDegree[$node]++;
                }
            }
        }

        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }

        $sorted = [];
        while (!empty($queue)) {
            $current  = array_shift($queue);
            $sorted[] = $current;
            foreach ($adjList[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        if (count($sorted) !== count($depGraph)) {
            // Find the first two nodes still involved in a cycle
            $remaining = array_values(array_diff(array_keys($depGraph), $sorted));
            $x = $remaining[0];
            // Find a dep of x that is also remaining (forms the direct cycle pair)
            $y = $remaining[1] ?? $x;
            foreach ($depGraph[$x] as $dep) {
                if (in_array($dep, $remaining)) {
                    $y = $dep;
                    break;
                }
            }
            return ['cycle' => true, 'x' => $x, 'y' => $y];
        }

        return ['cycle' => false, 'sorted' => $sorted];
    }

    private function evaluateExpression(string $expression, array $variables): float|int
    {
        uksort($variables, fn($a, $b) => strlen($b) - strlen($a));

        $expr = $expression;
        foreach ($variables as $name => $value) {
            $expr = preg_replace('/\b' . preg_quote($name, '/') . '\b/', (string) $value, $expr);
        }

        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', trim($expr))) {
            throw new \RuntimeException("Unresolved variable in expression after substitution.");
        }

        return eval("return {$expr};");
    }

    private function checkBrackets(string $expression): ?string
    {
        $depth      = 0;
        $openStack  = [];
        $len        = strlen($expression);

        for ($i = 0; $i < $len; $i++) {
            $ch = $expression[$i];
            if ($ch === '(') {
                $depth++;
                $openStack[] = $i + 1; // 1-based position
            } elseif ($ch === ')') {
                if ($depth === 0) {
                    return "Unexpected closing ')' at position {$i} — there is no matching '(' for it.";
                }
                $depth--;
                array_pop($openStack);
            }
        }

        if ($depth > 0) {
            $positions = implode(', ', $openStack);
            return $depth === 1
                ? "Missing closing ')' — an opening '(' at position {$positions} was never closed."
                : "Missing {$depth} closing ')' brackets — unclosed '(' at positions: {$positions}.";
        }

        return null;
    }

    private function syntaxChecks(string $expression): array
    {
        $errors = [];
        $expr   = trim($expression);

        if ($expr === '') {
            $errors[] = 'Expression is empty.';
            return $errors;
        }

        // Consecutive operators (e.g. * *, + -, but allow unary minus like *-1)
        if (preg_match('/[\+\*\/]{2,}|[\+\*\/]\s*[\+\*\/]/', $expr)) {
            $errors[] = 'Consecutive operators detected (e.g. "* *" or "+ /") — check for double operators.';
        }

        // Starts with an operator other than minus (unary minus is ok)
        if (preg_match('/^[\+\*\/]/', $expr)) {
            $errors[] = 'Expression starts with an operator — it should start with a number, variable, or "(".';
        }

        // Ends with an operator
        if (preg_match('/[\+\-\*\/]\s*$/', $expr)) {
            $errors[] = 'Expression ends with an operator — add the right-hand side value or remove the trailing operator.';
        }

        // Division by literal zero
        if (preg_match('/\/\s*0(?!\.\d)/', $expr)) {
            $errors[] = 'Division by zero detected (e.g. "/ 0") — check your divisors.';
        }

        return $errors;
    }

    private function friendlyEvalError(string $raw): string
    {
        $raw = preg_replace('/syntax error, unexpected token[^,]*/', 'syntax error — unexpected token', $raw) ?? $raw;
        $raw = preg_replace('/syntax error, unexpected end of file.*/', 'syntax error — expression is incomplete.', $raw) ?? $raw;
        $raw = str_replace(['";"', '")"', '"("'], ['";"', '")"', '"("'], $raw);
        return 'Could not evaluate expression: ' . rtrim($raw, '.') . '. Check your brackets, operators, and variable names.';
    }
}
