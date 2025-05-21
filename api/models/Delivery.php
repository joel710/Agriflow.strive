<?php
class Delivery
{
    private $conn;
    private $table_name = "deliveries";

    // Propriétés
    public $id;
    public $order_id;
    public $customer_id;
    public $status;
    public $tracking_number;
    public $estimated_delivery_date;
    public $actual_delivery_date;
    public $delivery_person_name;
    public $delivery_person_phone;
    public $delivery_notes;
    public $delivery_address; // Ajout de la propriété delivery_address
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Définir l'ID du client
    public function setCustomerId($customer_id)
    {
        $this->customer_id = htmlspecialchars(strip_tags($customer_id));
    }

    // Créer une nouvelle livraison
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                (order_id, status, tracking_number, estimated_delivery_date, 
                delivery_person_name, delivery_person_phone, delivery_notes)
                VALUES (:order_id, :status, :tracking_number, :estimated_delivery_date,
                :delivery_person_name, :delivery_person_phone, :delivery_notes)";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->order_id = htmlspecialchars(strip_tags($this->order_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->tracking_number = htmlspecialchars(strip_tags($this->tracking_number));
        $this->estimated_delivery_date = htmlspecialchars(strip_tags($this->estimated_delivery_date));
        $this->delivery_person_name = htmlspecialchars(strip_tags($this->delivery_person_name));
        $this->delivery_person_phone = htmlspecialchars(strip_tags($this->delivery_person_phone));
        $this->delivery_notes = htmlspecialchars(strip_tags($this->delivery_notes));

        // Bind des valeurs
        $stmt->bindParam(":order_id", $this->order_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":tracking_number", $this->tracking_number);
        $stmt->bindParam(":estimated_delivery_date", $this->estimated_delivery_date);
        $stmt->bindParam(":delivery_person_name", $this->delivery_person_name);
        $stmt->bindParam(":delivery_person_phone", $this->delivery_person_phone);
        $stmt->bindParam(":delivery_notes", $this->delivery_notes);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Lire les détails d'une livraison
    public function readOne()
    {
        $query = "SELECT d.*, o.customer_id, o.delivery_address
                FROM " . $this->table_name . " d
                LEFT JOIN orders o ON d.order_id = o.id
                WHERE d.id = :id AND o.customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->order_id = $row['order_id'];
            $this->status = $row['status'];
            $this->tracking_number = $row['tracking_number'];
            $this->estimated_delivery_date = $row['estimated_delivery_date'];
            $this->actual_delivery_date = $row['actual_delivery_date'];
            $this->delivery_person_name = $row['delivery_person_name'];
            $this->delivery_person_phone = $row['delivery_person_phone'];
            $this->delivery_notes = $row['delivery_notes'];
            $this->delivery_address = $row['delivery_address'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Mettre à jour le statut d'une livraison
    public function updateStatus()
    {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    actual_delivery_date = CASE 
                        WHEN :status = 'livree' THEN CURRENT_TIMESTAMP
                        ELSE actual_delivery_date
                    END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtenir l'historique des livraisons d'un client
    public function getCustomerDeliveries($customer_id, $page = 1, $per_page = 10)
    {
        $offset = ($page - 1) * $per_page;

        $query = "SELECT d.*, o.delivery_address, o.status as order_status
                FROM " . $this->table_name . " d
                LEFT JOIN orders o ON d.order_id = o.id
                WHERE o.customer_id = :customer_id
                ORDER BY d.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->bindParam(":limit", $per_page, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtenir les statistiques de livraison d'un client
    public function getCustomerStats($customer_id)
    {
        $query = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) as ongoing_deliveries,
                    SUM(CASE WHEN status = 'livree' THEN 1 ELSE 0 END) as completed_deliveries,
                    AVG(CASE 
                        WHEN status = 'livree' 
                        THEN TIMESTAMPDIFF(HOUR, created_at, actual_delivery_date)
                        ELSE NULL 
                    END) as avg_delivery_time
                FROM " . $this->table_name . " d
                LEFT JOIN orders o ON d.order_id = o.id
                WHERE o.customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}