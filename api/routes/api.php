<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure les contrôleurs
require_once '../controllers/OrderController.php';
require_once '../controllers/DeliveryController.php';
require_once '../controllers/FavoriteController.php';

// Obtenir l'URL de la requête
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/agriflow/api';
$endpoint = str_replace($base_path, '', $request_uri);
$method = $_SERVER['REQUEST_METHOD'];

// Initialiser les contrôleurs
$orderController = new OrderController();
$deliveryController = new DeliveryController();
$favoriteController = new FavoriteController();

// Router les requêtes vers les contrôleurs appropriés
try {
    // Routes pour les commandes
    if (preg_match('#^/orders$#', $endpoint)) {
        switch ($method) {
            case 'GET':
                $orderController->getCustomerOrders();
                break;
            case 'POST':
                $orderController->createOrder();
                break;
            default:
                throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/orders/([0-9]+)$#', $endpoint, $matches)) {
        $order_id = $matches[1];
        switch ($method) {
            case 'GET':
                $orderController->getOrderDetails($order_id);
                break;
            case 'DELETE':
                $orderController->cancelOrder($order_id);
                break;
            default:
                throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/orders/stats$#', $endpoint)) {
        if ($method === 'GET') {
            $orderController->getOrderStats();
        } else {
            throw new Exception('Méthode non autorisée');
        }
    }

    // Routes pour les livraisons
    elseif (preg_match('#^/deliveries$#', $endpoint)) {
        if ($method === 'GET') {
            $deliveryController->getCustomerDeliveries();
        } else {
            throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/deliveries/([0-9]+)$#', $endpoint, $matches)) {
        $delivery_id = $matches[1];
        if ($method === 'GET') {
            $deliveryController->getDeliveryDetails($delivery_id);
        } else {
            throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/deliveries/([0-9]+)/status$#', $endpoint, $matches)) {
        $delivery_id = $matches[1];
        if ($method === 'GET') {
            $deliveryController->getDeliveryStatus($delivery_id);
        } else {
            throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/deliveries/stats$#', $endpoint)) {
        if ($method === 'GET') {
            $deliveryController->getDeliveryStats();
        } else {
            throw new Exception('Méthode non autorisée');
        }
    }

    // Routes pour les favoris
    elseif (preg_match('#^/favorites$#', $endpoint)) {
        switch ($method) {
            case 'GET':
                $favoriteController->getFavorites();
                break;
            case 'POST':
                $favoriteController->addToFavorites();
                break;
            default:
                throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/favorites/([0-9]+)$#', $endpoint, $matches)) {
        $product_id = $matches[1];
        switch ($method) {
            case 'GET':
                $favoriteController->isProductFavorite($product_id);
                break;
            case 'DELETE':
                $favoriteController->removeFromFavorites($product_id);
                break;
            default:
                throw new Exception('Méthode non autorisée');
        }
    } elseif (preg_match('#^/favorites/check$#', $endpoint)) {
        if ($method === 'POST') {
            $favoriteController->checkFavorites();
        } else {
            throw new Exception('Méthode non autorisée');
        }
    }

    // Route non trouvée
    else {
        throw new Exception('Endpoint non trouvé');
    }
} catch (Exception $e) {
    if ($e->getMessage() === 'Endpoint non trouvé') {
        echo ApiResponse::notFound('Route non trouvée');
    } elseif ($e->getMessage() === 'Méthode non autorisée') {
        http_response_code(405);
        echo ApiResponse::error('Méthode non autorisée', 405);
    } else {
        echo ApiResponse::error($e->getMessage());
    }
}