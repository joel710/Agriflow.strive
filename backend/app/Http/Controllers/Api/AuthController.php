<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Producer;
use App\Models\Customer;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB; // Pour les transactions

class AuthController extends Controller
{
    /**
     * Register a new user (customer or producer).
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:producteur,client',

            // Producer specific fields (conditionally required)
            'farm_name' => 'required_if:role,producteur|string|max:255',
            'siret' => 'nullable|string|size:14|unique:producers,siret',
            'experience_years' => 'nullable|integer|min:0',
            'farm_type' => 'nullable|string|in:cultures,elevage,mixte',
            'surface_hectares' => 'nullable|numeric|min:0',
            'farm_address' => 'nullable|string',
            'certifications' => 'nullable|string', // Consider JSON or separate table for multi-select
            'delivery_availability' => 'nullable|string|in:3j,5j,7j',
            'farm_description' => 'nullable|string',
            'farm_photo_url' => 'nullable|string|url',

            // Customer specific fields (conditionally required)
            'delivery_address' => 'required_if:role,client|string', // Assuming one primary address for now
            'food_preferences' => 'nullable|string|in:bio,local,aucune',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'] ?? null,
                'role' => $validatedData['role'],
                'is_active' => true, // Activate on registration by default
            ]);

            if ($validatedData['role'] === 'producteur') {
                Producer::create([
                    'user_id' => $user->id,
                    'farm_name' => $validatedData['farm_name'],
                    'siret' => $validatedData['siret'] ?? null,
                    'experience_years' => $validatedData['experience_years'] ?? null,
                    'farm_type' => $validatedData['farm_type'] ?? null,
                    'surface_hectares' => $validatedData['surface_hectares'] ?? null,
                    'farm_address' => $validatedData['farm_address'] ?? null,
                    'certifications' => $validatedData['certifications'] ?? null,
                    'delivery_availability' => $validatedData['delivery_availability'] ?? null,
                    'farm_description' => $validatedData['farm_description'] ?? null,
                    'farm_photo_url' => $validatedData['farm_photo_url'] ?? null,
                ]);
            } elseif ($validatedData['role'] === 'client') {
                Customer::create([
                    'user_id' => $user->id,
                    'delivery_address' => $validatedData['delivery_address'],
                    'food_preferences' => $validatedData['food_preferences'] ?? null,
                ]);
            }

            DB::commit();

            // Optionally log the user in and issue a token immediately
            // $token = $user->createToken('api-token')->plainTextToken;
            // return response()->json(['message' => 'User registered successfully', 'user' => $user, 'token' => $token], 201);

            return response()->json(['message' => 'Utilisateur enregistré avec succès. Veuillez vous connecter.', 'user_id' => $user->id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Authenticate the user and return a token.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255' // Optional: for naming the token
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email ou mot de passe incorrect.'], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout(); // Log out the user if inactive
            return response()->json(['message' => 'Votre compte est inactif. Veuillez contacter l\'administrateur.'], 403);
        }

        $user->last_login = now();
        $user->save();

        // Revoke all old tokens and issue a new one
        // $user->tokens()->delete(); // Optional: if you want only one active session per user
        $tokenName = $credentials['device_name'] ?? $request->userAgent() ?? 'api-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        $user->load(['producer', 'customer', 'settings', 'wallet']); // Eager load related data

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => $user, // Send user data along with profile, settings, wallet
        ]);
    }

    /**
     * Log the user out (revoke the current token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Get the authenticated User.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['producer', 'customer', 'settings', 'wallet']);
        return response()->json($user);
    }
}
