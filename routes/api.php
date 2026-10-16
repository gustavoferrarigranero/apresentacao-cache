<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('tasks', [TaskController::class, 'index']);

Route::post('tasks', [TaskController::class, 'store'])->middleware('throttle:tasks');

Route::get('tasks/{task}', [TaskController::class, 'show']);
Route::match(['put', 'patch'], 'tasks/{task}', [TaskController::class, 'update']);
Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
