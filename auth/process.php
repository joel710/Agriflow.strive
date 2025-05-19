<?php
require_once 'auth.php';

// Initialisation de la classe Auth
$auth = new Auth();

// Traitement de la connexion
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($auth->login($email, $password)) {
        // Redirection selon le rôle
        $role = $_SESSION['role'];
        if ($role === 'producteur') {
            header('Location: ../tableau-producteur.html');
        } else {
            header('Location: ../tableau-client.html');
        }
        exit;
    } else {
        $errors = $auth->getErrors();
        // Redirection avec erreurs
        header('Location: ../login.html?error=' . urlencode(implode(', ', $errors)));
        exit;
    }
}

// Traitement de l'inscription
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    // Données communes
    $registrationData = [
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
        'role' => $_POST['role'] ?? ''
    ];

    // Données spécifiques au producteur
    if ($registrationData['role'] === 'producteur') {
        $registrationData += [
            'farm_name' => filter_input(INPUT_POST, 'farm_name', FILTER_SANITIZE_STRING),
            'siret' => filter_input(INPUT_POST, 'siret', FILTER_SANITIZE_STRING),
            'experience_years' => filter_input(INPUT_POST, 'experience_years', FILTER_VALIDATE_INT),
            'farm_type' => filter_input(INPUT_POST, 'farm_type', FILTER_SANITIZE_STRING),
            'surface_hectares' => filter_input(INPUT_POST, 'surface_hectares', FILTER_VALIDATE_FLOAT),
            'farm_address' => filter_input(INPUT_POST, 'farm_address', FILTER_SANITIZE_STRING),
            'certifications' => filter_input(INPUT_POST, 'certifications', FILTER_SANITIZE_STRING),
            'delivery_availability' => filter_input(INPUT_POST, 'delivery_availability', FILTER_SANITIZE_STRING),
            'farm_description' => filter_input(INPUT_POST, 'farm_description', FILTER_SANITIZE_STRING)
        ];
    }
    // Données spécifiques au client
    else {
        $registrationData += [
            'delivery_address' => filter_input(INPUT_POST, 'delivery_address', FILTER_SANITIZE_STRING),
            'food_preferences' => filter_input(INPUT_POST, 'food_preferences', FILTER_SANITIZE_STRING)
        ];
    }

    if ($auth->register($registrationData)) {
        // Connexion automatique après inscription
        if ($auth->login($registrationData['email'], $registrationData['password'])) {
            // Redirection selon le rôle
            if ($registrationData['role'] === 'producteur') {
                header('Location: ../tableau-producteur.html');
            } else {
                header('Location: ../tableau-client.html');
            }
            exit;
        }
    } else {
        $errors = $auth->getErrors();
        // Redirection avec erreurs
        header('Location: ../login.html?signup=true&error=' . urlencode(implode(', ', $errors)));
        exit;
    }
}

// Traitement de la déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header('Location: ../index.html');
    exit;
}

// Protection des pages
if (isset($_GET['action']) && $_GET['action'] === 'check_auth') {
    if (!$auth->checkSession()) {
        http_response_code(401);
        echo json_encode(['authenticated' => false]);
        exit;
    }
    echo json_encode(['authenticated' => true, 'role' => $_SESSION['role']]);
    exit;
}
?>