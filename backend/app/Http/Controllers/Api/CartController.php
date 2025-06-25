<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User; // ou Cart model si on le crée

// Pour cette implémentation, nous allons simuler la gestion du panier
// en session pour les invités et stocker une version simple pour les utilisateurs connectés.
// Une solution plus robuste utiliserait des tables `carts` et `cart_items`.

class CartController extends Controller
{
    // Helper pour obtenir le panier (depuis la session ou une source persistante pour user connecté)
    private function getCart(Request $request)
    {
        if ($request->user()) {
            // Pour un utilisateur connecté, on pourrait charger depuis la DB.
            // Pour simplifier, on va utiliser la session, mais on pourrait aussi
            // ajouter une colonne 'cart_data' (JSON) au modèle User.
            // $cart = $request->user()->cart_data ?? [];
            // Ici, on simule avec la session même pour user connecté pour alléger.
            return $request->session()->get('cart', []);
        }
        return $request->session()->get('cart', []);
    }

    private function saveCart(Request $request, array $cart)
    {
        $request->session()->put('cart', $cart);
        if ($request->user()) {
            // $request->user()->update(['cart_data' => $cart]); // Si on stocke sur le User model
        }
    }

    /**
     * Display the current user's cart.
     */
    public function index(Request $request)
    {
        $cart = $this->getCart($request);
        $detailedCart = [];
        $totalAmount = 0;

        $productIds = array_keys($cart);
        if (!empty($productIds)) {
            $products = Product::with('producer:id,farm_name')->whereIn('id', $productIds)->where('is_available', true)->get()->keyBy('id');

            foreach ($cart as $productId => $item) {
                if (isset($products[$productId])) {
                    $product = $products[$productId];
                    $quantity = (int)$item['quantity'];
                    $itemTotal = $product->price * $quantity;
                    $totalAmount += $itemTotal;

                    $detailedCart[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'price' => (float)$product->price,
                        'quantity' => $quantity,
                        'unit' => $product->unit,
                        'image_url' => $product->image_url,
                        'item_total' => $itemTotal,
                        'producer_name' => $product->producer->farm_name ?? 'N/A',
                        'stock_quantity' => $product->stock_quantity, // Pour vérification côté client
                        'is_available' => $product->is_available,
                    ];
                } else {
                    // Product might have become unavailable or removed, remove from cart
                    unset($cart[$productId]);
                     $this->saveCart($request, $cart); // Resave cart without the missing item
                }
            }
        }

        return response()->json([
            'items' => $detailedCart,
            'total_items' => count($detailedCart), // ou array_sum(array_column($cart, 'quantity')) pour total de pièces
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Add an item to the cart or update its quantity.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];

        $product = Product::where('id', $productId)->where('is_available', true)->first();

        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé ou indisponible.'], 404);
        }

        if ($product->stock_quantity < $quantity) {
            return response()->json(['message' => 'Quantité en stock insuffisante pour ' . $product->name . '. Stock actuel: ' . $product->stock_quantity], 400);
        }

        $cart = $this->getCart($request);

        if (isset($cart[$productId])) {
            // Si on veut que l'ajout remplace la quantité :
            // $cart[$productId]['quantity'] = $quantity;
            // Si on veut que l'ajout s'additionne (plus commun pour un "add to cart")
            // Mais cet endpoint est plus un "set quantity"
            $cart[$productId]['quantity'] = $quantity; // Mettre à jour la quantité
        } else {
            $cart[$productId] = ['quantity' => $quantity];
        }

        // Vérifier si la nouvelle quantité totale ne dépasse pas le stock
        if ($product->stock_quantity < $cart[$productId]['quantity']) {
            // Remettre à la quantité max possible ou retourner une erreur
            // $cart[$productId]['quantity'] = $product->stock_quantity;
            return response()->json(['message' => 'Quantité totale demandée (' . $cart[$productId]['quantity'] . ') dépasse le stock pour ' . $product->name . '. Stock actuel: ' . $product->stock_quantity], 400);
        }


        $this->saveCart($request, $cart);

        return $this->index($request); // Return the updated cart
    }

    /**
     * Increment item quantity in cart.
     */
    public function increment(Request $request, $productId)
    {
        $product = Product::where('id', $productId)->where('is_available', true)->first();
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé ou indisponible.'], 404);
        }

        $cart = $this->getCart($request);
        if (!isset($cart[$productId])) {
            return response()->json(['message' => 'Produit non trouvé dans le panier.'], 404);
        }

        $newQuantity = $cart[$productId]['quantity'] + 1;
        if ($product->stock_quantity < $newQuantity) {
            return response()->json(['message' => 'Stock insuffisant pour augmenter la quantité.'], 400);
        }
        $cart[$productId]['quantity'] = $newQuantity;
        $this->saveCart($request, $cart);
        return $this->index($request);
    }

    /**
     * Decrement item quantity in cart.
     */
    public function decrement(Request $request, $productId)
    {
        $cart = $this->getCart($request);
        if (!isset($cart[$productId])) {
            return response()->json(['message' => 'Produit non trouvé dans le panier.'], 404);
        }

        $newQuantity = $cart[$productId]['quantity'] - 1;
        if ($newQuantity < 1) {
            unset($cart[$productId]); // Remove item if quantity is less than 1
        } else {
            $cart[$productId]['quantity'] = $newQuantity;
        }
        $this->saveCart($request, $cart);
        return $this->index($request);
    }


    /**
     * Remove an item from the cart.
     * The $productId here is the product_id, not the cart_item_id.
     */
    public function destroy(Request $request, $productId)
    {
        $cart = $this->getCart($request);

        if (!isset($cart[$productId])) {
            return response()->json(['message' => 'Produit non trouvé dans le panier.'], 404);
        }

        unset($cart[$productId]);
        $this->saveCart($request, $cart);

        return $this->index($request); // Return the updated cart
    }

    /**
     * Clear the entire cart.
     */
    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        if ($request->user()) {
            // $request->user()->update(['cart_data' => []]);
        }
        return response()->json(['message' => 'Panier vidé avec succès.', 'items' => [], 'total_amount' => 0, 'total_items' => 0]);
    }
}
