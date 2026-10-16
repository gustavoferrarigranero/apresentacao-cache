<?php

namespace App\Service;

use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{

    public function __construct(
        private TaskRepository $taskRepository,
    ) { }

    public function all(?string $status): Collection
    {
        return $this->taskRepository->all($status);
    }
}