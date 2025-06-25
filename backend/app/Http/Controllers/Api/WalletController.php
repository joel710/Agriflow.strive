<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet; // Make sure Wallet model is created

class WalletController extends Controller
{
    /**
     * Display the authenticated user's wallet and recent transactions.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Ensure wallet exists, create if not (idempotent)
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'currency' => 'XOF'] // Default values
        );

        // Load recent transactions (e.g., last 20)
        $transactions = $wallet->transactions()
                                ->orderBy('created_at', 'desc')
                                ->paginate(15); // Paginate transactions

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    // Pas d'endpoint pour ajouter des fonds directement via API pour l'instant.
    // Cela se ferait via des processus admin ou des remboursements, paiements de producteurs.

    // Exemple de méthode (pour admin ou système) pour créditer un portefeuille:
    // public function creditWallet(Request $request) {
    //     $validated = $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'amount' => 'required|numeric|min:0.01',
    //         'description' => 'required|string',
    //     ]);
    //     $user = User::find($validated['user_id']);
    //     $wallet = $user->wallet()->firstOrCreate(['currency' => 'XOF']);
    //     $wallet->balance += $validated['amount'];
    //     $wallet->save();
    //     $wallet->transactions()->create([
    //         'type' => 'credit',
    //         'amount' => $validated['amount'],
    //         'description' => $validated['description']
    //     ]);
    //     return response()->json(['message' => 'Portefeuille crédité.', 'wallet' => $wallet]);
    // }
}
