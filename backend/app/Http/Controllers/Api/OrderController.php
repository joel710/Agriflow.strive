<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Exceptions\InsufficientStockException; // Custom exception

class OrderController extends Controller
{
    /**
     * Store a newly created order in storage.
     * Accessible only to authenticated customers.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isCustomer() || !$user->customer) {
            return response()->json(['message' => 'Seuls les clients peuvent passer commande.'], 403);
        }
        $customer = $user->customer;

        $validatedData = $request->validate([
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|integer|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string|max:1000', // Could be pre-filled from customer profile
            'delivery_notes' => 'nullable|string|max:1000',
            'payment_method_slug' => 'required|string', // e.g., 'paygate', 'tmoney', 'moov', 'wallet'
            // 'promo_code' => 'nullable|string|exists:promo_codes,code', // Future enhancement
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($validatedData['cart_items'] as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                if (!$product || !$product->is_available) {
                    throw new \Exception("Le produit '{$product->name}' n'est plus disponible.");
                }
                if ($product->stock_quantity < $cartItem['quantity']) {
                    // Custom exception for more specific handling if needed
                    // throw new InsufficientStockException("Stock insuffisant pour le produit '{$product->name}'. Demandé: {$cartItem['quantity']}, Disponible: {$product->stock_quantity}");
                     throw new \Exception("Stock insuffisant pour le produit '{$product->name}'. Demandé: {$cartItem['quantity']}, Disponible: {$product->stock_quantity}");
                }

                $itemTotal = $product->price * $cartItem['quantity'];
                $totalAmount += $itemTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $product->price, // Price at the time of order
                    'total_price' => $itemTotal,
                ];

                // Décrémenter le stock (optimistic lock or check version if high concurrency)
                $product->stock_quantity -= $cartItem['quantity'];
                $product->save();
            }

            // TODO: Vérifier si le totalAmount est > 0
            // TODO: Appliquer les codes promo si présents

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_amount' => $totalAmount,
                'status' => 'en_attente', // Default status, will be updated after payment
                'payment_status' => 'en_attente',
                'payment_method' => $validatedData['payment_method_slug'],
                'delivery_address' => $validatedData['delivery_address'],
                'delivery_notes' => $validatedData['delivery_notes'] ?? null,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData); // Create OrderItem linked to the order
            }

            // TODO: Vider le panier de l'utilisateur après la création de la commande
            // $request->session()->forget('cart'); // Si le panier est en session
            // Ou logique de suppression du panier en BDD si applicable (déplacé après init paiement)


            // Initiate Payment
            $paymentService = new \App\Services\PaymentService();
            $paymentDetails = $request->input('payment_details', []); // e.g., phone for mobile money
            $paymentResponse = $paymentService->initiatePayment($order, $validatedData['payment_method_slug'], $paymentDetails);

            if (!$paymentResponse['success']) {
                // Le paiement n'a pas pu être initié, la commande est créée mais en attente.
                // On pourrait choisir de rollback la commande ou de la laisser en attente.
                // Pour l'instant, on la laisse et le front gèrera le message d'erreur.
                // Alternative: throw new \Exception($paymentResponse['message']); pour rollback.
                DB::commit(); // Commit order, but payment failed to initiate
                $order->load('items.product');
                return response()->json([
                    'message' => 'Commande créée, mais l\'initiation du paiement a échoué: ' . $paymentResponse['message'],
                    'order' => $order,
                    'payment_initiation_failed' => true,
                    'payment_error_message' => $paymentResponse['message']
                ], 201); // 201 car la commande est créée
            }

            // Vider le panier APRÈS une tentative d'initiation de paiement réussie (ou même si échec, selon la logique métier)
            if ($request->session()->has('cart')) { // Vérifier si la session a un panier
                 $request->session()->forget('cart');
            }


            DB::commit();
            $order->load('items.product');

            // TODO: Envoyer une notification de nouvelle commande (au client, à l'admin, au producteur)
            // $user->notify(new NewOrderNotification($order));

            return response()->json([
                'message' => 'Commande créée avec succès. ' . $paymentResponse['message'],
                'order' => $order,
                'redirect_url' => $paymentResponse['redirect_url'] ?? null,
                'transaction_id' => $paymentResponse['transaction_id'] ?? null,
                'payment_sdk_data' => $paymentResponse['data_for_sdk'] ?? null,
            ], 201);

        } catch (InsufficientStockException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'INSUFFICIENT_STOCK'], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log l'erreur serveur
            \Log::error('Erreur création commande: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur lors de la création de la commande: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Display a listing of the authenticated user's orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $ordersQuery = null;

        if ($user->isCustomer() && $user->customer) {
            $ordersQuery = Order::where('customer_id', $user->customer->id);
        } elseif ($user->isProducer() && $user->producer) {
            // Les producteurs voient les commandes qui contiennent leurs produits
            $producerId = $user->producer->id;
            $ordersQuery = Order::whereHas('items.product', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })->with(['items' => function($query) use ($producerId) {
                // Charger seulement les items du producteur pour cette commande
                $query->whereHas('product', function ($subQuery) use ($producerId) {
                    $subQuery->where('producer_id', $producerId);
                })->with('product:id,name,image_url');
            }, 'customer.user:id,email']); // Charger aussi le client
        } elseif ($user->isAdmin()) {
            $ordersQuery = Order::query()->with(['items.product:id,name,image_url', 'customer.user:id,email']);
        } else {
            return response()->json(['message' => 'Non autorisé à voir les commandes.'], 403);
        }

        $perPage = $request->input('per_page', 10);
        $orders = $ordersQuery->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($orders);
    }


    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order) // Route model binding
    {
        $user = $request->user();

        // Autorisation : client propriétaire, producteur concerné, ou admin
        $isOwner = ($user->isCustomer() && $user->customer && $order->customer_id === $user->customer->id);

        $isProducerConcerned = false;
        if ($user->isProducer() && $user->producer) {
            $producerId = $user->producer->id;
            $isProducerConcerned = $order->items()->whereHas('product', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })->exists();
        }

        if (!$isOwner && !$isProducerConcerned && !$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé à voir cette commande.'], 403);
        }

        // Charger les relations nécessaires
        $order->load(['customer.user:id,email,phone', 'items.product.producer:id,farm_name', 'delivery', 'invoice']);

        // Si c'est un producteur, on peut filtrer les items pour ne montrer que les siens (si pas déjà fait)
        // ou ajouter une propriété pour indiquer quels items sont les siens.

        return response()->json($order);
    }

    /**
     * Update the status of an order (e.g., by producer or admin).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $user = $request->user();

        // Seuls producteurs (pour leurs items) et admins peuvent changer le statut.
        // La logique exacte de qui peut changer vers quel statut peut être complexe.
        // Ex: un producteur peut marquer "en_preparation" ou "pret_a_expedier" pour ses items.
        // Un admin peut changer n'importe quel statut.

        if (!$user->isAdmin() && !($user->isProducer() && $user->producer)) {
             return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        // Si producteur, vérifier qu'il est concerné par la commande
        if ($user->isProducer()) {
            $producerId = $user->producer->id;
            $isConcerned = $order->items()->whereHas('product', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })->exists();
            if (!$isConcerned) {
                return response()->json(['message' => 'Vous n\'êtes pas concerné par cette commande.'], 403);
            }
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree', 'annulee'])],
            // On pourrait aussi valider 'payment_status' ici si l'admin le modifie manuellement
        ]);

        // TODO: Logique de transition de statut plus fine (ex: ne pas passer de 'livree' à 'en_preparation')

        $order->status = $validated['status'];

        // Si la commande est annulée, gérer la réintégration des stocks
        if ($validated['status'] === 'annulee' && $order->getOriginal('status') !== 'annulee') {
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->stock_quantity += $item->quantity;
                    $product->save();
                }
            }
            // Potentiellement mettre à jour payment_status à 'remboursee' si déjà 'payee'
            if($order->payment_status === 'payee') {
                $order->payment_status = 'remboursee';
                // TODO: Déclencher un remboursement réel si applicable
            }
        }

        $order->save();
        $order->load(['customer.user:id,email,phone', 'items.product.producer:id,farm_name', 'delivery', 'invoice']);

        // TODO: Notification de changement de statut
        // $order->customer->user->notify(new OrderStatusUpdatedNotification($order));

        return response()->json($order);
    }

}
