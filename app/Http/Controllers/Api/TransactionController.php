<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    // Depósito
    public function deposit(Request $request, Account $account)
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($account, $validated) {
            $account->balance += $validated['amount'];
            $account->save();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => 'deposito',
                'amount' => $validated['amount'],
                'balance_after' => $account->balance,
                'description' => $validated['description'] ?? 'Depósito',
            ]);

            return response()->json([
                'message' => 'Depósito realizado exitosamente',
                'transaction' => $transaction,
                'new_balance' => $account->balance,
            ], 201);
        });
    }

    // Retiro
    public function withdraw(Request $request, Account $account)
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($account->balance < $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo insuficiente para realizar este retiro.'],
            ]);
        }

        return DB::transaction(function () use ($account, $validated) {
            $account->balance -= $validated['amount'];
            $account->save();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => 'retiro',
                'amount' => $validated['amount'],
                'balance_after' => $account->balance,
                'description' => $validated['description'] ?? 'Retiro',
            ]);

            return response()->json([
                'message' => 'Retiro realizado exitosamente',
                'transaction' => $transaction,
                'new_balance' => $account->balance,
            ], 201);
        });
    }

    // Transferencia entre cuentas
    public function transfer(Request $request, Account $account)
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'to_account_number' => 'required|string|exists:accounts,account_number',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $destinationAccount = Account::where('account_number', $validated['to_account_number'])->first();

        if ($destinationAccount->id === $account->id) {
            throw ValidationException::withMessages([
                'to_account_number' => ['No podés transferir a la misma cuenta.'],
            ]);
        }

        if ($account->balance < $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo insuficiente para realizar esta transferencia.'],
            ]);
        }

        return DB::transaction(function () use ($account, $destinationAccount, $validated) {
            // Restar de la cuenta origen
            $account->balance -= $validated['amount'];
            $account->save();

            // Sumar a la cuenta destino
            $destinationAccount->balance += $validated['amount'];
            $destinationAccount->save();

            // Registrar transacción de salida
            $outTransaction = Transaction::create([
                'account_id' => $account->id,
                'type' => 'transferencia_enviada',
                'amount' => $validated['amount'],
                'balance_after' => $account->balance,
                'description' => $validated['description'] ?? 'Transferencia enviada',
                'related_account_id' => $destinationAccount->id,
            ]);

            // Registrar transacción de entrada
            Transaction::create([
                'account_id' => $destinationAccount->id,
                'type' => 'transferencia_recibida',
                'amount' => $validated['amount'],
                'balance_after' => $destinationAccount->balance,
                'description' => $validated['description'] ?? 'Transferencia recibida',
                'related_account_id' => $account->id,
            ]);

            return response()->json([
                'message' => 'Transferencia realizada exitosamente',
                'transaction' => $outTransaction,
                'new_balance' => $account->balance,
            ], 201);
        });
    }

    private function authorizeAccount(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'No autorizado');
        }
    }
}