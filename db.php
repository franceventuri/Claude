<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);
    $dataDir = dirname(DB_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        initSchema($pdo);
    } else {
        migrateSchema($pdo);
    }

    return $pdo;
}

function initSchema(PDO $pdo): void
{
    $sql = file_get_contents(SCHEMA_PATH);
    if ($sql === false) {
        throw new RuntimeException('Impossibile leggere install/schema.sql');
    }
    $pdo->exec($sql);
}

/**
 * Aggiunge eventuali colonne/tabelle mancanti su database gia' esistenti,
 * cosi' un aggiornamento dei file dell'app non rompe l'archivio gia' in uso.
 */
function migrateSchema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_version (version INTEGER NOT NULL)');
    $row = $pdo->query('SELECT version FROM schema_version LIMIT 1')->fetch();
    if (!$row) {
        $pdo->exec('INSERT INTO schema_version (version) VALUES (1)');
    }
}

function jsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void
{
    jsonResponse(['error' => $message], $status);
}
