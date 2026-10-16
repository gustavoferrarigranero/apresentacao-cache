<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_project_is_ready_to_use_redis_queues(): void
    {
        $this->assertSame('redis', config('queue.default'));
        $this->assertSame('PONG', (string) Redis::connection()->ping());
    }

    public function test_the_queue_support_tables_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('job_batches'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
    }
}
