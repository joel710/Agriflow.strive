<?php
class Favorite
{
    private $conn;
    private $table_name = "favorites";

    // Propriétés
    public $id;
    public $customer_id;
    public $product_id;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Ajouter un produit aux favoris
    public function add()
    {
        // Vérifier si le produit n'est pas déjà dans les favoris
        if ($this->isProductFavorite()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                (customer_id, product_id)
                VALUES (:customer_id, :product_id)";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->customer_id = htmlspecialchars(strip_tags($this->customer_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));

        // Bind des valeurs
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":product_id", $this->product_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Supprimer un produit des favoris
    public function remove()
    {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE customer_id = :customer_id AND product_id = :product_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":product_id", $this->product_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Vérifier si un produit est dans les favoris
    public function isProductFavorite()
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE customer_id = :customer_id AND product_id = :product_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":product_id", $this->product_id);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['count'] > 0;
    }

    // Lire tous les favoris d'un client
    public function readCustomerFavorites($page = 1, $per_page = 10)
    {
        $offset = ($page - 1) * $per_page;

        $query = "SELECT f.*, 
                    p.name as product_name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.unit,
                    p.stock_quantity,
                    pr.name as producer_name
                FROM " . $this->table_name . " f
                LEFT JOIN products p ON f.product_id = p.id
                LEFT JOIN producers pr ON p.producer_id = pr.id
                WHERE f.customer_id = :customer_id
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":limit", $per_page, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtenir le nombre total de favoris d'un client
    public function getTotalFavorites()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . "
                WHERE customer_id = :customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Vérifier si plusieurs produits sont dans les favoris
    public function checkMultipleFavorites($product_ids)
    {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $query = "SELECT product_id FROM " . $this->table_name . "
                WHERE customer_id = ? AND product_id IN ($placeholders)";

        $stmt = $this->conn->prepare($query);
        $params = array_merge([$this->customer_id], $product_ids);
        $stmt->execute($params);

        $favorites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $favorites[] = $row['product_id'];
        }

        return $favorites;
    }
}