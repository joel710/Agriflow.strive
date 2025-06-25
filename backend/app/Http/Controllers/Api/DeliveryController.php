<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    /**
     * Display a listing of deliveries (e.g., for admin or producer).
     * For simplicity, admin sees all, producer sees deliveries for their orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Delivery::query()->with(['order.customer.user:id,email', 'order.items.product:id,name']); // Eager load details

        if ($user->isProducer() && $user->producer) {
            $producerId = $user->producer->id;
            $query->whereHas('order.items.product', function ($q) use ($producerId) {
                $q->where('producer_id', $producerId);
            });
        } elseif (!$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // TODO: Add filters (status, date range, etc.)
        $perPage = $request->input('per_page', 15);
        $deliveries = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($deliveries);
    }

    /**
     * Store a newly created delivery resource in storage.
     * Typically linked to an order. Could be created automatically when order is confirmed,
     * or manually by producer/admin.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !($user->isProducer() && $user->producer)) {
            return response()->json(['message' => 'Action non autorisée pour créer une livraison.'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id|unique:deliveries,order_id', // One delivery per order
            'status' => ['nullable', Rule::in(['en_attente', 'en_preparation', 'en_cours', 'livree', 'annulee', 'echec_livraison'])],
            'tracking_number' => 'nullable|string|max:100|unique:deliveries,tracking_number',
            'estimated_delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
            'delivery_person_name' => 'nullable|string|max:255',
            'delivery_person_phone' => 'nullable|string|max:20',
            'delivery_notes' => 'nullable|string',
        ]);

        $order = Order::find($validated['order_id']);

        // Authorization: if producer, check if they are part of this order
        if ($user->isProducer()) {
            $isConcerned = $order->items()->whereHas('product', function ($q) use ($user) {
                $q->where('producer_id', $user->producer->id);
            })->exists();
            if (!$isConcerned && !$order->items()->whereNull('product_id')->exists()) { // Allow if order has no specific product or producer is part of it
                 return response()->json(['message' => 'Vous n\'êtes pas autorisé à gérer la livraison pour cette commande.'], 403);
            }
        }

        // Default status if order is confirmed and delivery is being created
        if (empty($validated['status']) && in_array($order->status, ['confirmee', 'en_preparation'])) {
            $validated['status'] = 'en_preparation'; // Or 'en_attente' if further action needed
        }


        $delivery = Delivery::create($validated);
        $delivery->load(['order.customer.user:id,email']);

        activity()->log("Delivery created for order #{$order->id} by user #{$user->id}");
        return response()->json($delivery, 201);
    }

    /**
     * Display the specified delivery resource.
     */
    public function show(Request $request, Delivery $delivery)
    {
        $user = $request->user();
        $order = $delivery->order;

        // Authorization: customer of the order, producer concerned, or admin
        $isCustomerOwner = ($user->isCustomer() && $user->customer && $order->customer_id === $user->customer->id);
        $isProducerConcerned = false;
        if ($user->isProducer() && $user->producer) {
            $isProducerConcerned = $order->items()->whereHas('product', function ($q) use ($user) {
                $q->where('producer_id', $user->producer->id);
            })->exists();
        }

        if (!$isCustomerOwner && !$isProducerConcerned && !$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé à voir cette livraison.'], 403);
        }

        $delivery->load(['order.customer.user:id,email', 'order.items.product:id,name']);
        return response()->json($delivery);
    }

    /**
     * Update the specified delivery resource in storage.
     */
    public function update(Request $request, Delivery $delivery)
    {
        $user = $request->user();
        $order = $delivery->order;

        // Authorization: producer concerned or admin
        $isProducerConcerned = false;
        if ($user->isProducer() && $user->producer) {
            $isProducerConcerned = $order->items()->whereHas('product', function ($q) use ($user) {
                $q->where('producer_id', $user->producer->id);
            })->exists();
        }
        if (!$isProducerConcerned && !$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé à mettre à jour cette livraison.'], 403);
        }

        $validated = $request->validate([
            'status' => ['sometimes','required', Rule::in(['en_attente', 'en_preparation', 'en_cours', 'livree', 'annulee', 'echec_livraison'])],
            'tracking_number' => ['nullable', 'string', 'max:100', Rule::unique('deliveries', 'tracking_number')->ignore($delivery->id)],
            'estimated_delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
            'actual_delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
            'delivery_person_name' => 'nullable|string|max:255',
            'delivery_person_phone' => 'nullable|string|max:20',
            'delivery_notes' => 'nullable|string',
        ]);

        $delivery->update($validated);

        // If delivery status changes, potentially update order status too
        if ($request->has('status')) {
            if ($validated['status'] === 'livree' && $order->status !== 'livree') {
                $order->status = 'livree';
                $order->payment_status = $order->payment_status === 'en_attente' ? 'payee' : $order->payment_status; // Ex: COD
                $order->save();
                // TODO: Notify customer of delivery
            } elseif (in_array($validated['status'], ['en_cours', 'en_preparation']) && $order->status !== 'en_livraison' && $order->status !== 'en_preparation') {
                 $order->status = $validated['status'] === 'en_cours' ? 'en_livraison' : 'en_preparation';
                 $order->save();
                  // TODO: Notify customer
            }
        }

        activity()->log("Delivery #{$delivery->id} for order #{$order->id} updated by user #{$user->id}. New status: {$delivery->status}");
        $delivery->load(['order.customer.user:id,email']);
        return response()->json($delivery);
    }

    /**
     * Remove the specified delivery resource from storage (not typical).
     * Usually deliveries are cancelled, not deleted.
     */
    // public function destroy(Delivery $delivery)
    // {
    //     // Add authorization
    //     $delivery->delete();
    //     return response()->json(null, 204);
    // }
}
