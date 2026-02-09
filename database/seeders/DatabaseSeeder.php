<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\Routine;
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
        // 1. Crear usuarios de prueba
        $users = User::factory(10)->create();

        // 2. Crear categorías musculares
        $categories = collect(['Pecho', 'Espalda', 'Pierna'])->map(function ($name) {
            return Category::create([
                'name' => $name,
                'icon_path' => strtolower($name) . '.png',
            ]);
        });

        // 3. Crear ejercicios por categoría
        $exercises = collect();
        foreach ($categories as $category) {
            $created = Exercise::factory(4)->create([
                'category_id' => $category->id,
            ]);
            $exercises = $exercises->merge($created);
        }

        // 4. Crear rutinas con usuarios y ejercicios aleatorios
        Routine::factory(5)->create()->each(function ($routine) use ($users, $exercises) {
            // Asignar la rutina a 2-4 usuarios aleatorios
            $routine->users()->attach(
                $users->random(rand(2, 4))->pluck('id')->toArray()
            );

            // Asignar 3-5 ejercicios aleatorios con datos pivot
            $selectedExercises = $exercises->random(rand(3, 5));
            $sequence = 1;
            foreach ($selectedExercises as $exercise) {
                $routine->exercises()->attach($exercise->id, [
                    'sequence' => $sequence++,
                    'target_sets' => rand(2, 5),
                    'target_reps' => rand(6, 15),
                    'rest_seconds' => rand(30, 120),
                ]);
            }
        });
    }
}
