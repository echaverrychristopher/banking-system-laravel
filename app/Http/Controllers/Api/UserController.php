<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Listar todos los usuarios (solo admin)
    public function index()
    {
        return response()->json(User::select('id', 'name', 'email', 'role', 'created_at')->get());
    }

    // Cambiar el rol de un usuario (solo admin)
    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:cliente,cajero,admin',
        ]);

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Rol actualizado exitosamente',
            'user' => $user,
        ]);
    }
}