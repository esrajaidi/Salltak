<?php

namespace App\Services\CartImport;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SheinSessionVault
{
    private const TABLE = 'shein_account_sessions';

    public function info(): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return ['connected' => false, 'enabled' => false, 'updated_at' => null];
        }
        $row = DB::table(self::TABLE)->where('id', 1)->first();
        return [
            'connected' => $row !== null,
            'enabled' => (bool) ($row->is_enabled ?? false),
            'updated_at' => $row->updated_at ?? null,
        ];
    }

    public function read(bool $force = false): ?array
    {
        if (! Schema::hasTable(self::TABLE)) return null;
        $row = DB::table(self::TABLE)->where('id', 1)->first();
        if (! $row || (! $force && ! $row->is_enabled)) return null;

        try {
            $state = json_decode(Crypt::decryptString($row->encrypted_state), true, 512, JSON_THROW_ON_ERROR);
            return $this->clean($state);
        } catch (\Throwable) {
            // An invalid or expired session should not leak secrets or crash importing.
            return null;
        }
    }

    public function save(array $state, int $actorId): void
    {
        $clean = $this->clean($state);
        if ($clean['cookies'] === []) {
            throw ValidationException::withMessages([
                'storage_state' => 'الملف لا يحتوي على جلسة SHEIN صالحة. سجّل الدخول من جديد على جهازك.',
            ]);
        }

        DB::table(self::TABLE)->updateOrInsert(['id' => 1], [
            'encrypted_state' => Crypt::encryptString(json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            'is_enabled' => false, // Require a manual test before activation.
            'updated_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function enable(bool $enabled): void
    {
        if ($enabled && $this->read(true) === null) {
            throw ValidationException::withMessages(['storage_state' => 'ارفع جلسة SHEIN صالحة أولًا.']);
        }
        DB::table(self::TABLE)->where('id', 1)->update(['is_enabled' => $enabled, 'updated_at' => now()]);
    }

    public function delete(): void
    {
        if (Schema::hasTable(self::TABLE)) DB::table(self::TABLE)->where('id', 1)->delete();
    }

    private function clean(mixed $state): array
    {
        if (! is_array($state) || ! is_array($state['cookies'] ?? null)) {
            throw new \InvalidArgumentException('Invalid browser session format');
        }

        $cookies = [];
        foreach (array_slice($state['cookies'], 0, 250) as $cookie) {
            if (! is_array($cookie)) continue;
            $domain = strtolower(ltrim((string) ($cookie['domain'] ?? ''), '.'));
            $name = (string) ($cookie['name'] ?? '');
            $value = $cookie['value'] ?? '';
            $path = (string) ($cookie['path'] ?? '/');
            if (($domain !== 'shein.com' && ! str_ends_with($domain, '.shein.com'))
                || ! preg_match('/^[a-zA-Z0-9_\-]{1,150}$/', $name)
                || ! is_string($value) || strlen($value) > 12000
                || ! str_starts_with($path, '/') || strlen($path) > 150
                || empty($cookie['secure'])) continue;

            $expires = (float) ($cookie['expires'] ?? -1);
            if ($expires > 0 && $expires < time()) continue;
            $cleanCookie = [
                'name' => $name, 'value' => $value,
                'domain' => (string) $cookie['domain'],
                'path' => $path, 'expires' => $expires > 0 ? $expires : -1,
                'httpOnly' => (bool) ($cookie['httpOnly'] ?? false),
                'secure' => true,
                'sameSite' => in_array(($cookie['sameSite'] ?? ''), ['Strict','Lax','None'], true) ? $cookie['sameSite'] : 'Lax',
            ];
            $cookies[] = $cleanCookie;
        }

        $origins = [];
        foreach (array_slice($state['origins'] ?? [], 0, 15) as $entry) {
            if (! is_array($entry)) continue;
            $origin = (string) ($entry['origin'] ?? '');
            $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
            if (! str_starts_with($origin, 'https://')
                || ($host !== 'shein.com' && ! str_ends_with($host, '.shein.com'))) continue;
            $pairs = [];
            foreach (array_slice($entry['localStorage'] ?? [], 0, 100) as $item) {
                if (! is_array($item)) continue;
                $key = $item['name'] ?? '';
                $value = $item['value'] ?? '';
                if (is_string($key) && strlen($key) <= 200 && is_string($value) && strlen($value) <= 12000) {
                    $pairs[] = ['name' => $key, 'value' => $value];
                }
            }
            $origins[] = ['origin' => $origin, 'localStorage' => $pairs];
        }

        return ['cookies' => $cookies, 'origins' => $origins];
    }
}
