<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoutineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.id' => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.sequence' => ['required', 'integer', 'min:1'],
            'exercises.*.target_sets' => ['required', 'integer', 'min:1'],
            'exercises.*.target_reps' => ['required', 'integer', 'min:1'],
            'exercises.*.rest_seconds' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la rutina es obligatorio.',
            'exercises.required' => 'Debes incluir al menos un ejercicio.',
            'exercises.min' => 'Debes incluir al menos un ejercicio.',
            'exercises.*.id.exists' => 'El ejercicio con ID :input no existe en la base de datos.',
        ];
    }
}
