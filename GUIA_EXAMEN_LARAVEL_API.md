# 📚 Guía Completa - Laravel API GymTracker

> **Documento de preparación para el examen** - Febrero 2026

---

## 📋 Índice Rápido

1. [Estructura del Proyecto](#1-estructura-del-proyecto)
2. [Modelos y Relaciones](#2-modelos-y-relaciones)
3. [Migraciones (Base de Datos)](#3-migraciones-base-de-datos)
4. [Rutas API](#4-rutas-api)
5. [Controladores](#5-controladores)
6. [Resources (Transformación JSON)](#6-resources-transformación-json)
7. [Autenticación con Sanctum](#7-autenticación-con-sanctum)
8. [Seeders y Factories](#8-seeders-y-factories)
9. [Cambios Comunes que te Pueden Pedir](#9-cambios-comunes-que-te-pueden-pedir)
10. [Comandos Útiles](#10-comandos-útiles)

---

## 1. Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── AuthController.php      ← Login/Register/Logout
│   │   ├── CategoryController.php      ← CRUD Categorías
│   │   ├── ExerciseController.php      ← CRUD Ejercicios
│   │   ├── RoutineController.php       ← CRUD Rutinas + gestión ejercicios
│   │   └── MyRoutineController.php     ← Suscripciones del usuario
│   └── Resources/
│       ├── CategoryResource.php        ← Transforma Category a JSON
│       ├── ExerciseResource.php        ← Transforma Exercise a JSON
│       ├── RoutineResource.php         ← Transforma Routine a JSON
│       └── ExerciseRoutineResource.php ← Ejercicio + datos pivot
├── Models/
│   ├── Category.php
│   ├── Exercise.php
│   ├── Routine.php
│   └── User.php
database/
├── migrations/                         ← Estructura de tablas
├── factories/                          ← Datos falsos para testing
└── seeders/                            ← Poblar la BD
routes/
└── api.php                             ← Todas las rutas de la API
```

---

## 2. Modelos y Relaciones

### 📍 Archivo: `app/Models/Category.php`

```php
class Category extends Model
{
    protected $fillable = ['name', 'icon_path'];
    
    // Una categoría tiene MUCHOS ejercicios
    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }
}
```

**Relación:** Category `1 ──────< N` Exercise (Uno a Muchos)

---

### 📍 Archivo: `app/Models/Exercise.php`

```php
class Exercise extends Model
{
    protected $fillable = ['category_id', 'name', 'instruction'];
    
    // Un ejercicio PERTENECE A una categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

**Relación:** Exercise `N >────── 1` Category (Inversa de hasMany)

---

### 📍 Archivo: `app/Models/Routine.php`

```php
class Routine extends Model
{
    protected $fillable = ['name', 'description'];
    
    // Muchos a muchos con Exercise (tabla pivot: exercise_routine)
    public function exercises()
    {
        return $this->belongsToMany(Exercise::class)
                    ->withPivot('sequence', 'target_sets', 'target_reps', 'rest_seconds')
                    ->withTimestamps();
    }
    
    // Muchos a muchos con User (tabla pivot: routine_user)
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
```

**Relaciones:**
- Routine `N <────> N` Exercise (Muchos a Muchos con pivot)
- Routine `N <────> N` User (Muchos a Muchos - suscripciones)

---

### 📍 Archivo: `app/Models/User.php`

```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  // ⚠️ HasApiTokens es de Sanctum
    
    protected $fillable = ['name', 'email', 'password', 'remember_token'];
    
    // Rutinas a las que el usuario está suscrito
    public function routines()
    {
        return $this->belongsToMany(Routine::class)->withTimestamps();
    }
}
```

---

### 🗂️ Diagrama de Relaciones

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│   Category   │       │   Exercise   │       │   Routine    │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id           │───┐   │ id           │   ┌───│ id           │
│ name         │   │   │ category_id  │◄──┘   │ name         │
│ icon_path    │   └──►│ name         │       │ description  │
└──────────────┘       │ instruction  │       └──────┬───────┘
                       └──────┬───────┘              │
                              │                      │
                              │  ┌───────────────────┘
                              │  │
                       ┌──────▼──▼──────┐       ┌──────────────┐
                       │exercise_routine│       │ routine_user │
                       ├────────────────┤       ├──────────────┤
                       │ exercise_id    │       │ user_id      │◄─┐
                       │ routine_id     │       │ routine_id   │  │
                       │ sequence       │       └──────────────┘  │
                       │ target_sets    │                         │
                       │ target_reps    │       ┌──────────────┐  │
                       │ rest_seconds   │       │    User      │  │
                       └────────────────┘       ├──────────────┤  │
                                                │ id           │──┘
                                                │ name         │
                                                │ email        │
                                                │ password     │
                                                └──────────────┘
```

---

## 3. Migraciones (Base de Datos)

### 📍 Archivo: `database/migrations/2026_02_09_175449_create_categories_table.php`

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('icon_path')->nullable();
    $table->timestamps();
});
```

---

### 📍 Archivo: `database/migrations/2026_02_09_180218_create_exercises_table.php`

```php
Schema::create('exercises', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('instruction');
    $table->timestamps();
});
```

**Nota:** `constrained()->cascadeOnDelete()` significa:
- Crea FK hacia `categories.id`
- Si se borra la categoría, se borran sus ejercicios

---

### 📍 Archivo: `database/migrations/2026_02_09_180327_create_routines_table.php`

```php
Schema::create('routines', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

---

### 📍 Archivo: `database/migrations/2026_02_09_180514_create_exercise_routine_table.php`

```php
Schema::create('exercise_routine', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
    $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('sequence');      // Orden del ejercicio
    $table->unsignedInteger('target_sets');   // Series objetivo
    $table->unsignedInteger('target_reps');   // Repeticiones objetivo
    $table->unsignedInteger('rest_seconds');  // Descanso en segundos
    $table->timestamps();
});
```

**⚠️ IMPORTANTE:** Esta es una **tabla pivot** con datos adicionales (sequence, sets, reps, rest)

---

### 📍 Archivo: `database/migrations/2026_02_09_180721_create_routine_user_table.php`

```php
Schema::create('routine_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
});
```

**Nota:** Tabla pivot para suscripciones de usuarios a rutinas.

---

## 4. Rutas API

### 📍 Archivo: `routes/api.php`

### Rutas PÚBLICAS (sin token)

| Método | Ruta | Controlador | Descripción |
|--------|------|-------------|-------------|
| POST | `/api/register` | AuthController@register | Registrar usuario |
| POST | `/api/login` | AuthController@login | Iniciar sesión |
| GET | `/api/categories` | CategoryController@index | Listar categorías |
| GET | `/api/categories/{id}` | CategoryController@show | Ver categoría |
| GET | `/api/categories/{id}/exercises` | CategoryController@exercises | Ejercicios de categoría |
| GET | `/api/exercises` | ExerciseController@index | Listar ejercicios |
| GET | `/api/exercises/{id}` | ExerciseController@show | Ver ejercicio |
| GET | `/api/routines` | RoutineController@index | Listar rutinas |
| GET | `/api/routines/{id}` | RoutineController@show | Ver rutina |
| GET | `/api/routines/{id}/exercises` | RoutineController@exercises | Ejercicios de rutina |

### Rutas PROTEGIDAS (requieren token)

| Método | Ruta | Controlador | Descripción |
|--------|------|-------------|-------------|
| GET | `/api/user` | Closure | Datos del usuario actual |
| POST | `/api/logout` | AuthController@logout | Cerrar sesión |
| POST | `/api/categories` | CategoryController@store | Crear categoría |
| PUT | `/api/categories/{id}` | CategoryController@update | Editar categoría |
| DELETE | `/api/categories/{id}` | CategoryController@destroy | Borrar categoría |
| POST | `/api/exercises` | ExerciseController@store | Crear ejercicio |
| PUT | `/api/exercises/{id}` | ExerciseController@update | Editar ejercicio |
| DELETE | `/api/exercises/{id}` | ExerciseController@destroy | Borrar ejercicio |
| POST | `/api/routines` | RoutineController@store | Crear rutina |
| PUT | `/api/routines/{id}` | RoutineController@update | Editar rutina |
| DELETE | `/api/routines/{id}` | RoutineController@destroy | Borrar rutina |
| POST | `/api/routines/{id}/exercises` | RoutineController@addExercise | Añadir ejercicios |
| DELETE | `/api/routines/{id}/exercises/{eid}` | RoutineController@removeExercise | Quitar ejercicio |
| GET | `/api/my-routines` | MyRoutineController@index | Mis suscripciones |
| POST | `/api/my-routines` | MyRoutineController@store | Suscribirse a rutina |
| DELETE | `/api/my-routines/{id}` | MyRoutineController@destroy | Desuscribirse |

### Código de las rutas:

```php
// PÚBLICAS
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('exercises', ExerciseController::class)->only(['index', 'show']);
Route::apiResource('routines', RoutineController::class)->only(['index', 'show']);

Route::get('/categories/{category}/exercises', [CategoryController::class, 'exercises']);
Route::get('/routines/{routine}/exercises', [RoutineController::class, 'exercises']);

// PROTEGIDAS (middleware auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('exercises', ExerciseController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('routines', RoutineController::class)->only(['store', 'update', 'destroy']);
    
    Route::post('/routines/{routine}/exercises', [RoutineController::class, 'addExercise']);
    Route::delete('/routines/{routine}/exercises/{exercise}', [RoutineController::class, 'removeExercise']);
    
    Route::apiResource('my-routines', MyRoutineController::class)
        ->only(['index', 'store', 'destroy'])
        ->parameters(['my-routines' => 'routine']);
});
```

---

## 5. Controladores

### 📍 Archivo: `app/Http/Controllers/Api/AuthController.php`

#### Register (Registro de usuario)

```php
public function register(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'remember_token' => Str::random(60),
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json(['user' => $user, 'token' => $token], 201);
}
```

**Puntos clave:**
- Valida campos requeridos
- `password_confirmation` requerido por `confirmed`
- `Hash::make()` encripta la contraseña
- `createToken()` genera token Sanctum

---

#### Login

```php
public function login(Request $request): JsonResponse
{
    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Las credenciales proporcionadas son incorrectas.',
        ], 401);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json(['user' => $user, 'token' => $token]);
}
```

---

#### Logout

```php
public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Sesión cerrada correctamente.']);
}
```

---

### 📍 Archivo: `app/Http/Controllers/CategoryController.php`

```php
// Listar todas
public function index(): JsonResponse
{
    $categories = Category::all();
    return response()->json(CategoryResource::collection($categories));
}

// Ver una
public function show(Category $category): JsonResponse
{
    return response()->json(new CategoryResource($category));
}

// Crear (requiere token)
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $category = Category::create(['name' => $validated['name']]);
    return response()->json(new CategoryResource($category), 201);
}

// Actualizar (requiere token)
public function update(Request $request, Category $category): JsonResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $category->update(['name' => $validated['name']]);
    return response()->json(new CategoryResource($category));
}

// Borrar (requiere token)
public function destroy(Category $category): JsonResponse
{
    $category->delete();
    return response()->json(['message' => 'Categoría eliminada correctamente.']);
}

// Ejercicios de una categoría
public function exercises(Category $category): JsonResponse
{
    $category->load('exercises');
    return response()->json(ExerciseResource::collection($category->exercises));
}
```

---

### 📍 Archivo: `app/Http/Controllers/RoutineController.php`

#### Añadir ejercicios a rutina (método importante)

```php
public function addExercise(Request $request, Routine $routine): JsonResponse
{
    // Acepta un array de ejercicios o uno solo
    if ($request->has('exercises')) {
        $validated = $request->validate([
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'exercises.*.reps' => ['required', 'integer', 'min:1'],
            'exercises.*.sets' => ['required', 'integer', 'min:1'],
            'exercises.*.rest_seconds' => ['nullable', 'integer', 'min:0'],
            'exercises.*.sequence' => ['nullable', 'integer', 'min:1'],
        ]);

        $routine->exercises()->detach();  // Elimina anteriores

        $sequence = 1;
        foreach ($validated['exercises'] as $exercise) {
            $routine->exercises()->attach($exercise['exercise_id'], [
                'sequence' => $exercise['sequence'] ?? $sequence++,
                'target_sets' => $exercise['sets'],
                'target_reps' => $exercise['reps'],
                'rest_seconds' => $exercise['rest_seconds'] ?? 60,
            ]);
        }
    } else {
        // Ejercicio individual
        $validated = $request->validate([
            'exercise_id' => ['required', 'exists:exercises,id'],
            'reps' => ['required', 'integer', 'min:1'],
            'sets' => ['required', 'integer', 'min:1'],
        ]);

        $routine->exercises()->attach($validated['exercise_id'], [
            'sequence' => $routine->exercises()->count() + 1,
            'target_sets' => $validated['sets'],
            'target_reps' => $validated['reps'],
            'rest_seconds' => $validated['rest_seconds'] ?? 60,
        ]);
    }

    return response()->json(new RoutineResource($routine->load('exercises')), 201);
}
```

**Métodos importantes de relaciones:**
- `attach()` - Añadir a relación many-to-many
- `detach()` - Quitar de relación many-to-many
- `sync()` - Reemplazar todos los relacionados

---

### 📍 Archivo: `app/Http/Controllers/MyRoutineController.php`

```php
// Ver mis rutinas suscritas
public function index(Request $request): JsonResponse
{
    $routines = $request->user()
        ->routines()
        ->with('exercises')
        ->get();

    return response()->json(RoutineResource::collection($routines));
}

// Suscribirse a una rutina
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'routine_id' => ['required', 'exists:routines,id'],
    ]);

    $user = $request->user();
    $routineId = $validated['routine_id'];

    // Verificar si ya está suscrito
    if ($user->routines()->where('routines.id', $routineId)->exists()) {
        return response()->json(['message' => 'Ya estás suscrito a esta rutina.'], 409);
    }

    $user->routines()->attach($routineId);
    return response()->json(new RoutineResource(Routine::find($routineId)), 201);
}

// Desuscribirse
public function destroy(Request $request, Routine $routine): JsonResponse
{
    $request->user()->routines()->detach($routine->id);
    return response()->json(['message' => 'Te has desuscrito de la rutina.']);
}
```

---

## 6. Resources (Transformación JSON)

### 📍 Archivo: `app/Http/Resources/CategoryResource.php`

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'icon_path' => $this->icon_path,
        'exercises' => ExerciseResource::collection($this->whenLoaded('exercises')),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

**`whenLoaded()`** - Solo incluye la relación si se cargó con `with()` o `load()`

---

### 📍 Archivo: `app/Http/Resources/ExerciseRoutineResource.php`

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'instruction' => $this->instruction,
        'category_id' => $this->category_id,
        // Datos de la tabla pivot al mismo nivel
        'sequence' => $this->pivot->sequence,
        'target_sets' => $this->pivot->target_sets,
        'target_reps' => $this->pivot->target_reps,
        'rest_seconds' => $this->pivot->rest_seconds,
    ];
}
```

**`$this->pivot`** - Accede a los datos de la tabla intermedia

---

## 7. Autenticación con Sanctum

### ¿Qué es Sanctum?
Sistema de autenticación por tokens para APIs de Laravel.

### Configuración necesaria:

1. **En el modelo User:**
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

2. **Proteger rutas con middleware:**
```php
Route::middleware('auth:sanctum')->group(function () {
    // Rutas protegidas aquí
});
```

3. **Crear token al login/register:**
```php
$token = $user->createToken('auth-token')->plainTextToken;
```

4. **Usar token en peticiones:**
```
Authorization: Bearer TU_TOKEN_AQUI
```

5. **Eliminar token al logout:**
```php
$request->user()->currentAccessToken()->delete();
```

---

## 8. Seeders y Factories

### 📍 Archivo: `database/seeders/DatabaseSeeder.php`

```php
public function run(): void
{
    // 1. Crear 10 usuarios
    $users = User::factory(10)->create();

    // 2. Crear categorías manualmente
    $categories = collect(['Pecho', 'Espalda', 'Pierna'])->map(function ($name) {
        return Category::create([
            'name' => $name,
            'icon_path' => strtolower($name) . '.png',
        ]);
    });

    // 3. Crear 4 ejercicios por categoría (12 total)
    foreach ($categories as $category) {
        Exercise::factory(4)->create(['category_id' => $category->id]);
    }

    // 4. Crear 5 rutinas con ejercicios y usuarios
    Routine::factory(5)->create()->each(function ($routine) use ($users, $exercises) {
        // Asignar a 2-4 usuarios
        $routine->users()->attach($users->random(rand(2, 4))->pluck('id'));
        
        // Añadir 3-5 ejercicios con datos pivot
        foreach ($exercises->random(rand(3, 5)) as $exercise) {
            $routine->exercises()->attach($exercise->id, [
                'sequence' => $sequence++,
                'target_sets' => rand(2, 5),
                'target_reps' => rand(6, 15),
                'rest_seconds' => rand(30, 120),
            ]);
        }
    });
}
```

### 📍 Archivo: `database/factories/ExerciseFactory.php`

```php
public function definition(): array
{
    return [
        'category_id' => Category::factory(),
        'name' => $this->faker->words(3, true),
        'instruction' => $this->faker->paragraph(),
    ];
}
```

---

## 9. Cambios Comunes que te Pueden Pedir

### ➕ Añadir un campo nuevo a una tabla

**Ejemplo:** Añadir `difficulty` a exercises

1. **Crear migración:**
```bash
php artisan make:migration add_difficulty_to_exercises_table
```

2. **Editar la migración:**
```php
public function up(): void
{
    Schema::table('exercises', function (Blueprint $table) {
        $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
    });
}

public function down(): void
{
    Schema::table('exercises', function (Blueprint $table) {
        $table->dropColumn('difficulty');
    });
}
```

3. **Ejecutar migración:**
```bash
php artisan migrate
```

4. **Añadir al fillable del modelo:**
```php
// app/Models/Exercise.php
protected $fillable = ['category_id', 'name', 'instruction', 'difficulty'];
```

5. **Añadir al Resource:**
```php
// app/Http/Resources/ExerciseResource.php
return [
    'id' => $this->id,
    'name' => $this->name,
    'difficulty' => $this->difficulty,  // ← Nuevo
    // ...
];
```

6. **Actualizar validación en controlador:**
```php
// app/Http/Controllers/ExerciseController.php
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255'],
    'difficulty' => ['sometimes', 'in:easy,medium,hard'],  // ← Nuevo
    // ...
]);
```

---

### ➕ Añadir una nueva ruta

**Ejemplo:** Endpoint para buscar ejercicios por nombre

1. **Añadir ruta en `routes/api.php`:**
```php
Route::get('/exercises/search', [ExerciseController::class, 'search']);
```

2. **Añadir método en controlador:**
```php
public function search(Request $request): JsonResponse
{
    $query = $request->query('q');
    
    $exercises = Exercise::where('name', 'like', "%{$query}%")
        ->with('category')
        ->get();
    
    return response()->json(ExerciseResource::collection($exercises));
}
```

---

### ➕ Añadir validación personalizada

**Ejemplo:** Validar que el nombre de categoría sea único

```php
// En CategoryController@store
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
]);

// En CategoryController@update (excluir el actual)
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
]);
```

---

### ➕ Añadir una relación nueva

**Ejemplo:** Añadir comentarios a rutinas

1. **Crear modelo y migración:**
```bash
php artisan make:model Comment -m
```

2. **Definir migración:**
```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('body');
    $table->timestamps();
});
```

3. **Añadir relación en Routine:**
```php
public function comments()
{
    return $this->hasMany(Comment::class);
}
```

---

### ➕ Cambiar respuesta JSON de un endpoint

**Archivo:** `app/Http/Resources/RoutineResource.php`

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'description' => $this->description,
        'exercise_count' => $this->exercises->count(),  // ← Añadir
        'exercises' => ExerciseRoutineResource::collection($this->whenLoaded('exercises')),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

---

### ➕ Proteger/Desproteger una ruta

**Proteger (añadir autenticación):**
```php
// Mover a dentro del grupo middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/exercises', [ExerciseController::class, 'index']);  // Ahora requiere token
});
```

**Desproteger:**
```php
// Mover fuera del grupo middleware
Route::get('/exercises', [ExerciseController::class, 'index']);  // Ahora es pública
```

---

## 10. Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Resetear BD y ejecutar migraciones + seeders
php artisan migrate:fresh --seed

# Crear controlador
php artisan make:controller NombreController --api

# Crear modelo con migración y factory
php artisan make:model Nombre -mf

# Crear Resource
php artisan make:resource NombreResource

# Crear Seeder
php artisan make:seeder NombreSeeder

# Crear Factory
php artisan make:factory NombreFactory

# Ver todas las rutas
php artisan route:list

# Ver rutas de API
php artisan route:list --path=api

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📝 Resumen de Archivos Importantes

| Concepto | Archivo |
|----------|---------|
| Rutas API | `routes/api.php` |
| Autenticación | `app/Http/Controllers/Api/AuthController.php` |
| CRUD Categorías | `app/Http/Controllers/CategoryController.php` |
| CRUD Ejercicios | `app/Http/Controllers/ExerciseController.php` |
| CRUD Rutinas | `app/Http/Controllers/RoutineController.php` |
| Mis Rutinas | `app/Http/Controllers/MyRoutineController.php` |
| Modelo Category | `app/Models/Category.php` |
| Modelo Exercise | `app/Models/Exercise.php` |
| Modelo Routine | `app/Models/Routine.php` |
| Modelo User | `app/Models/User.php` |
| Resource Category | `app/Http/Resources/CategoryResource.php` |
| Resource Exercise | `app/Http/Resources/ExerciseResource.php` |
| Resource Routine | `app/Http/Resources/RoutineResource.php` |
| Resource Pivot | `app/Http/Resources/ExerciseRoutineResource.php` |
| Migraciones | `database/migrations/*.php` |
| Factories | `database/factories/*.php` |
| Seeders | `database/seeders/DatabaseSeeder.php` |

---

## ✅ Checklist Pre-Examen

- [ ] Entiendo las 4 relaciones (hasMany, belongsTo, belongsToMany, belongsToMany con pivot)
- [ ] Sé qué hace `withPivot()` y cómo acceder con `$this->pivot`
- [ ] Entiendo la diferencia entre rutas públicas y protegidas
- [ ] Sé cómo funciona `attach()`, `detach()` y `sync()`
- [ ] Puedo añadir un campo nuevo a una tabla
- [ ] Puedo crear una nueva ruta y método en controlador
- [ ] Entiendo cómo funcionan los Resources para transformar JSON
- [ ] Sé cómo usar Sanctum para autenticación
- [ ] Puedo ejecutar migraciones y seeders

---

**¡Mucha suerte en el examen! 🚀**
