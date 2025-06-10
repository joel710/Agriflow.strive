# Documentation Technique Complète – Agriflow

## Table des matières
1. [Présentation générale](#présentation-générale)
2. [Architecture du projet](#architecture-du-projet)
3. [Base de données](#base-de-données)
4. [API Backend](#api-backend)
5. [Authentification & Sécurité](#authentification--sécurité)
6. [Fonctionnalités principales](#fonctionnalités-principales)
7. [Front-end & Tableaux de bord](#front-end--tableaux-de-bord)
8. [Annexes](#annexes)

---

## Présentation générale
Agriflow est une plateforme de gestion de commandes, livraisons et favoris pour un marché entre producteurs et clients. Elle propose une API REST, une gestion d'authentification sécurisée, et des interfaces web pour chaque type d'utilisateur.

## Architecture du projet

```
agriflow/
├── api/                # Backend PHP (API REST)
│   ├── config/         # Configuration (DB, réponses API)
│   ├── controllers/    # Contrôleurs métier (Commandes, Livraisons, Favoris)
│   ├── models/         # Modèles de données (ORM léger)
│   └── routes/         # Définition des routes API
├── auth/               # Authentification (PHP + JS)
├── database/           # Schéma SQL
├── assets/js/          # Scripts JS front (dashboard)
├── js/                 # Autres scripts JS
├── *.html              # Pages web (accueil, login, dashboard, etc.)
```

## Base de données

La base de données `agriflow` est structurée pour supporter :
- Utilisateurs (`users`), Producteurs (`producers`), Clients (`customers`)
- Produits, Commandes, Items de commande, Livraisons, Factures
- Favoris, Notifications, Sessions, Paramètres utilisateur

### Extrait du schéma principal :
- **users** : email, mot de passe, rôle (producteur/client), etc.
- **producers** : profil producteur (ferme, SIRET, etc.)
- **customers** : profil client (adresse, préférences)
- **products** : produits proposés
- **orders** : commandes passées
- **order_items** : items d'une commande
- **deliveries** : suivi des livraisons
- **favorites** : produits favoris d'un client
- **sessions** : gestion des connexions

Voir `database/agriflow.sql` pour le détail complet.

## API Backend

L'API REST (PHP) expose les routes suivantes (voir `api/routes/api.php`) :

### Commandes (`/orders`)
- `GET /orders` : Liste des commandes du client connecté
- `POST /orders` : Création d'une commande
- `GET /orders/{id}` : Détail d'une commande
- `DELETE /orders/{id}` : Annulation d'une commande
- `GET /orders/stats` : Statistiques commandes du client

### Livraisons (`/deliveries`)
- `GET /deliveries` : Liste des livraisons du client
- `GET /deliveries/{id}` : Détail d'une livraison
- `GET /deliveries/{id}/status` : Statut temps réel d'une livraison
- `GET /deliveries/stats` : Statistiques de livraison

### Favoris (`/favorites`)
- `GET /favorites` : Liste des favoris du client
- `POST /favorites` : Ajouter un produit aux favoris
- `DELETE /favorites/{product_id}` : Retirer un produit des favoris
- `GET /favorites/{product_id}` : Vérifier si un produit est favori
- `POST /favorites/check` : Vérifier plusieurs favoris

#### Réponses API
- Succès : `{ status: 'success', data: ..., message: ... }`
- Erreur : `{ status: 'error', message: ..., errors: ... }`

Voir `api/config/ApiResponse.php` pour le formatage des réponses.

## Authentification & Sécurité

- Authentification via sessions PHP et tokens (voir `auth/auth.php`, `auth/process.php`)
- Inscription : Producteur ou Client (création de profil spécifique)
- Connexion : Vérification email/mot de passe, création de session et token
- Déconnexion : Suppression du token et destruction de la session
- Vérification de session : Contrôle du token et de l'expiration
- Sécurité : Hashage des mots de passe (Argon2id), validation des entrées, gestion des erreurs

## Fonctionnalités principales

### Pour les clients :
- Parcourir les produits
- Passer commande
- Suivre ses commandes et livraisons
- Gérer ses favoris
- Recevoir des notifications

### Pour les producteurs :
- Gérer leur profil de ferme
- Ajouter/éditer des produits
- Suivre les commandes reçues
- Gérer les livraisons

### Pour tous :
- Gestion du compte, préférences, notifications

## Front-end & Tableaux de bord

- Pages HTML : `accueil.html`, `login.html`, `tableau-client.html`, `tableau-producteur.html`, etc.
- Scripts JS :
  - `assets/js/dashboard.js` : Gestion du tableau de bord client (onglets, stats, commandes, livraisons, favoris)
  - `auth/auth.js` : Gestion de l'authentification côté client (login, register, logout, protection des pages)
  - `js/profile-menu.js` : Menu utilisateur

## Annexes

- **Configuration** : Voir `api/config/database.php` pour la connexion MySQL
- **Modèles** : Voir `api/models/` pour la logique métier (Order, Delivery, Favorite, OrderItem)
- **Contrôleurs** : Voir `api/controllers/` pour la logique d'API
- **Routes** : Voir `api/routes/api.php`
- **Authentification** : Voir `auth/`
- **Base de données** : Voir `database/agriflow.sql`

---

*Document généré automatiquement. Pour toute question, contactez l'équipe technique Agriflow.* 