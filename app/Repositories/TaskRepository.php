<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository
{
    public function all(?string $status): Collection
    {
        return Task::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->get();
    }

    public function created(mixed $data)
    {
        return Task::query()->create($data);
    }

    public function update(Task $task, mixed $validated)
    {
        return $task->update($validated);
    }

    public function delete(Task $task)
    {
        return $task->delete();
    }

}