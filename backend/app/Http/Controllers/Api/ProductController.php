<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // For file uploads
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * Publicly accessible.
     */
    public function index(Request $request)
    {
        $request->validate([
            'producer_id' => 'nullable|integer|exists:producers,id',
            'is_available' => 'nullable|boolean',
            'is_bio' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Product::query()->with('producer:id,farm_name'); // Eager load producer info

        if ($request->filled('producer_id')) {
            $query->where('producer_id', $request->producer_id);
        }
        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }
        if ($request->filled('is_bio')) {
            $query->where('is_bio', $request->boolean('is_bio'));
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // By default, only show available products to public
        if (!Auth::guard('sanctum')->check()) { // No authenticated user or not admin/owner
             $query->where('is_available', true);
        }
        // Authenticated users (especially admin or producer owner) might see unavailable products too,
        // or this logic can be refined based on roles if needed for specific views.

        $perPage = $request->input('per_page', 15);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     * Accessible to authenticated producers and admins.
     */
    public function store(Request $request)
    {
        $user = Auth::user(); // Or $request->user()

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'stock_quantity' => 'required|integer|min:0',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // image_file for actual file
            'is_bio' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            // If admin is creating, producer_id might be required.
            // If producer is creating, producer_id is derived.
            'producer_id' => ($user->isAdmin() ? 'required|integer|exists:producers,id' : 'nullable|integer'),
        ]);

        if ($user->isProducer()) {
            if (!$user->producer) {
                return response()->json(['message' => 'Profil producteur non trouvé pour cet utilisateur.'], 403);
            }
            // Producer can only create products for themselves
            $validatedData['producer_id'] = $user->producer->id;
        } elseif (!$user->isAdmin()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        // If admin, producer_id from request is used (already validated by 'exists:producers,id')


        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('product_images', 'public');
        }

        $product = Product::create([
            'producer_id' => $validatedData['producer_id'],
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'price' => $validatedData['price'],
            'unit' => $validatedData['unit'],
            'stock_quantity' => $validatedData['stock_quantity'],
            'image_url' => $imagePath ? Storage::url($imagePath) : null, // Store URL if image uploaded
            'is_bio' => $validatedData['is_bio'] ?? false,
            'is_available' => $validatedData['is_available'] ?? true,
        ]);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     * Publicly accessible, but might show more details for authenticated users.
     */
    public function show(Product $product) // Route model binding
    {
        // Logic to hide unavailable products from public, similar to index() if needed
        if (!$product->is_available && !Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Produit non trouvé ou non disponible.'], 404);
        }
        $product->load('producer:id,farm_name,farm_photo_url');
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     * Accessible to the producer owner or admin.
     */
    public function update(Request $request, Product $product)
    {
        $user = Auth::user();

        // Authorization: only producer owner or admin can update
        if (!($user->isAdmin() || ($user->isProducer() && $user->producer && $user->producer->id === $product->producer_id))) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|max:50',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // For new image upload
            'remove_image' => 'nullable|boolean', // To remove existing image
            'is_bio' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            // Admin might change producer_id
            'producer_id' => ($user->isAdmin() ? 'sometimes|required|integer|exists:producers,id' : 'nullable'),
        ]);

        // Handle producer_id update only if admin and provided
        if ($user->isAdmin() && $request->has('producer_id')) {
            $product->producer_id = $validatedData['producer_id'];
        } elseif ($request->has('producer_id') && $product->producer_id != $validatedData['producer_id']) {
            // Producer trying to change producer_id - forbidden
             return response()->json(['message' => 'Un producteur ne peut pas changer le propriétaire du produit.'], 403);
        }


        if ($request->hasFile('image_file')) {
            // Delete old image if exists
            if ($product->image_url) {
                $oldImagePath = str_replace(Storage::url(''), '', $product->image_url);
                Storage::disk('public')->delete($oldImagePath);
            }
            $imagePath = $request->file('image_file')->store('product_images', 'public');
            $product->image_url = Storage::url($imagePath);
        } elseif ($request->boolean('remove_image') && $product->image_url) {
            $oldImagePath = str_replace(Storage::url(''), '', $product->image_url);
            Storage::disk('public')->delete($oldImagePath);
            $product->image_url = null;
        }

        // Mass update other fields from validated data, excluding image_file, remove_image and producer_id (handled separately)
        $updateData = collect($validatedData)->except(['image_file', 'remove_image', 'producer_id'])->all();
        if(count($updateData) > 0) {
            $product->update($updateData);
        } else {
            // If only producer_id was changed by admin, or only image was handled
            $product->save(); // ensure producer_id or image_url changes are saved
        }


        return response()->json($product->fresh()->load('producer:id,farm_name')); // Return updated product with fresh data
    }

    /**
     * Remove the specified resource from storage.
     * Accessible to the producer owner or admin.
     */
    public function destroy(Product $product)
    {
        $user = Auth::user();

        // Authorization: only producer owner or admin can delete
        if (!($user->isAdmin() || ($user->isProducer() && $user->producer && $user->producer->id === $product->producer_id))) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        // Delete image from storage if exists
        if ($product->image_url) {
            $imagePath = str_replace(Storage::url(''), '', $product->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        try {
            $product->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle potential foreign key constraint violation if product is in an order_item
            // A better approach might be to soft delete products or prevent deletion if they are part of orders.
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return response()->json(['message' => 'Impossible de supprimer ce produit car il est référencé dans des commandes. Vous pouvez le rendre indisponible à la place.'], 409); // 409 Conflict
            }
            throw $e; // Re-throw other DB exceptions
        }


        return response()->json(null, 204); // No content
    }
}
