<?php
require_once 'auth.php';
redirect_if_not_logged_in();

$current_user_id = get_logged_in_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, company, position, link, applied_at, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $current_user_id,
            $_POST['company'] ?? '',
            $_POST['position'] ?? '',
            $_POST['link'] ?? '',
            $_POST['applied_at'] ?: date('Y-m-d'),
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'Wysłano'
        ]);
    } elseif ($action === 'edit') {
        // Ensure user owns this application
        $stmt = $pdo->prepare("UPDATE applications SET company = ?, position = ?, link = ?, applied_at = ?, notes = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $_POST['company'] ?? '',
            $_POST['position'] ?? '',
            $_POST['link'] ?? '',
            $_POST['applied_at'] ?: date('Y-m-d'),
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'Wysłano',
            $_POST['id'],
            $current_user_id
        ]);
    } elseif ($action === 'update_status') {
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['id'], $current_user_id]);
    }
    
    header("Location: index.php");
    exit;
}
