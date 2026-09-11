<?php

namespace Tests\Feature;

use Tests\TestCase;

class RailwayDeploymentConfigTest extends TestCase
{
    public function test_railway_deployment_files_are_present_and_configured(): void
    {
        $root = dirname(__DIR__, 2);

        $dockerfile = file_get_contents($root.'/Dockerfile');
        $startScript = file_get_contents($root.'/docker/railway-start.sh');
        $dockerIgnore = file_get_contents($root.'/.dockerignore');

        $this->assertStringContainsString('php:8.3-cli-bookworm', $dockerfile);
        $this->assertStringContainsString('playwright install --with-deps chromium', $dockerfile);
        $this->assertStringContainsString('EXPOSE 8080', $dockerfile);

        $this->assertStringContainsString('php artisan migrate --force', $startScript);
        $this->assertStringContainsString('0.0.0.0', $startScript);
        $this->assertStringContainsString('${PORT:-8080}', $startScript);

        $this->assertStringContainsString('shein-debug*.json', $dockerIgnore);
        $this->assertStringContainsString('.env', $dockerIgnore);
    }
}
