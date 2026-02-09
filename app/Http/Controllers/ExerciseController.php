<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    /**
     * GET /api/exercises
     * Lista todos los ejercicios del sistema (público).
     */
    public function index(): JsonResponse
    {
        $exercises = Exercise::with('category')->get();

        return response()->json(ExerciseResource::collection($exercises));
    }

    /**
     * GET /api/exercises/{exercise}
     * Detalle de un ejercicio concreto (público).
     */
    public function show(Exercise $exercise): JsonResponse
    {
        $exercise->load('category');

        return response()->json(new ExerciseResource($exercise));
    }

    /**
     * POST /api/exercises
     * Crea un ejercicio nuevo (requiere token).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $exercise = Exercise::create([
            'name' => $validated['name'],
            'instruction' => $validated['instruction'] ?? null,
            'category_id' => $validated['category_id'],
        ]);

        return response()->json(new ExerciseResource($exercise->load('category')), 201);
    }

    /**
     * PUT /api/exercises/{exercise}
     * Modifica un ejercicio (requiere token).
     */
    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
        ]);

        $exercise->update($validated);

        return response()->json(new ExerciseResource($exercise->load('category')));
    }

    /**
     * DELETE /api/exercises/{exercise}
     * Elimina un ejercicio (requiere token).
     */
    public function destroy(Exercise $exercise): JsonResponse
    {
        $exercise->delete();

        return response()->json(['message' => 'Ejercicio eliminado correctamente.']);
    }
}
