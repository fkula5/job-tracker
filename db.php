<?php
$db_dir = __DIR__ . '/data';
$db_file = $db_dir . '/jobs.db';

if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    )");

    // Migration: Add password column if it doesn't exist
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('password', $cols)) {
        // For existing users, we'll set a placeholder or require password reset.
        // For simplicity in this demo, let's just add it with an empty string or a default hash.
        $pdo->exec("ALTER TABLE users ADD COLUMN password TEXT NOT NULL DEFAULT ''");
    }

    // Applications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        company TEXT NOT NULL,
        position TEXT NOT NULL,
        link TEXT,
        applied_at DATE DEFAULT CURRENT_DATE,
        notes TEXT,
        status TEXT DEFAULT 'Wysłano',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Migration: Add user_id if it doesn't exist
    $cols = $pdo->query("PRAGMA table_info(applications)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('user_id', $cols)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN user_id INTEGER REFERENCES users(id) ON DELETE CASCADE");
    }

} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}

$statuses = [
    'Wysłano' => 'secondary',
    'Rozmowa' => 'primary',
    'Odrzucona' => 'danger',
    'Oferta' => 'success'
];
