<?php
/**
 * Configurazione applicazione Preventivi Henoto / Emvisia.
 * Modifica questo file dopo il primo caricamento sul server.
 */

// --- Password di accesso condivisa -----------------------------------
// Password di default: "preventivi2026"
// Per generarne una nuova esegui in locale: php -r "echo password_hash('LA_TUA_PASSWORD', PASSWORD_DEFAULT);"
// e incolla il risultato qui sotto.
define('APP_PASSWORD_HASH', '$2y$12$S5cKiGcaqPAHGaA7lWSlSOWkGGmA0mFfnvPnJHCbNjR00auVCnr1a');

// --- Percorso database SQLite ------------------------------------------
define('DB_PATH', __DIR__ . '/data/app.sqlite');
define('SCHEMA_PATH', __DIR__ . '/install/schema.sql');

// --- Nome applicazione ---------------------------------------------------
define('APP_NAME', 'Preventivi Henoto / Emvisia');

// --- Fuso orario -----------------------------------------------------------
date_default_timezone_set('Europe/Rome');

// --- Sessione ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 14, // 14 giorni
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
