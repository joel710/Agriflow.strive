<?php
require_once '../config/Database.php';
require_once '../config/ApiResponse.php';
require_once '../models/Favorite.php';

class FavoriteController
{
    private $db;
    private $favorite;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->favorite = new Favorite($this->db);
    }

    // Obtenir la liste des favoris d'un client
    public function getFavorites()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;

        try {
            $this->favorite->customer_id = $_SESSION['user_id'];
            $stmt = $this->favorite->readCustomerFavorites($page, $per_page);
            $favorites = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $favorite_item = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'image_url' => $row['image_url'],
                    'unit' => $row['unit'],
                    'stock_quantity' => $row['stock_quantity'],
                    'producer_name' => $row['producer_name'],
                    'created_at' => $row['created_at']
                ];
                array_push($favorites, $favorite_item);
            }

            // Obtenir le nombre total de favoris pour la pagination
            $total_favorites = $this->favorite->getTotalFavorites();

            $response_data = [
                'favorites' => $favorites,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_favorites,
                    'total_pages' => ceil($total_favorites / $per_page)
                ]
            ];

            echo ApiResponse::success($response_data);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Ajouter un produit aux favoris
    public function addToFavorites()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->product_id)) {
            echo ApiResponse::validation(['product_id' => 'L\'ID du produit est requis']);
            return;
        }

        try {
            $this->favorite->customer_id = $_SESSION['user_id'];
            $this->favorite->product_id = $data->product_id;

            if ($this->favorite->add()) {
                echo ApiResponse::success(null, 'Produit ajouté aux favoris');
            } else {
                echo ApiResponse::error('Le produit est déjà dans vos favoris');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Retirer un produit des favoris
    public function removeFromFavorites($product_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->favorite->customer_id = $_SESSION['user_id'];
            $this->favorite->product_id = $product_id;

            if ($this->favorite->remove()) {
                echo ApiResponse::success(null, 'Produit retiré des favoris');
            } else {
                echo ApiResponse::error('Erreur lors du retrait du produit des favoris');
            }
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Vérifier si des produits sont dans les favoris
    public function checkFavorites()
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->product_ids) || !is_array($data->product_ids)) {
            echo ApiResponse::validation(['product_ids' => 'La liste des IDs de produits est requise']);
            return;
        }

        try {
            $this->favorite->customer_id = $_SESSION['user_id'];
            $favorite_products = $this->favorite->checkMultipleFavorites($data->product_ids);

            echo ApiResponse::success([
                'favorite_products' => $favorite_products
            ]);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }

    // Vérifier si un produit spécifique est dans les favoris
    public function isProductFavorite($product_id)
    {
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::unauthorized();
            return;
        }

        try {
            $this->favorite->customer_id = $_SESSION['user_id'];
            $this->favorite->product_id = $product_id;

            $is_favorite = $this->favorite->isProductFavorite();

            echo ApiResponse::success([
                'is_favorite' => $is_favorite
            ]);
        } catch (Exception $e) {
            echo ApiResponse::error($e->getMessage());
        }
    }
}