<?php
require_once '../config/Database.php';
require_once '../config/ApiResponse.php';
require_once '../models/Order.php';
require_once '../models/OrderItem.php';

class OrderController
{
    private $db;
    private $order;
    private $orderItem;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
        $this->orderItem = new OrderItem($this->db);
    }

    // Obtenir la liste des commandes d'un client
    public function getCustomerOrders()
    {
        // Vérifier l'authentification du client
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $customer_id = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        try {
            $stmt = $this->order->readCustomerOrders($customer_id, $page, $per_page);
            $orders = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $order_item = [
                    'id' => $row['id'],
                    'total_amount' => $row['total_amount'],
                    'status' => $row['status'],
                    'payment_status' => $row['payment_status'],
                    'items_count' => $row['items_count'],
                    'delivery_status' => $row['delivery_status'],
                    'estimated_delivery_date' => $row['estimated_delivery_date'],
                    'created_at' => $row['created_at']
                ];
                array_push($orders, $order_item);
            }

            echo ApiResponse::success($orders);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Obtenir les détails d'une commande spécifique
    public function getOrderDetails($order_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->order->id = $order_id;
            $this->order->customer_id = $_SESSION['user_id'];

            if ($this->order->readOne()) {
                // Récupérer les items de la commande
                $stmt = $this->orderItem->readOrderItems($order_id);
                $items = [];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $item = [
                        'id' => $row['id'],
                        'product_name' => $row['product_name'],
                        'quantity' => $row['quantity'],
                        'unit_price' => $row['unit_price'],
                        'total_price' => $row['total_price'],
                        'image_url' => $row['image_url'],
                        'unit' => $row['unit']
                    ];
                    array_push($items, $item);
                }

                $order_data = [
                    'order' => [
                        'id' => $this->order->id,
                        'status' => $this->order->status,
                        'payment_status' => $this->order->payment_status,
                        'total_amount' => $this->order->total_amount,
                        'delivery_address' => $this->order->delivery_address,
                        'delivery_notes' => $this->order->delivery_notes,
                        'created_at' => $this->order->created_at
                    ],
                    'delivery' => [
                        'status' => $this->order->delivery_status,
                        'estimated_delivery_date' => $this->order->estimated_delivery_date,
                        'tracking_number' => $this->order->tracking_number,
                        'delivery_person_name' => $this->order->delivery_person_name,
                        'delivery_person_phone' => $this->order->delivery_person_phone
                    ],
                    'items' => $items
                ];

                echo ApiResponse::success($order_data);
            } else {
                echo ApiResponse::notFound('Commande non trouvée');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Créer une nouvelle commande
    public function createOrder()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->items) || empty($data->items)) {
            echo ApiResponse::validation(['items' => 'La commande doit contenir au moins un produit']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // Créer la commande
            $this->order->customer_id = $_SESSION['user_id'];
            $this->order->status = 'en_attente';
            $this->order->payment_status = 'en_attente';
            $this->order->delivery_address = $data->delivery_address;
            $this->order->delivery_notes = $data->delivery_notes ?? '';
            $this->order->total_amount = 0; // Sera mis à jour après l'ajout des items

            if ($this->order->create()) {
                $order_id = $this->db->lastInsertId();
                $total_amount = 0;

                // Ajouter les items
                foreach ($data->items as $item) {
                    $this->orderItem->order_id = $order_id;
                    $this->orderItem->product_id = $item->product_id;
                    $this->orderItem->quantity = $item->quantity;
                    $this->orderItem->unit_price = $item->unit_price;

                    if (!$this->orderItem->create()) {
                        throw new Exception('Erreur lors de l\'ajout d\'un produit à la commande');
                    }

                    $total_amount += ($item->quantity * $item->unit_price);
                }

                // Mettre à jour le montant total de la commande
                $this->order->id = $order_id;
                $this->order->total_amount = $total_amount;
                $this->order->updateStatus();

                $this->db->commit();
                echo ApiResponse::success(['order_id' => $order_id], 'Commande créée avec succès');
            } else {
                throw new Exception('Erreur lors de la création de la commande');
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Annuler une commande
    public function cancelOrder($order_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->order->id = $order_id;
            $this->order->customer_id = $_SESSION['user_id'];

            if ($this->order->cancel()) {
                echo ApiResponse::success(null, 'Commande annulée avec succès');
            } else {
                echo ApiResponse::error('Impossible d\'annuler la commande');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Obtenir les statistiques des commandes
    public function getOrderStats()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $stats = $this->order->getCustomerStats($_SESSION['user_id']);
            echo ApiResponse::success($stats);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }
}