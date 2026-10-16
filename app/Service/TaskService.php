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
        $key = 'tasks:list:'.($status ?? 'all');

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

    public function create(mixed $data)
    {
        return $this->taskRepository->created($data);
    }

    public function update(Task $task, mixed $validated)
    {
        return $this->taskRepository->update($task, $validated);
    }

    public function delete(Task $task)
    {
        return $this->taskRepository->delete($task);
    }
}