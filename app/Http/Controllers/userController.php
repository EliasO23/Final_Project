<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'message' => 'Get all data',
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $use = User::where('email', $request->email)->first();
        if ($use) {
            return response()->json([
                'message' => 'Email already registered'
            ], 400);
        }


        // create a new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'message' => 'Create a new User',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Get data by id',
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required',
            // 'email' => 'required|email|unique:users,email,',
            // 'password' => 'required'
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->name = $request->name;
        // $user->email = $request->email;
        // $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'message' => 'Update data',
            'data' => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->disabled = true;
        $user->save();

        return response()->json([
            'message' => 'Delete data',
            'data' => $user
        ], 200);
    }

    /**
     * Get the statistics of the users
     */
    public function stats()
    {
        // Usuarios por cada hora del día actual
        $startOfDay = now()->startOfDay();
        $currentHour = now()->hour;

        $hourlyStats = collect(range(0, $currentHour))->map(function ($hour) use ($startOfDay) {
            $startHour = $startOfDay->copy()->addHours($hour);
            $endHour = $startHour->copy()->addHour();

            return [
                'hour' => $startHour->format('H:00'),
                'count' => User::whereBetween('created_at', [$startHour, $endHour])->count(),
            ];
        });

        // Usuarios por día en la última semana
        $startOfWeek = now()->startOfDay()->subDays(6);
        $dailyStats = collect(range(0, 6))->map(function ($day) use ($startOfWeek) {
            $date = $startOfWeek->copy()->addDays($day);

            return [
                'date' => $date->format('l'),
                'count' => User::whereDate('created_at', $date)->count(),
            ];
        });

        // Usuarios por semana del mes actual
        $currentDate = now(); // Fecha actual
        $startOfWeek = $currentDate->copy()->startOfWeek(); // Inicio de la semana actual

        // Generar estadísticas para las últimas 4 semanas
        $weeklyStats = collect(range(0, 3))->map(function ($weekOffset) use ($startOfWeek) {
            $startWeek = $startOfWeek->copy()->subWeeks($weekOffset); // Inicio de la semana
            $endWeek = $startWeek->copy()->endOfWeek(); // Fin de la semana

            return [
                'week' => "Week " . ($weekOffset + 1), // Etiqueta de la semana
                'count' => User::whereBetween('created_at', [$startWeek, $endWeek])->count(),
            ];
        });

        $weeklyStats = $weeklyStats->reverse()->values();

        // Usuarios por mes en los últimos 6 meses
        $currentDate = now(); // Fecha actual
        $startOfMonth = $currentDate->copy()->startOfMonth(); // Inicio del mes actual

        // Generar estadísticas para los últimos 6 meses
        $monthlyStats = collect(range(0, 4))->map(function ($monthOffset) use ($startOfMonth) {
            $startMonth = $startOfMonth->copy()->subMonths($monthOffset); // Inicio del mes correspondiente
            $endMonth = $startMonth->copy()->endOfMonth(); // Fin del mes correspondiente

            return [
                'month' => $startMonth->format('F Y'), // Nombre del mes y año
                'count' => User::whereBetween('created_at', [$startMonth, $endMonth])->count(),
            ];
        });

        $monthlyStats = $monthlyStats->reverse()->values();

        return response()->json([
            'hourly_stats' => $hourlyStats,
            'daily_stats' => $dailyStats,
            'weekly_stats' => $weeklyStats,
            'monthly_stats' => $monthlyStats,
        ], 200);
    }

    /**
     * Login a user
     */
    public function login(Request $request)
    {
        // Obtener credenciales
        $credentials = $request->only('email', 'password');

        // Verificar credenciales
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Verificar password
        if(!password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generar token
        $token = JWTAuth::fromUser($user);

        
        return response()->json([
            'message' => 'Login success',
            'data' => $user,
            'token' => $token
        ], 200);
    }
}
