<?php
/**
 * TOOFLE - Configuration de l'application
 * -------------------------------------------------
 * Modifiez uniquement ce fichier pour adapter l'application
 * à votre environnement XAMPP (hôte, utilisateur, mot de passe...).
 */

return [
    // --- Base de données MySQL / MariaDB ---
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'toofle',
        'user'    => 'root',
        'pass'    => '',          // XAMPP : mot de passe vide par défaut
        'charset' => 'utf8mb4',
    ],

    // --- Application ---
    'app' => [
        'name'              => 'TOOFLE',
        // true en développement : la page d'erreur affiche le détail technique.
        'debug'             => true,
        // Devise utilisée par défaut (Frank CFA).
        'currency'          => 'XOF',
        'tagline'           => 'Le réseau social des professionnels du secteur TECH',
        'company'           => 'MANLAB',
        // Timeout de session en secondes (2 heures)
        'session_timeout'   => 7200,
        // Taille maximale d'un fichier uploadé (en octets) - 8 Mo
        'max_upload_size'   => 8 * 1024 * 1024,
        'allowed_images'    => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_files'     => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'zip', 'txt', 'mp4', 'mp3'],
        // Intervalle de rafraîchissement du chat (ms) côté client
        'chat_poll_ms'      => 3000,
        'timezone'          => 'Europe/Paris',
    ],

    // --- Paiement marketplace (optionnel) ---
    'payment' => [
        // 'none' : pas de paiement en ligne, 'stripe' / 'paypal' si vous branchez un vrai PSP
        'driver'         => 'none',
        'stripe_public'  => '',
        'paypal_client'  => '',
    ],
];
