<?php

namespace App\Service;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TaskService
{

    public function __construct(
        private TaskRepository $taskRepository,
    ) { }

    public function all(?string $status): \stdClass
    {
        $key = $this->getCacheListKey($status);

        $cache = Cache::driver();

        $ttl = config('cache.ttl_seconds');

        $hit = $cache->has($key);

        $tasks = $cache->remember(
            $key,
            $ttl,
            fn (): Collection => $this->taskRepository->all($status)
        );

        $result = new \stdClass();
        $result->items = $tasks;
        $result->hit = $hit;

        return $result;
    }

    private function getCacheListKey(?string $status): string
    {
        return 'tasks:list:'.($status ?? 'all');
    }

    public function create(mixed $data)
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($data['status'] ?? null)
        );

        return $this->taskRepository->created($data);
    }

    public function update(Task $task, mixed $validated)
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($task->status)
        );

        if ($task->status !== $validated['status']) {
            $cache->forget(
                $this->getCacheListKey($validated['status'])
            );
        }

        return $this->taskRepository->update($task, $validated);
    }

    public function delete(Task $task)
    {
        $cache = Cache::driver();

        $cache->forget(
            $this->getCacheListKey($task->status)
        );

        return $this->taskRepository->delete($task);
    }
}