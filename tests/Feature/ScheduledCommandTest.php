<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
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

    public function test_billing_reminders_command_is_registered_in_scheduler(): void
    {
        $schedule = app(Schedule::class);

        $commands = collect($schedule->events())
            ->map(fn ($e) => $e->command ?? '')
            ->filter();

        $this->assertTrue(
            $commands->contains(fn ($cmd) => str_contains($cmd, 'billing:send-payment-reminders')),
            'billing:send-payment-reminders must be registered in the scheduler'
        );
    }
}
