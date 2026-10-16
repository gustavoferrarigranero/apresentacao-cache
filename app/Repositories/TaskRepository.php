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

}