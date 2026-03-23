<?php

namespace App\Http\Controllers;

use App\Models\AlumnoEntrega;
use App\Models\Alumnos;
use App\Models\Ciclos;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AlumnosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Alumnos::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required'],
            'apellidos' => ['required'],
            'matricula' => ['required'],
            'dni' => ['required'],
            'telefono' => ['required'],
            'ciudad' => ['required'],
            'curso' => ['required'],
            'tutor' => ['required']
        ]);

        // Obtener el ciclo para extraer su 'grupo' (string)
        $ciclo = Ciclos::findOrFail($validated['curso']);

        // Generar email y contraseña temporal
        $email = strtolower($validated['nombre']) . '.' . strtolower(explode(' ', $validated['apellidos'])[0]) . '@ikasle.egibide.org';
        $password = Hash::make('12345Abcde');

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Ya existe un alumno con el email generado: $email"
            ], 422);
        }

        // USUARIO
        $user = User::create([
            'email' => $email,
            'password' => $password,
            'role' => 'alumno',
        ]);

        // ALUMNO con tutor y grupo (ciclo)
        Alumnos::create([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'matricula_id' => $validated['matricula'],
            'dni' => $validated['dni'],
            'ciudad' => $validated['ciudad'],
            'telefono' => $validated['telefono'],
            'user_id' => $user->id,
            'tutor_id' => $validated['tutor'],
            'grupo' => $ciclo->grupo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alumno agregado correctamente'
        ], 201);
    }

    public function inicio(Request $request)
    {
        $user = $request->user();

        $alumno = $user->alumno;

        if (!$alumno) {
            return response()->json([
                'message' => 'El usuario no tiene alumno asociado.'
            ], 404);
        }

        $hoy = now();

        $estanciaActual = $alumno->estancias()
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$estanciaActual) {
            $estanciaActual = $alumno->estancias()
                ->orderBy('fecha_inicio', 'desc')
                ->first();
        }

        if (!$estanciaActual) {
            return response()->json([
                'alumno' => $alumno,
                'estancia' => null,
                'message' => 'El alumno no tiene estancias asignadas todavía.'
            ]);
        }

        $estanciaActual->load([
            'empresa:id,nombre',
            'instructor:id,nombre,apellidos,telefono,empresa_id',
            'horariosDia.horariosTramo',
        ]);

        return response()->json([
            'alumno' => $alumno,
            'estancia' => [
                'fecha_inicio' => optional($estanciaActual->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($estanciaActual->fecha_fin)->toDateString(),
                'puesto' => $estanciaActual->puesto,
                'empresa' => $estanciaActual->empresa ? [
                    'nombre' => $estanciaActual->empresa->nombre,
                ] : null,
                'tutor' => $alumno->tutor ? [
                    'nombre' => $alumno->tutor->nombre,
                    'apellidos' => $alumno->tutor->apellidos,
                    'telefono' => $alumno->tutor->telefono,
                ] : null,
                'instructor' => $estanciaActual->instructor ? [
                    'nombre' => $estanciaActual->instructor->nombre,
                    'apellidos' => $estanciaActual->instructor->apellidos,
                    'telefono' => $estanciaActual->instructor->telefono,
                ] : null,
                'horario' => $estanciaActual->horariosDia->map(function ($dia) {
                    return [
                        'dia_semana' => $dia->dia_semana,
                        'tramos' => $dia->horariosTramo->map(function ($t) {
                            return [
                                'hora_inicio' => $t->hora_inicio,
                                'hora_fin' => $t->hora_fin,
                            ];
                        })->values()
                    ];
                })->values(),
            ],
        ]);
    }

    public function me()
    {
        $userId = auth()->id();

        $row = Alumnos::join('users', 'alumnos.user_id', '=', 'users.id')
            ->select(
                'alumnos.nombre',
                'alumnos.apellidos',
                'alumnos.telefono',
                'alumnos.ciudad',

                'users.email',
                'alumnos.id',
            )
            ->where('alumnos.user_id', $userId)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Alumno no encontrado'], 404);
        }

        return response()->json($row);
    }

    public function getAsignaturasAlumno($alumno_id)
    {

        $asignaturas = Alumnos::find($alumno_id)->ciclo->asignaturas;

        return response()->json($asignaturas, 200);
    }

    public function entregas($alumnoId)
{
    // Usamos el modelo Eloquent en lugar de DB::table
    $entregas = AlumnoEntrega::where('alumno_id', $alumnoId)
        ->select(
            'id', 
            'URL_Cuaderno as archivo',  // Mapeamos al nombre que espera tu frontend
            'Fecha_Entrega as fecha'    // Mapeamos al nombre que espera tu frontend
        )
        ->orderBy('Fecha_Entrega', 'desc')
        ->get();

    return response()->json($entregas);
}
}
