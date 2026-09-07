<?php

namespace App\Services;

class DepositCalculator
{
    public function calculate(float $total, iterable $rules, ?array $override = null): float
    {
        $total = max(0, $total);

        if ($override && isset($override['type'], $override['value'])) {
            return $this->amount($total, (string) $override['type'], (float) $override['value']);
        }

        $matched = null;
        foreach ($rules as $rule) {
            $r = is_array($rule) ? $rule : $rule->toArray();
            if (!($r['is_active'] ?? true)) {
                continue;
            }
            $min = (float) ($r['min_total'] ?? 0);
            $max = $r['max_total'] ?? null;
            if ($total >= $min && ($max === null || $max === '' || $total <= (float) $max)) {
                $matched = $r;
                break;
            }
        }

        if (!$matched) {
            return 0.0;
        }

        return $this->amount($total, (string) ($matched['type'] ?? 'percentage'), (float) ($matched['value'] ?? 0));
    }

    private function amount(float $total, string $type, float $value): float
    {
        $value = max(0, $value);
        $amount = $type === 'fixed' ? $value : ($total * min(100, $value) / 100);
        return round(min($total, $amount), 2);
    }
}
