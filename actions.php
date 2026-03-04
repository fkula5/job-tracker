<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (name) VALUES (?)");
            $stmt->execute([$name]);
        }
    } elseif ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, company, position, link, applied_at, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['user_id'] ?: null,
            $_POST['company'] ?? '',
            $_POST['position'] ?? '',
            $_POST['link'] ?? '',
            $_POST['applied_at'] ?: date('Y-m-d'),
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'Wysłano'
        ]);
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE applications SET user_id = ?, company = ?, position = ?, link = ?, applied_at = ?, notes = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $_POST['user_id'] ?: null,
            $_POST['company'] ?? '',
            $_POST['position'] ?? '',
            $_POST['link'] ?? '',
            $_POST['applied_at'] ?: date('Y-m-d'),
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'Wysłano',
            $_POST['id']
        ]);
    } elseif ($action === 'update_status') {
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    
    $redirect = "index.php";
    if (isset($_POST['current_user_id'])) {
        $redirect .= "?user_id=" . $_POST['current_user_id'];
    }
    header("Location: " . $redirect);
    exit;
}
