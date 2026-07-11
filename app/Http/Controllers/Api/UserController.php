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

  public function updateRole(Request $request, User $user)
{
    if ($user->id === $request->user()->id) {
        return response()->json([
            'message' => 'No podés cambiar tu propio rol.',
        ], 422);
    }

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