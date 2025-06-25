<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Producer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProducerProfileController extends Controller
{
    /**
     * Get the authenticated producer's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user->isProducer() || !$user->producer) {
            return response()->json(['message' => 'Profil producteur non trouvé ou non autorisé.'], 403);
        }
        return response()->json($user->producer);
    }

    /**
     * Update the authenticated producer's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user->isProducer() || !$user->producer) {
            return response()->json(['message' => 'Profil producteur non trouvé ou non autorisé.'], 403);
        }

        $producer = $user->producer;

        $validatedData = $request->validate([
            'farm_name' => 'sometimes|required|string|max:255',
            'siret' => ['nullable', 'string', 'size:14', Rule::unique('producers', 'siret')->ignore($producer->id)],
            'experience_years' => 'nullable|integer|min:0',
            'farm_type' => 'nullable|string|in:cultures,elevage,mixte',
            'surface_hectares' => 'nullable|numeric|min:0',
            'farm_address' => 'nullable|string',
            'certifications' => 'nullable|string',
            'delivery_availability' => 'nullable|string|in:3j,5j,7j',
            'farm_description' => 'nullable|string',
            'farm_photo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // For new photo upload
            'remove_farm_photo' => 'nullable|boolean', // To remove existing photo
        ]);

        if ($request->hasFile('farm_photo_file')) {
            // Delete old photo if exists
            if ($producer->farm_photo_url) {
                $oldPhotoPath = str_replace(Storage::url(''), '', $producer->farm_photo_url);
                Storage::disk('public')->delete($oldPhotoPath);
            }
            $photoPath = $request->file('farm_photo_file')->store('farm_photos', 'public');
            $validatedData['farm_photo_url'] = Storage::url($photoPath);
        } elseif ($request->boolean('remove_farm_photo') && $producer->farm_photo_url) {
            $oldPhotoPath = str_replace(Storage::url(''), '', $producer->farm_photo_url);
            Storage::disk('public')->delete($oldPhotoPath);
            $validatedData['farm_photo_url'] = null;
        }

        // Remove file upload keys from validatedData before mass update
        unset($validatedData['farm_photo_file']);
        unset($validatedData['remove_farm_photo']);

        $producer->update($validatedData);

        return response()->json(['message' => 'Profil producteur mis à jour avec succès.', 'producer' => $producer]);
    }
}
