<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code','name','type','is_active','sort_order','min_amount','max_amount',
        'fee_type','fee_value','instructions','config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'fee_value' => 'decimal:2',
            'config' => 'encrypted:array',
        ];
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function supports(float $amount): bool
    {
        return $this->is_active
            && ($this->min_amount === null || $amount >= (float) $this->min_amount)
            && ($this->max_amount === null || $amount <= (float) $this->max_amount);
    }

    public function feeFor(float $amount): float
    {
        if ($this->fee_type === 'fixed') {
            return round((float) $this->fee_value, 2);
        }
        if ($this->fee_type === 'percentage') {
            return round($amount * (float) $this->fee_value / 100, 2);
        }

        return 0.0;
    }

    public function proofMode(): string
    {
        $mode = (string) (($this->config ?? [])['proof_mode'] ?? 'reference_or_receipt');

        return in_array($mode, ['none','reference','receipt','reference_or_receipt'], true)
            ? $mode
            : 'reference_or_receipt';
    }

    public function integrationMode(): string
    {
        return (string) (($this->config ?? [])['integration_mode'] ?? 'manual_verification');
    }

    public function availability(): string
    {
        return (string) (($this->config ?? [])['availability'] ?? 'online');
    }

    public function catalogDefinition(): array
    {
        foreach (config('libya_payment_methods.methods', []) as $definition) {
            if (($definition['code'] ?? null) === $this->code) {
                return $definition;
            }
        }

        return [];
    }

    public function schemaDefinition(): array
    {
        $definition = $this->catalogDefinition();
        $schemaKey = $definition['schema'] ?? match ($this->type) {
            'bank' => 'bank_transfer',
            'wallet' => 'wallet',
            'api' => 'card_external',
            'cash' => 'cash',
            default => 'custom',
        };

        return config('libya_payment_methods.schemas.'.$schemaKey, config('libya_payment_methods.schemas.custom', []));
    }

    public function activationIssues(): array
    {
        $config = $this->config ?? [];
        $schema = $this->schemaDefinition();
        $activation = $schema['activation'] ?? [];
        $fields = $schema['fields'] ?? [];
        $issues = [];

        foreach (($activation['all_of'] ?? []) as $key) {
            if (! $this->hasConfigValue($key)) {
                $issues[] = 'أدخل '.($fields[$key]['label'] ?? $key).'.';
            }
        }

        foreach (($activation['any_of'] ?? []) as $keys) {
            $hasAny = false;
            foreach ($keys as $key) {
                if ($this->hasConfigValue($key)) {
                    $hasAny = true;
                    break;
                }
            }
            if (! $hasAny) {
                $labels = array_map(fn ($key) => $fields[$key]['label'] ?? $key, $keys);
                $issues[] = 'أدخل واحدًا على الأقل من: '.implode('، ', $labels).'.';
            }
        }

        $mode = $this->integrationMode();
        $allowedModes = array_keys($schema['modes'] ?? []);
        if ($allowedModes && ! in_array($mode, $allowedModes, true)) {
            $issues[] = 'وضع الربط الحالي لا يناسب هذه الطريقة.';
        }

        if ($mode === 'external_link' && empty($config['checkout_url'])) {
            $issues[] = 'رابط الدفع الخارجي مطلوب.';
        }

        if ($mode === 'partner_api') {
            // Salltak intentionally does not pretend that a provider connector
            // exists. A bank/licensed-partner connector must be implemented in
            // application code before this mode can be activated for customers.
            $issues[] = 'وضع API يحتاج موصل رسمي خاص بالمصرف/المزود قبل التفعيل.';
        }

        return array_values(array_unique($issues));
    }

    public function isConfiguredForActivation(): bool
    {
        return $this->activationIssues() === [];
    }

    public function canOfferForOrder(Order $order, float $amount): bool
    {
        if (! $this->supports($amount) || ! $this->isConfiguredForActivation()) {
            return false;
        }

        if ($this->integrationMode() === 'partner_api') {
            return false;
        }

        if ($this->availability() === 'delivery_only') {
            return in_array($order->status, ['arrived_libya','awaiting_balance','ready_for_delivery','out_for_delivery'], true);
        }

        return true;
    }

    public function customerDetails(): array
    {
        $config = $this->config ?? [];
        $fields = $this->schemaDefinition()['fields'] ?? [];
        $details = [];

        foreach ($fields as $key => $definition) {
            if (! ($definition['public'] ?? false)) {
                continue;
            }
            if (isset($config[$key]) && trim((string) $config[$key]) !== '') {
                $details[$key] = $config[$key];
            }
        }

        return $details;
    }

    public function customerDetailLabels(): array
    {
        $labels = [];
        foreach (($this->schemaDefinition()['fields'] ?? []) as $key => $definition) {
            if ($definition['public'] ?? false) {
                $labels[$key] = $definition['label'] ?? $key;
            }
        }

        return $labels;
    }

    public function externalPaymentUrl(): ?string
    {
        if ($this->integrationMode() !== 'external_link') {
            return null;
        }

        $url = trim((string) (($this->config ?? [])['checkout_url'] ?? ''));
        return $url !== '' ? $url : null;
    }

    public function qrImageUrl(): ?string
    {
        $url = trim((string) (($this->config ?? [])['qr_image_url'] ?? ''));
        return $url !== '' ? $url : null;
    }

    public function documentationStatus(): string
    {
        return (string) (($this->config ?? [])['documentation_status'] ?? 'custom');
    }

    private function hasConfigValue(string $key): bool
    {
        $value = ($this->config ?? [])[$key] ?? null;
        return $value !== null && trim((string) $value) !== '';
    }
}
