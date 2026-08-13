<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Task::factory(15)->create([
        //     "user_id" => User::inRandomOrder()->first()->id
        // ]);

        $users = User::factory(5)->create();
        $users->each(function ($user) {
            Project::factory(2)->create([
                'user_id' => $user->id,
            ])->each(function ($project) use ($user) {
                Task::factory(2)->create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                ]);
            });
            Task::factory(2)->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
