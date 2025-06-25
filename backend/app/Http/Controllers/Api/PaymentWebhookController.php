<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\PaymentService; // Importer le service
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle PayGate webhook notifications.
     */
    public function handlePaygateWebhook(Request $request)
    {
        Log::info('PayGate Webhook received:', $request->all());

        // 1. Vérifier la signature du webhook (essentiel pour la sécurité)
        // $signature = $request->header('X-Paygate-Signature');
        // $expectedSignature = hash_hmac('sha256', $request->getContent(), config('services.paygate.webhook_secret'));
        // if (!hash_equals($expectedSignature, $signature)) {
        //     Log::warning('PayGate Webhook: Invalid signature.');
        //     return response()->json(['error' => 'Invalid signature'], 400);
        // }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null; // e.g., 'payment.succeeded', 'payment.failed'
        $data = $payload['data'] ?? [];
        $orderId = $data['order_id'] ?? ($data['metadata']['order_id'] ?? null); // L'ID de commande de notre système
        $transactionId = $data['transaction_id'] ?? ($data['id'] ?? null); // L'ID de transaction de PayGate

        if (!$orderId || !$transactionId) {
            Log::warning('PayGate Webhook: Missing order_id or transaction_id.', $payload);
            return response()->json(['error' => 'Missing order_id or transaction_id'], 400);
        }

        $order = Order::find($orderId);
        if (!$order) {
            Log::warning("PayGate Webhook: Order #{$orderId} not found.");
            return response()->json(['error' => "Order #{$orderId} not found"], 404);
        }

        try {
            if ($eventType === 'payment.succeeded') {
                $this->paymentService->handlePaymentSuccess($order, 'paygate', $transactionId, $data);
                 activity()->log("Webhook: PayGate payment succeeded for order #{$order->id}");
            } elseif ($eventType === 'payment.failed') {
                $this->paymentService->handlePaymentFailure($order, 'paygate', $transactionId, $data);
                activity()->error("Webhook: PayGate payment failed for order #{$order->id}");
            } else {
                Log::info("PayGate Webhook: Unhandled event type '{$eventType}' for order #{$orderId}.");
            }
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error("PayGate Webhook: Error processing event for order #{$orderId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal server error while processing webhook'], 500);
        }
    }

    /**
     * Handle TMoney webhook notifications.
     */
    public function handleTMoneyWebhook(Request $request)
    {
        Log::info('TMoney Webhook received:', $request->all());
        // Logique similaire à PayGate: vérifier signature, parser payload, appeler PaymentService
        // $this->paymentService->handlePaymentSuccess($order, 'tmoney', $transactionId, $data);
        // $this->paymentService->handlePaymentFailure($order, 'tmoney', $transactionId, $data);
        activity()->log("TMoney webhook received and processed (simulated).");
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle Moov webhook notifications.
     */
    public function handleMoovWebhook(Request $request)
    {
        Log::info('Moov Webhook received:', $request->all());
        // Logique similaire
        activity()->log("Moov webhook received and processed (simulated).");
        return response()->json(['status' => 'success'], 200);
    }


    // Routes de redirection après paiement (si la passerelle redirige le client)
    // Ces routes sont typiquement pour le navigateur du client, pas des webhooks serveur-à-serveur.
    public function paymentSuccessRedirect(Request $request, $orderId) {
        $order = Order::findOrFail($orderId);
        // Vérifier le statut réel du paiement, ne pas se fier uniquement à la redirection.
        // La page frontend affichera le statut de la commande.
        Log::info("Client redirected to payment success page for order #{$orderId}. Method: " . $request->query('method'));
        // Idéalement, le frontend a une route /commande/succes/{orderId}
        // et cette route Laravel redirige vers l'URL frontend.
        $frontendSuccessUrl = config('app.frontend_url', 'http://localhost:4200') . "/commande-succes/{$orderId}";
        return redirect()->away($frontendSuccessUrl);
    }

    public function paymentFailureRedirect(Request $request, $orderId) {
        $order = Order::find($orderId); // Peut être null si la commande n'a pas été créée
        Log::info("Client redirected to payment failure page for order #{$orderId}. Method: " . $request->query('method'));
        $frontendFailureUrl = config('app.frontend_url', 'http://localhost:4200') . "/commande-echec/{$orderId}";
        return redirect()->away($frontendFailureUrl);
    }
     public function paymentCancelledRedirect(Request $request, $orderId) {
        Log::info("Client redirected to payment cancelled page for order #{$orderId}. Method: " . $request->query('method'));
        $frontendCancelledUrl = config('app.frontend_url', 'http://localhost:4200') . "/panier"; // ou une page spécifique
        return redirect()->away($frontendCancelledUrl);
    }

}
