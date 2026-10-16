<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskObserver
{
    private function getCacheListKey(?string $status): string
    {
        return 'tasks:list:'.($status ?? 'all');
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($task->status)
        );
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($task->status)
        );
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($task->status)
        );
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
