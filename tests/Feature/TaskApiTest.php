<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_tasks_filtered_by_status(): void
    {
        Task::factory()->create(['status' => 'open']);
        Task::factory()->create(['status' => 'done']);

        $this->getJson('/api/tasks?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'open');
    }

    public function test_it_creates_a_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Preparar aula',
            'description' => 'Revisar exemplos de cache',
            'status' => 'open',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Preparar aula');

        $this->assertDatabaseHas('tasks', ['title' => 'Preparar aula']);
    }

    public function test_it_shows_updates_and_deletes_a_task(): void
    {
        $task = Task::factory()->create(['status' => 'open']);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'done',
        ])->assertOk()->assertJsonPath('data.status', 'done');

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_it_validates_the_payload(): void
    {
        $this->postJson('/api/tasks', ['status' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);
    }
}
