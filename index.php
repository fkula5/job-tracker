<?php
// Konfiguracja bazy danych
$db_dir = __DIR__ . '/data';
$db_file = $db_dir . '/jobs.db';

// Tworzenie folderu na bazę danych, jeśli nie istnieje
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Inicjalizacja tabeli
    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company TEXT NOT NULL,
        position TEXT NOT NULL,
        link TEXT,
        applied_at DATE DEFAULT CURRENT_DATE,
        notes TEXT,
        status TEXT DEFAULT 'Wysłano'
    )");
} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}

// Obsługa akcji (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO applications (company, position, link, applied_at, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['company'] ?? '',
            $_POST['position'] ?? '',
            $_POST['link'] ?? '',
            $_POST['applied_at'] ?: date('Y-m-d'),
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'Wysłano'
        ]);
    } elseif ($action === 'update_status') {
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    
    header("Location: index.php");
    exit;
}

// Pobieranie listy aplikacji
$apps = $pdo->query("SELECT * FROM applications ORDER BY applied_at DESC, id DESC")->fetchAll();

$statuses = [
    'Wysłano' => 'secondary',
    'Rozmowa' => 'primary',
    'Odrzucona' => 'danger',
    'Oferta' => 'success'
];
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #121212; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .status-badge { cursor: pointer; transition: opacity 0.2s; }
        .status-badge:hover { opacity: 0.8; }
        .table-hover tbody tr:hover { background-color: rgba(255,255,255,0.05); }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="bi bi-briefcase-fill me-2 text-primary"></i>Job Tracker</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Dodaj ofertę
        </button>
    </div>

    <div class="card bg-dark text-light">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Firma</th>
                            <th>Stanowisko</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Notatki</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apps as $app): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app['company']) ?></strong>
                                <?php if ($app['link']): ?>
                                    <a href="<?= htmlspecialchars($app['link']) ?>" target="_blank" class="ms-1 text-info small"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($app['position']) ?></td>
                            <td class="small"><?= $app['applied_at'] ?></td>
                            <td>
                                <div class="dropdown">
                                    <span class="badge bg-<?= $statuses[$app['status']] ?> status-badge dropdown-toggle" data-bs-toggle="dropdown">
                                        <?= $app['status'] ?>
                                    </span>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($statuses as $s => $color): ?>
                                            <li>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $s ?>">
                                                    <button type="submit" class="dropdown-item small"><?= $s ?></button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($app['notes']) ?>">
                                <?= htmlspecialchars($app['notes']) ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                    <button type="submit" class="btn btn-link text-danger btn-sm p-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($apps)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Brak wpisów. Kliknij przycisk powyżej, aby dodać pierwszą aplikację.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dodawania -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary text-light">
                <h5 class="modal-title">Nowa aplikacja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label small">Firma *</label>
                    <input type="text" name="company" class="form-control form-control-sm bg-secondary text-white border-0" required placeholder="np. Google">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Stanowisko *</label>
                    <input type="text" name="position" class="form-control form-control-sm bg-secondary text-white border-0" required placeholder="np. Senior PHP Developer">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Link do oferty</label>
                    <input type="url" name="link" class="form-control form-control-sm bg-secondary text-white border-0" placeholder="https://...">
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label small">Data aplikacji</label>
                        <input type="date" name="applied_at" class="form-control form-control-sm bg-secondary text-white border-0" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm bg-secondary text-white border-0">
                            <?php foreach ($statuses as $s => $c): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small">Notatki / Widełki</label>
                    <textarea name="notes" class="form-control form-control-sm bg-secondary text-white border-0" rows="3" placeholder="Dodatkowe informacje..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-sm btn-outline-secondary text-light" data-bs-dismiss="modal">Anuluj</button>
                <button type="submit" class="btn btn-sm btn-primary px-4">Dodaj aplikację</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
