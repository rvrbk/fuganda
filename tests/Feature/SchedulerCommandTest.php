<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchedulerCommandTest extends TestCase
{
    public function test_schedule_run_executes_registered_command_successfully(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));

        $exitCode = Artisan::call('schedule:run');

        Carbon::setTestNow();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('ops:heartbeat', Artisan::output());
    }
}
