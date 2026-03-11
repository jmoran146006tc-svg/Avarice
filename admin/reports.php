<?php
/**
 * Avaritia Admin — Reports
 */
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $reportId = (int)($_POST['report_id'] ?? 0);

    if ($action === 'resolve') {
        $stmt = $db->prepare("UPDATE reports SET status = 'resolved', resolved_by = ?, resolution_notes = ? WHERE report_id = ?");
        $stmt->execute([getCurrentUserId(), $_POST['notes'] ?? '', $reportId]);
        $message = 'Report resolved.';
        $messageType = 'success';
    } elseif ($action === 'dismiss') {
        $stmt = $db->prepare("UPDATE reports SET status = 'dismissed', resolved_by = ? WHERE report_id = ?");
        $stmt->execute([getCurrentUserId(), $reportId]);
        $message = 'Report dismissed.';
        $messageType = 'success';
    } elseif ($action === 'investigate') {
        $stmt = $db->prepare("UPDATE reports SET status = 'investigating' WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $message = 'Report marked as investigating.';
        $messageType = 'info';
    }
}

$filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];

if (in_array($filter, ['open', 'investigating', 'resolved', 'dismissed'])) {
    $where = 'WHERE r.status = ?';
    $params = [$filter];
}

$stmt = $db->prepare("
    SELECT r.*, u.username AS reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.user_id
    $where ORDER BY r.created_at DESC
");
$stmt->execute($params);
$reports = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Reports</h1>
    <p>Review and manage user-submitted reports.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="flex gap-1 mb-2">
    <a href="?status=all" class="btn <?php echo $filter === 'all' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">All</a>
    <a href="?status=open" class="btn <?php echo $filter === 'open' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Open</a>
    <a href="?status=investigating" class="btn <?php echo $filter === 'investigating' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Investigating</a>
    <a href="?status=resolved" class="btn <?php echo $filter === 'resolved' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Resolved</a>
    <a href="?status=dismissed" class="btn <?php echo $filter === 'dismissed' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Dismissed</a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Reporter</th>
                <th>Type</th>
                <th>Reason</th>
                <th>Details</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No reports found.</td></tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td>#<?php echo $report['report_id']; ?></td>
                    <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                    <td><span class="badge badge-info"><?php echo ucfirst($report['reported_type']); ?></span></td>
                    <td><strong><?php echo htmlspecialchars($report['reason']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($report['details'] ?? '', 0, 80)); ?><?php echo strlen($report['details'] ?? '') > 80 ? '…' : ''; ?></td>
                    <td>
                        <?php
                        $badgeClass = match($report['status']) {
                            'open' => 'badge-pending',
                            'investigating' => 'badge-info',
                            'resolved' => 'badge-active',
                            'dismissed' => 'badge-closed',
                            default => 'badge-info'
                        };
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($report['status']); ?></span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                    <td>
                        <?php if ($report['status'] === 'open' || $report['status'] === 'investigating'): ?>
                        <div class="flex gap-1">
                            <?php if ($report['status'] === 'open'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="investigate">
                                <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                <button class="btn btn-ghost btn-sm">🔍 Investigate</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="resolve">
                                <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                <input type="hidden" name="notes" value="Resolved by admin.">
                                <button class="btn btn-ghost btn-sm">✓ Resolve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="dismiss">
                                <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                <button class="btn btn-danger btn-sm">✕ Dismiss</button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
