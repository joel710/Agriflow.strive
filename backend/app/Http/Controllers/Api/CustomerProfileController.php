<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;

class CustomerProfileController extends Controller
{
    /**
     * Get the authenticated customer's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user->isCustomer() || !$user->customer) {
            return response()->json(['message' => 'Profil client non trouvé ou non autorisé.'], 403);
        }
        return response()->json($user->customer);
    }

    /**
     * Update the authenticated customer's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user->isCustomer() || !$user->customer) {
            return response()->json(['message' => 'Profil client non trouvé ou non autorisé.'], 403);
        }

        $customer = $user->customer;

        $validatedData = $request->validate([
            'delivery_address' => 'sometimes|required|string',
            'food_preferences' => 'nullable|string|in:bio,local,aucune',
        ]);

        $customer->update($validatedData);

        return response()->json(['message' => 'Profil client mis à jour avec succès.', 'customer' => $customer]);
    }
}
