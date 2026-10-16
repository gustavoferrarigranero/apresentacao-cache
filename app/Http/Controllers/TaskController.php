<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Service\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
    ) { }

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $tasks = $this->taskService->all($status);

        return response()->json(['data' => $tasks]);
    }

    public function store(TaskRequest $request): JsonResponse
    {
        $task = Task::query()->create($request->validated());

        return response()->json(['data' => $task], Response::HTTP_CREATED);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json(['data' => $task]);
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        return response()->json(['data' => $task->refresh()]);
    }

    public function destroy(Task $task): Response
    {
        $task->delete();

        return response()->noContent();
    }
}
