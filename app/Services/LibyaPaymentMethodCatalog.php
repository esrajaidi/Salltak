<?php

namespace App\Services;

use App\Models\PaymentMethod;

class LibyaPaymentMethodCatalog
{
    /**
     * Install/refresh Libya payment definitions without wiping merchant-entered
     * values. Incomplete active methods are automatically disabled so a stale
     * catalog/config change can never expose an unusable payment option.
     *
     * @return array{created:int,updated:int,disabled:int,total:int}
     */
    public function sync(): array
    {
        $created = 0;
        $updated = 0;
        $disabled = 0;
        $methods = config('libya_payment_methods.methods', []);

        foreach ($methods as $definition) {
            $method = PaymentMethod::query()->where('code', $definition['code'])->first();
            $defaultConfig = $definition['config'] ?? [];

            if (! $method) {
                PaymentMethod::create([
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'is_active' => false,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'min_amount' => null,
                    'max_amount' => null,
                    'fee_type' => 'none',
                    'fee_value' => 0,
                    'instructions' => $definition['instructions'] ?? null,
                    'config' => $defaultConfig,
                ]);
                $created++;
                continue;
            }

            // Existing merchant values win over catalog defaults. This keeps
            // entered IBANs, wallet IDs, Merchant IDs and encrypted secrets.
            $mergedConfig = array_replace($defaultConfig, $method->config ?? []);
            $changes = [];
            if (($method->config ?? []) !== $mergedConfig) {
                $changes['config'] = $mergedConfig;
            }
            if ($method->type !== $definition['type']) {
                $changes['type'] = $definition['type'];
            }
            if (! $method->instructions) {
                $changes['instructions'] = $definition['instructions'] ?? null;
            }
            if ((int) $method->sort_order === 0 && isset($definition['sort_order'])) {
                $changes['sort_order'] = $definition['sort_order'];
            }

            if ($changes) {
                $method->update($changes);
                $updated++;
            }

            $method->refresh();
            if ($method->is_active && ! $method->isConfiguredForActivation()) {
                $method->update(['is_active' => false]);
                $disabled++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'disabled' => $disabled, 'total' => count($methods)];
    }
}
