<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\UserSetting;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile information (basic user, producer/customer profile, settings, wallet).
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['producer', 'customer', 'settings', 'wallet']);
        return response()->json($user);
    }

    /**
     * Update the authenticated user's basic profile information.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'phone' => 'nullable|string|max:20',
            // Email update is more complex due to verification, handle separately if needed.
            // 'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($request->has('phone')) {
            $user->phone = $validatedData['phone'];
        }

        // Add other updatable fields here if any (e.g. name if you add it to User model)

        $user->save();

        $user->load(['producer', 'customer', 'settings', 'wallet']);
        return response()->json(['message' => 'Profil mis à jour avec succès.', 'user' => $user]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 422);
        }

        $user->password = Hash::make($validatedData['password']);
        $user->save();

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    /**
     * Get the authenticated user's settings.
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [ // Default values if creating for the first time
                'notification_email' => true,
                'notification_sms' => false,
                'notification_app' => true,
                'language' => 'fr',
                'theme' => 'light',
            ]
        );
        return response()->json($settings);
    }

    /**
     * Update the authenticated user's settings.
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);

        $validatedData = $request->validate([
            'notification_email' => 'sometimes|boolean',
            'notification_sms' => 'sometimes|boolean',
            'notification_app' => 'sometimes|boolean',
            'language' => 'sometimes|string|in:fr,en', // Add more languages as needed
            'theme' => 'sometimes|string|in:light,dark',
        ]);

        $settings->update($validatedData);

        return response()->json(['message' => 'Paramètres mis à jour avec succès.', 'settings' => $settings]);
    }
}
