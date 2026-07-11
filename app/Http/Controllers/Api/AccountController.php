<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    // Listar las cuentas del usuario autenticado
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts;

        return response()->json($accounts);
    }

    // Crear una nueva cuenta para el usuario autenticado
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:ahorro,corriente,credito',
        ]);

        $account = Account::create([
            'user_id' => $request->user()->id,
            'account_number' => $this->generateAccountNumber(),
            'type' => $validated['type'],
            'balance' => 0,
            'credit_limit' => $validated['type'] === 'credito' ? 50000 : null,
            'status' => 'activa',
        ]);

        return response()->json([
            'message' => 'Cuenta creada exitosamente',
            'account' => $account,
        ], 201);
    }

    // Ver el detalle de una cuenta específica (con sus transacciones)
    public function show(Request $request, Account $account)
    {
        // Seguridad: verificar que la cuenta pertenece al usuario autenticado
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $account->load('transactions');

        return response()->json($account);
    }

    // Genera un número de cuenta único de 10 dígitos
    private function generateAccountNumber()
    {
        do {
            $number = strval(rand(1000000000, 9999999999));
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }
}