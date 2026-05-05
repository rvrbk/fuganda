<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledCommandTest extends TestCase
{
    public function test_ops_heartbeat_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('ops:heartbeat');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Heartbeat logged.', Artisan::output());
    }
}
