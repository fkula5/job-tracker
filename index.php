<?php
require_once 'db.php';

$current_user_id = $_GET['user_id'] ?? null;

// Pobieranie użytkowników
$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();

// Pobieranie listy aplikacji (filtrowanie po użytkowniku)
if ($current_user_id) {
    $stmt = $pdo->prepare("SELECT a.*, u.name as user_name FROM applications a LEFT JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.applied_at DESC, a.id DESC");
    $stmt->execute([$current_user_id]);
    $apps = $stmt->fetchAll();

    $total_apps = count($apps);
    $stats_counts = array_fill_keys(array_keys($statuses), 0);
    foreach ($apps as $app) {
        if (isset($stats_counts[$app['status']])) {
            $stats_counts[$app['status']]++;
        }
    }
} else {
    $apps = $pdo->query("SELECT a.*, u.name as user_name FROM applications a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.applied_at DESC, a.id DESC")->fetchAll();
    
    $total_apps = count($apps);
    $stats_counts = array_fill_keys(array_keys($statuses), 0);
    foreach ($apps as $app) {
        if (isset($stats_counts[$app['status']])) {
            $stats_counts[$app['status']]++;
        }
    }
}

$offer_rate = $total_apps > 0 ? round(($stats_counts['Oferta'] / $total_apps) * 100, 1) : 0;
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
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-briefcase-fill me-2 text-primary"></i>Job Tracker</h1>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill"></i> Nowy użytkownik
            </button>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="bi bi-plus-lg me-1"></i> Dodaj ofertę
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <form method="GET" class="input-group input-group-sm">
                <label class="input-group-text bg-dark text-light border-secondary">Użytkownik</label>
                <select name="user_id" class="form-select bg-dark text-light border-secondary" onchange="this.form.submit()">
                    <option value="">Wszyscy</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $current_user_id == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark text-light stats-card h-100 border-start border-4 border-secondary">
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-1 text-muted small uppercase">Wszystkie</h6>
                    <h2 class="card-title mb-0"><?= $total_apps ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark text-light stats-card h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-1 text-muted small uppercase">Wysłano</h6>
                    <h2 class="card-title mb-0"><?= $stats_counts['Wysłano'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card bg-dark text-light stats-card h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-1 text-muted small uppercase">Rozmowy</h6>
                    <div class="d-flex align-items-baseline gap-2">
                        <h2 class="card-title mb-0"><?= $stats_counts['Rozmowa'] ?></h2>
                        <?php 
                            $conv_rate = $total_apps > 0 ? round((($stats_counts['Rozmowa'] + $stats_counts['Oferta']) / $total_apps) * 100, 1) : 0;
                        ?>
                        <span class="text-primary small fw-bold" title="Współczynnik odpowiedzi">(<?= $conv_rate ?>%)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-lg-3">
            <div class="card bg-dark text-light stats-card h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-1 text-muted small uppercase">Oferty</h6>
                    <div class="d-flex align-items-baseline gap-2">
                        <h2 class="card-title mb-0"><?= $stats_counts['Oferta'] ?></h2>
                        <span class="text-success small fw-bold" title="Success Rate">(<?= $offer_rate ?>%)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <div class="card bg-dark text-light stats-card h-100 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-1 text-muted small uppercase">Odrzucone</h6>
                    <h2 class="card-title mb-0"><?= $stats_counts['Odrzucona'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if ($total_apps > 0): ?>
    <div class="progress mb-4" style="height: 10px; border-radius: 5px;">
        <?php 
        foreach ($statuses as $status => $color_name): 
            $percent = ($stats_counts[$status] / $total_apps) * 100;
            if ($percent > 0):
        ?>
            <div class="progress-bar bg-<?= $color_name ?>" role="progressbar" 
                 style="width: <?= $percent ?>%" 
                 title="<?= $status ?>: <?= $stats_counts[$status] ?> (<?= round($percent, 1) ?>%)"></div>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
    <?php endif; ?>

    <div class="card bg-dark text-light">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Użytkownik</th>
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
                            <td class="small text-muted"><?= htmlspecialchars($app['user_name'] ?? 'Brak') ?></td>
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
                                                <form action="actions.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $s ?>">
                                                    <input type="hidden" name="current_user_id" value="<?= $current_user_id ?>">
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
                                <button type="button" class="btn btn-link text-info btn-sm p-0 me-2" 
                                        onclick="editApp(<?= htmlspecialchars(json_encode($app)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="actions.php" method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                    <input type="hidden" name="current_user_id" value="<?= $current_user_id ?>">
                                    <button type="submit" class="btn btn-link text-danger btn-sm p-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($apps)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Brak wpisów. Kliknij przycisk powyżej, aby dodać pierwszą aplikację.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nowy Użytkownik -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="actions.php" method="POST" class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary text-light">
                <h5 class="modal-title">Dodaj użytkownika</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_user">
                <input type="hidden" name="current_user_id" value="<?= $current_user_id ?>">
                <div class="mb-3">
                    <label class="form-label small">Nazwa / Imię</label>
                    <input type="text" name="name" class="form-control form-control-sm bg-secondary text-white border-0" required>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" class="btn btn-sm btn-primary px-4 w-100">Zapisz</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dodawania / Edycji -->
<div class="modal fade" id="appModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="actions.php" method="POST" class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary text-light">
                <h5 class="modal-title" id="modalTitle">Nowa aplikacja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="modalId">
                <input type="hidden" name="current_user_id" value="<?= $current_user_id ?>">
                
                <div class="mb-3">
                    <label class="form-label small">Użytkownik</label>
                    <select name="user_id" id="modalUserId" class="form-select form-select-sm bg-secondary text-white border-0">
                        <option value="">Wybierz użytkownika...</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $current_user_id == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Firma *</label>
                    <input type="text" name="company" id="modalCompany" class="form-control form-control-sm bg-secondary text-white border-0" required placeholder="np. Google">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Stanowisko *</label>
                    <input type="text" name="position" id="modalPosition" class="form-control form-control-sm bg-secondary text-white border-0" required placeholder="np. Senior PHP Developer">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Link do oferty</label>
                    <input type="url" name="link" id="modalLink" class="form-control form-control-sm bg-secondary text-white border-0" placeholder="https://...">
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label small">Data aplikacji</label>
                        <input type="date" name="applied_at" id="modalAppliedAt" class="form-control form-control-sm bg-secondary text-white border-0" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col">
                        <label class="form-label small">Status</label>
                        <select name="status" id="modalStatus" class="form-select form-select-sm bg-secondary text-white border-0">
                            <?php foreach ($statuses as $s => $c): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small">Notatki / Widełki</label>
                    <textarea name="notes" id="modalNotes" class="form-control form-control-sm bg-secondary text-white border-0" rows="3" placeholder="Dodatkowe informacje..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-sm btn-outline-secondary text-light" data-bs-dismiss="modal">Anuluj</button>
                <button type="submit" class="btn btn-sm btn-primary px-4" id="modalSubmit">Dodaj aplikację</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal do dodawania (repurposing appModal) -->
<div style="display:none;">
    <button id="triggerAddModal" data-bs-toggle="modal" data-bs-target="#appModal"></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const appModal = new bootstrap.Modal(document.getElementById('appModal'));
    
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Nowa aplikacja';
        document.getElementById('modalAction').value = 'add';
        document.getElementById('modalId').value = '';
        document.getElementById('modalCompany').value = '';
        document.getElementById('modalPosition').value = '';
        document.getElementById('modalLink').value = '';
        document.getElementById('modalAppliedAt').value = '<?= date('Y-m-d') ?>';
        document.getElementById('modalStatus').value = 'Wysłano';
        document.getElementById('modalNotes').value = '';
        document.getElementById('modalSubmit').innerText = 'Dodaj aplikację';
        appModal.show();
    }

    function editApp(app) {
        document.getElementById('modalTitle').innerText = 'Edytuj aplikację';
        document.getElementById('modalAction').value = 'edit';
        document.getElementById('modalId').value = app.id;
        document.getElementById('modalUserId').value = app.user_id || '';
        document.getElementById('modalCompany').value = app.company;
        document.getElementById('modalPosition').value = app.position;
        document.getElementById('modalLink').value = app.link || '';
        document.getElementById('modalAppliedAt').value = app.applied_at;
        document.getElementById('modalStatus').value = app.status;
        document.getElementById('modalNotes').value = app.notes || '';
        document.getElementById('modalSubmit').innerText = 'Zapisz zmiany';
        appModal.show();
    }
</script>
</body>
</html>
