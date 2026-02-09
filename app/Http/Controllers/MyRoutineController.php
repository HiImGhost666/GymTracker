<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyRoutineController extends Controller
{
    /**
     * GET /api/my-routines
     * Lista las rutinas del usuario logueado (requiere token).
     */
    public function index(Request $request): JsonResponse
    {
        $routines = $request->user()
            ->routines()
            ->with('exercises')
            ->get();

        return response()->json(RoutineResource::collection($routines));
    }

    /**
     * POST /api/my-routines
     * Suscribe al usuario a una rutina existente (requiere token).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'routine_id' => ['required', 'exists:routines,id'],
        ]);

        $user = $request->user();
        $routineId = $validated['routine_id'];

        // Verificar si ya está suscrito
        if ($user->routines()->where('routines.id', $routineId)->exists()) {
            return response()->json([
                'message' => 'Ya estás suscrito a esta rutina.',
            ], 409);
        }

        $user->routines()->attach($routineId);

        $routine = Routine::with('exercises')->find($routineId);

        return response()->json(new RoutineResource($routine), 201);
    }

    /**
     * DELETE /api/my-routines/{routine}
     * Desuscribe al usuario de una rutina (requiere token).
     */
    public function destroy(Request $request, Routine $routine): JsonResponse
    {
        $user = $request->user();

        // Verificar si está suscrito
        if (!$user->routines()->where('routines.id', $routine->id)->exists()) {
            return response()->json([
                'message' => 'No estás suscrito a esta rutina.',
            ], 404);
        }

        $user->routines()->detach($routine->id);

        return response()->json(['message' => 'Te has desuscrito de la rutina.']);
    }
}
