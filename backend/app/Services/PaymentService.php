<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http; // Laravel HTTP Client
use Illuminate\Support\Str;

class PaymentService
{
    protected $paygateConfig;
    protected $tmoneyConfig;
    protected $moovConfig;

    public function __construct()
    {
        $this->paygateConfig = config('services.paygate');
        $this->tmoneyConfig = config('services.tmoney');
        $this->moovConfig = config('services.moov');
    }

    /**
     * Initiate a payment process for an order.
     *
     * @param Order $order
     * @param string $paymentMethodSlug
     * @param array $additionalDetails (e.g., phone number for mobile money, card details tokenized by frontend)
     * @return array ['success' => bool, 'message' => string, 'redirect_url' => string|null, 'transaction_id' => string|null, 'data_for_sdk' => array|null]
     */
    public function initiatePayment(Order $order, string $paymentMethodSlug, array $additionalDetails = [])
    {
        activity()->log("Initiating payment for order #{$order->id} with method {$paymentMethodSlug}");

        switch ($paymentMethodSlug) {
            case 'paygate': // Fictional PayGate integration
                return $this->initiatePaygatePayment($order, $additionalDetails);
            case 'tmoney':
                return $this->initiateTMoneyPayment($order, $additionalDetails);
            case 'moov':
                return $this->initiateMoovPayment($order, $additionalDetails);
            case 'wallet': // Paiement par portefeuille interne
                return $this->processWalletPayment($order);
            default:
                return ['success' => false, 'message' => 'Méthode de paiement non supportée.', 'redirect_url' => null, 'transaction_id' => null];
        }
    }

    private function initiatePaygatePayment(Order $order, array $details)
    {
        // Simulation d'une requête à l'API PayGate
        // $apiKey = $this->paygateConfig['api_key'];
        // $secretKey = $this->paygateConfig['secret_key'];
        // $endpoint = $this->paygateConfig['endpoint_url'] . '/payments';

        // $payload = [
        //     'amount' => $order->total_amount * 100, // Montant en centimes
        //     'currency' => 'XOF',
        //     'order_id' => $order->id,
        //     'reference' => 'AGRF-' . $order->id . '-' . Str::random(8),
        //     'description' => "Paiement pour commande Agriflow #" . $order->id,
        //     'customer_email' => $order->customer->user->email,
        //     'callback_url' => route('payment.paygate.callback'), // URL de callback pour PayGate
        //     'redirect_url' => route('payment.paygate.redirect', ['orderId' => $order->id]), // URL de redirection après paiement
        //     // ... autres détails requis par PayGate (ex: token de carte si tokenisation côté client)
        // ];

        // $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($endpoint, $payload);

        // if ($response->successful() && isset($response->json()['redirect_url'])) {
        //     $order->payment_transaction_id = $response->json()['transaction_id'] ?? Str::uuid()->toString();
        //     $order->save();
        //     return [
        //         'success' => true,
        //         'message' => 'Paiement initié. Redirection en cours...',
        //         'redirect_url' => $response->json()['redirect_url'],
        //         'transaction_id' => $order->payment_transaction_id
        //     ];
        // } else {
        //     activity()->error("PayGate initiation failed for order #{$order->id}: " . $response->body());
        //     return ['success' => false, 'message' => 'Échec de l\'initiation du paiement PayGate: ' . ($response->json()['error_message'] ?? 'Erreur inconnue'), 'redirect_url' => null, 'transaction_id' => null];
        // }

        // SIMULATION DE SUCCÈS pour PayGate pour l'instant
        $order->payment_transaction_id = 'PAYGATE_TXN_' . Str::uuid()->toString();
        $order->save();
        activity()->log("PayGate payment simulated for order #{$order->id}, transaction ID: {$order->payment_transaction_id}");
        return [
            'success' => true,
            'message' => 'Paiement PayGate simulé. Redirection en cours...',
            // L'URL de redirection serait fournie par PayGate. Pour la simulation :
            'redirect_url' => route('checkout.payment.success', ['orderId' => $order->id, 'method' => 'paygate', 'simulated' => 'true']),
            'transaction_id' => $order->payment_transaction_id
        ];
    }

    private function initiateTMoneyPayment(Order $order, array $details)
    {
        // Logique similaire pour TMoney
        // Vérifier que $details['phone_number'] est présent
        // $phoneNumber = $details['phone_number'] ?? null;
        // if (!$phoneNumber) return ['success' => false, 'message' => 'Numéro de téléphone requis pour TMoney.'];

        activity()->log("TMoney payment simulated for order #{$order->id}");
         $order->payment_transaction_id = 'TMONEY_TXN_' . Str::uuid()->toString();
         $order->save();
        // Simuler un succès et une redirection vers une page de statut de paiement
        return [
            'success' => true,
            'message' => 'Demande de paiement TMoney envoyée. Veuillez confirmer sur votre téléphone.',
            'redirect_url' => null, // Ou une page d'attente de confirmation
            'transaction_id' => $order->payment_transaction_id,
            'data_for_sdk' => ['message_to_display' => 'Veuillez composer *145# pour valider.'] // Ou similaire
        ];
    }

    private function initiateMoovPayment(Order $order, array $details)
    {
        // Logique similaire pour Moov
        activity()->log("Moov payment simulated for order #{$order->id}");
        $order->payment_transaction_id = 'MOOV_TXN_' . Str::uuid()->toString();
        $order->save();
        return [
            'success' => true,
            'message' => 'Demande de paiement Moov Money envoyée. Veuillez confirmer sur votre téléphone.',
            'redirect_url' => null,
            'transaction_id' => $order->payment_transaction_id,
             'data_for_sdk' => ['message_to_display' => 'Veuillez suivre les instructions sur votre mobile.']
        ];
    }

    private function processWalletPayment(Order $order)
    {
        $customer = $order->customer->user; // Assurez-vous que la relation est chargée
        if (!$customer->wallet || $customer->wallet->balance < $order->total_amount) {
            activity()->warning("Wallet payment failed for order #{$order->id}: insufficient balance or no wallet.");
            return ['success' => false, 'message' => 'Solde du portefeuille insuffisant.', 'redirect_url' => null, 'transaction_id' => null];
        }

        try {
            DB::beginTransaction();

            $customer->wallet->balance -= $order->total_amount;
            $customer->wallet->save();

            $customer->wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $order->total_amount,
                'description' => "Paiement pour commande Agriflow #{$order->id}",
            ]);

            $order->payment_status = 'payee';
            $order->status = 'confirmee'; // Ou 'en_preparation' si paiement direct = confirmation
            $order->payment_transaction_id = 'WALLET_TXN_' . Str::uuid()->toString();
            $order->paid_at = now(); // Ajouter une colonne paid_at à orders si besoin
            $order->save();

            // TODO: Logique de répartition des gains / crédit aux producteurs si applicable ici

            DB::commit();
            activity()->log("Wallet payment successful for order #{$order->id}");
            return [
                'success' => true,
                'message' => 'Paiement par portefeuille réussi.',
                'redirect_url' => route('checkout.payment.success', ['orderId' => $order->id, 'method' => 'wallet']), // Rediriger vers une page de succès
                'transaction_id' => $order->payment_transaction_id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            activity()->error("Wallet payment processing error for order #{$order->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du traitement du paiement par portefeuille.', 'redirect_url' => null, 'transaction_id' => null];
        }
    }


    /**
     * Handle a successful payment notification/callback.
     */
    public function handlePaymentSuccess(Order $order, string $paymentMethod, string $transactionId, array $data = [])
    {
        if ($order->payment_status === 'payee') {
            activity()->info("Order #{$order->id} already marked as paid. Transaction ID: {$transactionId}");
            return; // Déjà traité
        }

        $order->payment_status = 'payee';
        $order->status = 'confirmee'; // Ou 'en_preparation'
        $order->payment_transaction_id = $transactionId;
        // $order->paid_at = now(); // Si vous avez une colonne paid_at
        $order->save();

        activity()->log("Payment successful for order #{$order->id} via {$paymentMethod}. Transaction ID: {$transactionId}");

        // TODO: Envoyer des notifications (client, admin, producteurs)
        // TODO: Créditer les portefeuilles des producteurs concernés
        // TODO: Générer une facture
    }

    /**
     * Handle a failed payment notification/callback.
     */
    public function handlePaymentFailure(Order $order, string $paymentMethod, ?string $transactionId, array $data = [])
    {
        $order->payment_status = 'echec';
        // Le statut de la commande reste 'en_attente' ou passe à 'annulee_paiement_echec'
        if ($transactionId) {
            $order->payment_transaction_id = $transactionId;
        }
        $order->save();

        activity()->error("Payment failed for order #{$order->id} via {$paymentMethod}. Transaction ID: {$transactionId}. Data: " . json_encode($data));
        // TODO: Envoyer une notification au client
    }
}
