<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ExerciseResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * Lista todas las categorías (público).
     */
    public function index(): JsonResponse
    {
        $categories = Category::all();

        return response()->json(CategoryResource::collection($categories));
    }

    /**
     * GET /api/categories/{category}
     * Detalle de una categoría específica (público).
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json(new CategoryResource($category));
    }

    /**
     * POST /api/categories
     * Crea una nueva categoría (requiere token).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
        ]);

        return response()->json(new CategoryResource($category), 201);
    }

    /**
     * PUT /api/categories/{category}
     * Edita una categoría existente (requiere token).
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update([
            'name' => $validated['name'],
        ]);

        return response()->json(new CategoryResource($category));
    }

    /**
     * DELETE /api/categories/{category}
     * Borra una categoría (requiere token).
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente.']);
    }

    /**
     * GET /api/categories/{category}/exercises
     * Lista ejercicios de esa categoría (público).
     */
    public function exercises(Category $category): JsonResponse
    {
        $category->load('exercises');

        return response()->json(ExerciseResource::collection($category->exercises));
    }
}
