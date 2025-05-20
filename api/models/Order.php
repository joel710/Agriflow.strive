<?php
class Order
{
    private $conn;
    private $table_name = "orders";

    // Propriétés de l'objet
    public $id;
    public $customer_id;
    public $total_amount;
    public $status;
    public $payment_status;
    public $payment_method;
    public $delivery_address;
    public $delivery_notes;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Créer une nouvelle commande
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                (customer_id, total_amount, status, payment_status, payment_method, delivery_address, delivery_notes)
                VALUES (:customer_id, :total_amount, :status, :payment_status, :payment_method, :delivery_address, :delivery_notes)";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->customer_id = htmlspecialchars(strip_tags($this->customer_id));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->delivery_address = htmlspecialchars(strip_tags($this->delivery_address));
        $this->delivery_notes = htmlspecialchars(strip_tags($this->delivery_notes));

        // Bind des valeurs
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":delivery_address", $this->delivery_address);
        $stmt->bindParam(":delivery_notes", $this->delivery_notes);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Lire toutes les commandes d'un client
    public function readCustomerOrders($customer_id, $page = 1, $per_page = 10)
    {
        $offset = ($page - 1) * $per_page;

        $query = "SELECT o.*, 
                    COUNT(oi.id) as items_count,
                    d.status as delivery_status,
                    d.estimated_delivery_date
                FROM " . $this->table_name . " o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN deliveries d ON o.id = d.order_id
                WHERE o.customer_id = :customer_id
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->bindParam(":limit", $per_page, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Lire une commande spécifique avec ses détails
    public function readOne()
    {
        $query = "SELECT o.*, 
                    d.status as delivery_status,
                    d.estimated_delivery_date,
                    d.tracking_number,
                    d.delivery_person_name,
                    d.delivery_person_phone
                FROM " . $this->table_name . " o
                LEFT JOIN deliveries d ON o.id = d.order_id
                WHERE o.id = :id AND o.customer_id = :customer_id
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->status = $row['status'];
            $this->payment_status = $row['payment_status'];
            $this->total_amount = $row['total_amount'];
            $this->delivery_address = $row['delivery_address'];
            $this->delivery_notes = $row['delivery_notes'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];

            // Ajouter les informations de livraison
            $this->delivery_status = $row['delivery_status'];
            $this->estimated_delivery_date = $row['estimated_delivery_date'];
            $this->tracking_number = $row['tracking_number'];
            $this->delivery_person_name = $row['delivery_person_name'];
            $this->delivery_person_phone = $row['delivery_person_phone'];

            return true;
        }
        return false;
    }

    // Mettre à jour le statut d'une commande
    public function updateStatus()
    {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":customer_id", $this->customer_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Annuler une commande
    public function cancel()
    {
        // Vérifier si la commande peut être annulée (statut en_attente ou confirmee)
        if (!in_array($this->status, ['en_attente', 'confirmee'])) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET status = 'annulee',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":customer_id", $this->customer_id);

        return $stmt->execute();
    }

    // Obtenir les statistiques des commandes d'un client
    public function getCustomerStats($customer_id)
    {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'en_livraison' THEN 1 ELSE 0 END) as ongoing_deliveries,
                    SUM(total_amount) as total_spent,
                    MAX(created_at) as last_order_date
                FROM " . $this->table_name . "
                WHERE customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}