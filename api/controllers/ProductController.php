<?php
require_once '../config/database.php';
require_once '../config/ApiResponse.php';
require_once '../models/Product.php';
require_once '../models/User.php'; // Pour vérifier le rôle et l'ID du producteur
require_once '../models/Producer.php'; // Pour obtenir le producer_id à partir du user_id

class ProductController
{
    private $db;
    private $product;
    private $user; // Pour la gestion des rôles/permissions
    private $producer_model; // Pour lier user_id à producer_id

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new Product($this->db);
        $this->user = new User($this->db); // Assurez-vous que User.php existe et est correct
        $this->producer_model = new Producer($this->db); // Assurez-vous que Producer.php existe
    }

    // Helper pour obtenir le producer_id de l'utilisateur connecté si c'est un producteur
    private function getProducerIdFromSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            return null;
        }
        if ($_SESSION['user_role'] === 'producteur') {
            // On suppose que $_SESSION['user_id'] est l'ID de la table 'users'
            // Il faut trouver le 'producers.id' correspondant
            $producerQuery = "SELECT id FROM producers WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->db->prepare($producerQuery);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $producer_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($producer_row) {
                return $producer_row['id']; // C'est producers.id
            }
        }
        return null;
    }

    // Helper pour vérifier les permissions
    // Pour Update/Delete, on vérifie si l'utilisateur est admin ou le producteur propriétaire du produit.
    private function checkPermission($product_id_to_check = null) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            // ApiResponse::unauthorized("Authentification requise."); // Sera envoyé par le routeur ou ici
            // http_response_code(401); echo json_encode(["status" => "error", "message" => "Authentification requise."]); exit;
            return false; // Le routeur devrait gérer la réponse non autorisée
        }

        $user_role = $_SESSION['user_role'];

        if ($user_role === 'admin') {
            return true; // L'admin a tous les droits
        }

        if ($user_role === 'producteur') {
            $connected_producer_id = $this->getProducerIdFromSession();
            if (!$connected_producer_id) {
                 // http_response_code(403); echo json_encode(["status" => "error", "message" => "Profil producteur non trouvé."]); exit;
                 return false; // Le routeur devrait gérer la réponse non autorisée
            }
            // Pour la création, il suffit d'être producteur.
            // Pour Update/Delete, il faut être le producteur propriétaire.
            if ($product_id_to_check !== null) { // Cas de Update/Delete
                $this->product->id = $product_id_to_check;
                if (!$this->product->readOne()) {
                    // http_response_code(404); echo json_encode(["status" => "error", "message" => "Produit non trouvé."]); exit;
                    return "not_found";
                }
                if ($this->product->producer_id != $connected_producer_id) {
                    // http_response_code(403); echo json_encode(["status" => "error", "message" => "Action non autorisée sur ce produit."]); exit;
                    return "forbidden";
                }
            }
            return true; // Le producteur est autorisé pour son propre produit ou pour créer
        }

        // Les clients ne peuvent pas gérer les produits directement via ces endpoints CRUD.
        // http_response_code(403); echo json_encode(["status" => "error", "message" => "Permissions insuffisantes."]); exit;
        return "forbidden_role";
    }


    // POST /products
    public function createProduct()
    {
        $permission_check = $this->checkPermission();
        if ($permission_check !== true) {
            // Gérer les cas spécifiques de checkPermission ou laisser le routeur le faire
            if ($permission_check === false) ApiResponse::unauthorized("Authentification requise.");
            else if ($permission_check === "forbidden_role") ApiResponse::forbidden("Permissions insuffisantes pour créer un produit.");
            else ApiResponse::forbidden("Action non autorisée."); // Cas générique
            return;
        }

        $connected_producer_id = null;
        if ($_SESSION['user_role'] === 'producteur') {
            $connected_producer_id = $this->getProducerIdFromSession();
            if (!$connected_producer_id) {
                ApiResponse::error("Impossible de lier l'utilisateur à un profil producteur.", 403);
                return;
            }
        }

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->name) || !isset($data->price) || !isset($data->unit) ||
            ($_SESSION['user_role'] === 'admin' && !isset($data->producer_id))
        ) {
            ApiResponse::badRequest("Les champs name, price, unit sont requis. Le producer_id est requis si créé par un admin.");
            return;
        }

        $this->product->producer_id = ($_SESSION['user_role'] === 'admin') ? $data->producer_id : $connected_producer_id;
        $this->product->name = $data->name;
        $this->product->description = $data->description ?? "";
        $this->product->price = $data->price;
        $this->product->unit = $data->unit;
        $this->product->stock_quantity = $data->stock_quantity ?? 0;
        $this->product->image_url = $data->image_url ?? null;
        $this->product->is_bio = $data->is_bio ?? false;
        $this->product->is_available = $data->is_available ?? true;

        try {
            // Vérifier si producer_id existe (surtout si admin le fournit)
            $producerCheckQuery = "SELECT id FROM producers WHERE id = :producer_id";
            $stmtProdCheck = $this->db->prepare($producerCheckQuery);
            $stmtProdCheck->bindParam(':producer_id', $this->product->producer_id, PDO::PARAM_INT);
            $stmtProdCheck->execute();
            if ($stmtProdCheck->rowCount() == 0) {
                ApiResponse::badRequest("Le producer_id fourni n'existe pas.");
                return;
            }

            if ($this->product->create()) {
                $this->product->readOne(); // Pour récupérer created_at, updated_at
                $created_product_data = [
                    'id' => (int)$this->product->id,
                    'producer_id' => (int)$this->product->producer_id,
                    'name' => $this->product->name,
                    'description' => $this->product->description,
                    'price' => (float)$this->product->price,
                    'unit' => $this->product->unit,
                    'stock_quantity' => (int)$this->product->stock_quantity,
                    'image_url' => $this->product->image_url,
                    'is_bio' => (bool)$this->product->is_bio,
                    'is_available' => (bool)$this->product->is_available,
                    'created_at' => $this->product->created_at,
                    'updated_at' => $this->product->updated_at
                ];
                ApiResponse::created($created_product_data, "Produit créé avec succès.");
            } else {
                ApiResponse::error("Impossible de créer le produit.", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error("Erreur lors de la création du produit: " . $e->getMessage(), 500);
        }
    }

    // GET /products
    public function getAllProducts()
    {
        $filters = [];
        if(isset($_GET['producer_id'])) $filters['producer_id'] = (int)$_GET['producer_id'];

        // Gestion de is_available et is_bio comme booléens stricts depuis la query string
        if(isset($_GET['is_available'])) {
            if ($_GET['is_available'] === 'true') $filters['is_available'] = true;
            elseif ($_GET['is_available'] === 'false') $filters['is_available'] = false;
        }
        if(isset($_GET['is_bio'])) {
             if ($_GET['is_bio'] === 'true') $filters['is_bio'] = true;
             elseif ($_GET['is_bio'] === 'false') $filters['is_bio'] = false;
        }


        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        if ($page < 1) $page = 1;
        if ($per_page < 1 || $per_page > 100) $per_page = 10; // Limiter per_page

        try {
            $stmt = $this->product->readAll($filters, $page, $per_page);
            $products_arr = [];
            $products_arr["items"] = [];

            $num = $stmt->rowCount();

            if($num > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $product_item = [
                        "id" => (int)$id,
                        "producer_id" => (int)$producer_id,
                        "name" => $name,
                        "description" => $description,
                        "price" => (float)$price,
                        "unit" => $unit,
                        "stock_quantity" => (int)$stock_quantity,
                        "image_url" => $image_url,
                        "is_bio" => (bool)$is_bio,
                        "is_available" => (bool)$is_available,
                        "created_at" => $created_at,
                        "updated_at" => $updated_at
                    ];
                    array_push($products_arr["items"], $product_item);
                }
            }

            $total_items = $this->product->countAll($filters);
            $products_arr["pagination"] = [
                "currentPage" => $page,
                "itemsPerPage" => $per_page,
                "totalItems" => (int)$total_items,
                "totalPages" => ceil($total_items / $per_page)
            ];

            ApiResponse::success($products_arr);
        } catch (Exception $e) {
            ApiResponse::error("Erreur lors de la récupération des produits: " . $e->getMessage());
        }
    }

    // GET /products/{id}
    public function getProductById($product_id)
    {
        $this->product->id = (int)$product_id;
        try {
            if ($this->product->readOne()) {
                // Par défaut, un client ne devrait pas voir un produit non disponible directement par son ID
                // à moins que ce ne soit via une commande passée, etc.
                // La logique de `is_available` pour la visibilité directe est importante.
                if (!$this->product->is_available) {
                    // Si l'utilisateur n'est pas authentifié, ou est un client, il ne voit pas le produit non disponible.
                    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'client') {
                        ApiResponse::notFound("Produit non trouvé ou non disponible.");
                        return;
                    }
                    // Admin et producteur propriétaire peuvent voir même si non disponible.
                    if ($_SESSION['user_role'] === 'producteur') {
                        $connected_producer_id = $this->getProducerIdFromSession();
                        if ($this->product->producer_id != $connected_producer_id) {
                            ApiResponse::notFound("Produit non trouvé ou non disponible."); // Ou forbidden si on veut être plus précis
                            return;
                        }
                    }
                }

                $product_data = [
                    "id" => (int)$this->product->id,
                    "producer_id" => (int)$this->product->producer_id,
                    "name" => $this->product->name,
                    "description" => $this->product->description,
                    "price" => (float)$this->product->price,
                    "unit" => $this->product->unit,
                    "stock_quantity" => (int)$this->product->stock_quantity,
                    "image_url" => $this->product->image_url,
                    "is_bio" => (bool)$this->product->is_bio,
                    "is_available" => (bool)$this->product->is_available,
                    "created_at" => $this->product->created_at,
                    "updated_at" => $this->product->updated_at
                ];
                ApiResponse::success($product_data);
            } else {
                ApiResponse::notFound("Produit non trouvé.");
            }
        } catch (Exception $e) {
            ApiResponse::error("Erreur lors de la récupération du produit: " . $e->getMessage());
        }
    }

    // PUT /products/{id}
    public function updateProduct($product_id)
    {
        $permission_check = $this->checkPermission((int)$product_id);
        if ($permission_check !== true) {
            if($permission_check === "not_found") ApiResponse::notFound("Produit non trouvé pour la mise à jour.");
            else if($permission_check === "forbidden") ApiResponse::forbidden("Action non autorisée sur ce produit.");
            else if ($permission_check === false) ApiResponse::unauthorized("Authentification requise.");
            else ApiResponse::forbidden("Permissions insuffisantes.");
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data)) {
            ApiResponse::badRequest("Aucune donnée fournie pour la mise à jour.");
            return;
        }

        // this->product est déjà chargé par readOne() dans checkPermission
        $this->product->name = $data->name ?? $this->product->name;
        $this->product->description = $data->description ?? $this->product->description;
        $this->product->price = $data->price ?? $this->product->price;
        $this->product->unit = $data->unit ?? $this->product->unit;
        $this->product->stock_quantity = $data->stock_quantity ?? $this->product->stock_quantity;
        $this->product->image_url = $data->image_url ?? $this->product->image_url;
        $this->product->is_bio = $data->is_bio ?? $this->product->is_bio; // bool
        $this->product->is_available = $data->is_available ?? $this->product->is_available; // bool

        // L'admin peut changer le producer_id, le producteur non.
        if ($_SESSION['user_role'] === 'admin' && isset($data->producer_id)) {
             // Vérifier si le nouveau producer_id existe
            $producerCheckQuery = "SELECT id FROM producers WHERE id = :producer_id";
            $stmtProdCheck = $this->db->prepare($producerCheckQuery);
            $stmtProdCheck->bindParam(':producer_id', $data->producer_id, PDO::PARAM_INT);
            $stmtProdCheck->execute();
            if ($stmtProdCheck->rowCount() == 0) {
                ApiResponse::badRequest("Le nouveau producer_id fourni n'existe pas.");
                return;
            }
            $this->product->producer_id = $data->producer_id;
        } elseif ($_SESSION['user_role'] === 'producteur' && isset($data->producer_id) && $data->producer_id != $this->product->producer_id) {
            ApiResponse::forbidden("Un producteur ne peut pas changer l'appartenance du produit.");
            return;
        }


        try {
            if ($this->product->update()) {
                $this->product->readOne(); // Recharger pour avoir updated_at
                 $updated_product_data = [
                    "id" => (int)$this->product->id,
                    "producer_id" => (int)$this->product->producer_id,
                    "name" => $this->product->name,
                    "description" => $this->product->description,
                    "price" => (float)$this->product->price,
                    "unit" => $this->product->unit,
                    "stock_quantity" => (int)$this->product->stock_quantity,
                    "image_url" => $this->product->image_url,
                    "is_bio" => (bool)$this->product->is_bio,
                    "is_available" => (bool)$this->product->is_available,
                    "created_at" => $this->product->created_at, // Ne change pas
                    "updated_at" => $this->product->updated_at
                ];
                ApiResponse::success($updated_product_data, "Produit mis à jour avec succès.");
            } else {
                // Soit aucune modif, soit erreur non catchée. update() retourne rowCount > 0.
                // On peut renvoyer le produit actuel si aucune modif n'a été faite.
                $current_product_data = [ /* ... idem que $updated_product_data ... */ ];
                 ApiResponse::success($this->product, "Aucune modification détectée ou produit mis à jour.", 200); // Ou 304 si on veut être strict
            }
        } catch (Exception $e) {
            ApiResponse::error("Erreur lors de la mise à jour du produit: " . $e->getMessage(), 500);
        }
    }

    // DELETE /products/{id}
    public function deleteProduct($product_id)
    {
        $permission_check = $this->checkPermission((int)$product_id);
         if ($permission_check !== true) {
            if($permission_check === "not_found") ApiResponse::notFound("Produit non trouvé pour la suppression.");
            else if($permission_check === "forbidden") ApiResponse::forbidden("Action non autorisée sur ce produit.");
            else if ($permission_check === false) ApiResponse::unauthorized("Authentification requise.");
            else ApiResponse::forbidden("Permissions insuffisantes.");
            return;
        }
        // $this->product->id est setté et produit vérifié par checkPermission

        try {
            if ($this->product->delete()) {
                ApiResponse::success(null, "Produit supprimé avec succès.", 204); // 204 No Content
            } else {
                // Devrait être couvert par not_found dans checkPermission si l'ID n'existe pas
                ApiResponse::error("Impossible de supprimer le produit. Il a peut-être déjà été supprimé.", 500);
            }
        } catch (PDOException $e) { // Capturer spécifiquement PDOException pour les contraintes FK
            if ($e->getCode() == '23000') { // Code d'erreur SQL pour violation de contrainte d'intégrité
                 ApiResponse::error("Impossible de supprimer le produit car il est référencé (ex: commandes, favoris).", 409); // 409 Conflict
            } else {
                ApiResponse::error("Erreur base de données lors de la suppression: " . $e->getMessage(), 500);
            }
        }
         catch (Exception $e) {
            ApiResponse::error("Erreur lors de la suppression du produit: " . $e->getMessage(), 500);
        }
    }
}
?>
