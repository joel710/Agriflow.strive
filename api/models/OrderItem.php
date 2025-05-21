<?php
class OrderItem
{
    private $conn;
    private $table_name = "order_items";

    // Propriétés
    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $unit_price;
    public $total_price;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Créer un nouvel item de commande
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                (order_id, product_id, quantity, unit_price, total_price)
                VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->order_id = htmlspecialchars(strip_tags($this->order_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->unit_price = htmlspecialchars(strip_tags($this->unit_price));
        $this->total_price = $this->quantity * $this->unit_price;

        // Bind des valeurs
        $stmt->bindParam(":order_id", $this->order_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":unit_price", $this->unit_price);
        $stmt->bindParam(":total_price", $this->total_price);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Lire les items d'une commande
    public function readOrderItems($order_id)
    {
        $query = "SELECT oi.*, p.name as product_name, p.image_url, p.unit
                FROM " . $this->table_name . " oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();

        return $stmt;
    }

    // Mettre à jour la quantité d'un item
    public function updateQuantity()
    {
        $query = "UPDATE " . $this->table_name . "
                SET quantity = :quantity,
                    total_price = :quantity * unit_price
                WHERE id = :id AND order_id = :order_id";

        $stmt = $this->conn->prepare($query);

        $this->quantity = htmlspecialchars(strip_tags($this->quantity));

        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":order_id", $this->order_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Supprimer un item
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE id = :id AND order_id = :order_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":order_id", $this->order_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Calculer le total d'une commande
    public function calculateOrderTotal($order_id)
    {
        $query = "SELECT SUM(total_price) as total
                FROM " . $this->table_name . "
                WHERE order_id = :order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}