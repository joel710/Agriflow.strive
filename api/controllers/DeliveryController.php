<?php
require_once '../config/database.php';
require_once '../config/ApiResponse.php';
require_once '../models/Delivery.php';

class DeliveryController
{
    private $db;
    private $delivery;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->delivery = new Delivery($this->db);
    }

    // Obtenir les détails d'une livraison
    public function getDeliveryDetails($delivery_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->delivery->id = $delivery_id;
            $this->delivery->customer_id = $_SESSION['user_id'];

            if ($this->delivery->readOne()) {
                $delivery_data = [
                    'id' => $this->delivery->id,
                    'order_id' => $this->delivery->order_id,
                    'status' => $this->delivery->status,
                    'tracking_number' => $this->delivery->tracking_number,
                    'estimated_delivery_date' => $this->delivery->estimated_delivery_date,
                    'actual_delivery_date' => $this->delivery->actual_delivery_date,
                    'delivery_person_name' => $this->delivery->delivery_person_name,
                    'delivery_person_phone' => $this->delivery->delivery_person_phone,
                    'delivery_notes' => $this->delivery->delivery_notes,
                    'delivery_address' => isset($this->delivery->delivery_address) ? $this->delivery->delivery_address : null,
                    'created_at' => $this->delivery->created_at,
                    'updated_at' => $this->delivery->updated_at
                ];

                echo ApiResponse::success($delivery_data);
            } else {
                echo ApiResponse::notFound('Livraison non trouvée');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Obtenir l'historique des livraisons d'un client
    public function getCustomerDeliveries()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        try {
            $stmt = $this->delivery->getCustomerDeliveries($_SESSION['user_id'], $page, $per_page);
            $deliveries = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $delivery_item = [
                    'id' => $row['id'],
                    'order_id' => $row['order_id'],
                    'status' => $row['status'],
                    'tracking_number' => $row['tracking_number'],
                    'estimated_delivery_date' => $row['estimated_delivery_date'],
                    'actual_delivery_date' => $row['actual_delivery_date'],
                    'delivery_person_name' => $row['delivery_person_name'],
                    'delivery_person_phone' => $row['delivery_person_phone'],
                    'delivery_address' => $row['delivery_address'],
                    'order_status' => $row['order_status'],
                    'created_at' => $row['created_at']
                ];
                array_push($deliveries, $delivery_item);
            }

            echo ApiResponse::success($deliveries);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Obtenir les statistiques de livraison d'un client
    public function getDeliveryStats()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $stats = $this->delivery->getCustomerStats($_SESSION['user_id']);

            // Formater le temps moyen de livraison
            if ($stats['avg_delivery_time'] !== null) {
                $stats['avg_delivery_time'] = round($stats['avg_delivery_time'], 1);
                $stats['avg_delivery_time_formatted'] = $this->formatDeliveryTime($stats['avg_delivery_time']);
            }

            echo ApiResponse::success($stats);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Formater le temps de livraison en heures et minutes
    private function formatDeliveryTime($hours)
    {
        $fullHours = floor($hours);
        $minutes = round(($hours - $fullHours) * 60);

        if ($fullHours > 0 && $minutes > 0) {
            return "$fullHours h $minutes min";
        } elseif ($fullHours > 0) {
            return "$fullHours h";
        } else {
            return "$minutes min";
        }
    }

    // Obtenir le statut en temps réel d'une livraison
    public function getDeliveryStatus($delivery_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->delivery->id = $delivery_id;
            $this->delivery->customer_id = $_SESSION['user_id'];

            if ($this->delivery->readOne()) {
                $status_data = [
                    'status' => $this->delivery->status,
                    'estimated_delivery_date' => $this->delivery->estimated_delivery_date,
                    'actual_delivery_date' => $this->delivery->actual_delivery_date,
                    'delivery_person_name' => $this->delivery->delivery_person_name,
                    'delivery_person_phone' => $this->delivery->delivery_person_phone,
                    'tracking_number' => $this->delivery->tracking_number
                ];

                echo ApiResponse::success($status_data);
            } else {
                echo ApiResponse::notFound('Livraison non trouvée');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }
}