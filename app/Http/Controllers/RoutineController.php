<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseRoutineResource;
use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    /**
     * GET /api/routines
     * Lista todas las rutinas públicas.
     */
    public function index(): JsonResponse
    {
        $routines = Routine::with('exercises')->get();

        return response()->json(RoutineResource::collection($routines));
    }

    /**
     * GET /api/routines/{routine}
     * Detalle completo de una rutina.
     */
    public function show(Routine $routine): JsonResponse
    {
        $routine->load('exercises');

        return response()->json(new RoutineResource($routine));
    }

    /**
     * POST /api/routines
     * Crea una rutina (requiere token).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $routine = Routine::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Asociar la rutina al usuario actual
        $request->user()->routines()->attach($routine->id);

        return response()->json(new RoutineResource($routine), 201);
    }

    /**
     * PUT /api/routines/{routine}
     * Edita una rutina (requiere token).
     */
    public function update(Request $request, Routine $routine): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $routine->update($validated);

        return response()->json(new RoutineResource($routine->load('exercises')));
    }

    /**
     * DELETE /api/routines/{routine}
     * Borra una rutina (requiere token).
     */
    public function destroy(Routine $routine): JsonResponse
    {
        $routine->delete();

        return response()->json(['message' => 'Rutina eliminada correctamente.']);
    }

    /**
     * GET /api/routines/{routine}/exercises
     * Muestra los ejercicios que componen una rutina (público).
     */
    public function exercises(Routine $routine): JsonResponse
    {
        $routine->load('exercises');

        return response()->json(ExerciseRoutineResource::collection($routine->exercises));
    }

    /**
     * POST /api/routines/{routine}/exercises
     * Añade un ejercicio a una rutina (requiere token).
     */
    public function addExercise(Request $request, Routine $routine): JsonResponse
    {
        $validated = $request->validate([
            'exercise_id' => ['required', 'exists:exercises,id'],
            'reps' => ['required', 'integer', 'min:1'],
            'sets' => ['required', 'integer', 'min:1'],
            'rest_seconds' => ['nullable', 'integer', 'min:0'],
            'sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        // Calcular sequence si no se proporciona
        $sequence = $validated['sequence'] ?? ($routine->exercises()->count() + 1);

        $routine->exercises()->attach($validated['exercise_id'], [
            'sequence' => $sequence,
            'target_sets' => $validated['sets'],
            'target_reps' => $validated['reps'],
            'rest_seconds' => $validated['rest_seconds'] ?? 60,
        ]);

        return response()->json(new RoutineResource($routine->load('exercises')), 201);
    }

    /**
     * DELETE /api/routines/{routine}/exercises/{exercise}
     * Quita un ejercicio de una rutina específica (requiere token).
     */
    public function removeExercise(Routine $routine, int $exerciseId): JsonResponse
    {
        $routine->exercises()->detach($exerciseId);

        return response()->json(['message' => 'Ejercicio eliminado de la rutina.']);
    }
}
