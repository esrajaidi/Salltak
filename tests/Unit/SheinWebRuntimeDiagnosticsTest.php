<?php

namespace Tests\Unit;

use App\Services\CartImport\Browser\SheinWebRuntimeDiagnostics;
use PHPUnit\Framework\TestCase;

final class SheinWebRuntimeDiagnosticsTest extends TestCase
{
    public function test_it_never_repeats_raw_node_stderr_or_sensitive_data(): void
    {
        $stderr = 'FATAL ERROR: Assertion failed. cookie=SECRET_VALUE '
            .'https://www.shein.com/ar/cart/share/landing?shc=SECRET_TOKEN';

        $this->assertSame('native_abort', SheinWebRuntimeDiagnostics::errorCategory($stderr));
        $this->assertSame('no_stderr', SheinWebRuntimeDiagnostics::errorCategory(''));
        $this->assertSame('module_unavailable', SheinWebRuntimeDiagnostics::errorCategory('ERR_MODULE_NOT_FOUND'));
        $this->assertSame('memory', SheinWebRuntimeDiagnostics::errorCategory('JavaScript heap out of memory'));
        $this->assertSame('thread_resource', SheinWebRuntimeDiagnostics::errorCategory('uv_thread_create failed'));
        $this->assertSame('permissions', SheinWebRuntimeDiagnostics::errorCategory('Access is denied'));
        $this->assertSame('unclassified', SheinWebRuntimeDiagnostics::errorCategory('trace ID 123456789'));
    }

    public function test_it_logs_input_size_only_as_bounded_bands(): void
    {
        $this->assertSame('small', SheinWebRuntimeDiagnostics::inputSizeBand(0));
        $this->assertSame('small', SheinWebRuntimeDiagnostics::inputSizeBand(8191));
        $this->assertSame('medium', SheinWebRuntimeDiagnostics::inputSizeBand(8192));
        $this->assertSame('medium', SheinWebRuntimeDiagnostics::inputSizeBand(131071));
        $this->assertSame('large', SheinWebRuntimeDiagnostics::inputSizeBand(131072));
    }

    public function test_it_has_only_predeclared_sapi_labels(): void
    {
        $this->assertContains(SheinWebRuntimeDiagnostics::sapi(), [
            'cli', 'php_builtin_server', 'web_server',
        ]);
    }
}
