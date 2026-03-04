<?php
require_once 'auth.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Wszystkie pola są wymagane.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            login($user['id'], $user['name']);
            header("Location: index.php");
            exit;
        } else {
            $error = "Nieprawidłowa nazwa użytkownika lub hasło.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Job Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; display: flex; align-items: center; min-height: 100vh; }
        .card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; max-width: 400px; margin: auto; }
    </style>
</head>
<body>
    <div class="card bg-dark text-light p-4">
        <h2 class="text-center mb-4 text-primary">Logowanie</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3 py-2 small"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small">Nazwa użytkownika</label>
                <input type="text" name="username" class="form-control bg-secondary text-white border-0" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small">Hasło</label>
                <input type="password" name="password" class="form-control bg-secondary text-white border-0" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Zaloguj się</button>
            <p class="text-center small mb-0 text-muted">Nie masz jeszcze konta? <a href="register.php" class="text-info">Zarejestruj się</a></p>
        </form>
    </div>
</body>
</html>
