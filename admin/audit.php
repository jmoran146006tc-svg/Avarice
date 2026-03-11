<?php
/**
 * Avaritia Admin — Audit Log
 */
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Filters
$actionFilter = $_GET['action'] ?? '';
$tableFilter = $_GET['table'] ?? '';

$where = [];
$params = [];

if ($actionFilter) {
    $where[] = "al.action = ?";
    $params[] = $actionFilter;
}
if ($tableFilter) {
    $where[] = "al.table_name = ?";
    $params[] = $tableFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT al.*, u.username
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    $whereClause
    ORDER BY al.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions and tables for filter dropdowns
$actions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$tables = $db->query("SELECT DISTINCT table_name FROM audit_log ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Audit Log</h1>
    <p>Complete activity trail of all changes made on the platform.</p>
</div>

<!-- Filters -->
<form method="GET" class="flex gap-1 mb-2 flex-wrap">
    <select name="action" class="form-control" style="max-width: 200px;">
        <option value="">All Actions</option>
        <?php foreach ($actions as $act): ?>
            <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $actionFilter === $act ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($act); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="table" class="form-control" style="max-width: 200px;">
        <option value="">All Tables</option>
        <?php foreach ($tables as $tbl): ?>
            <option value="<?php echo htmlspecialchars($tbl); ?>" <?php echo $tableFilter === $tbl ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tbl); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    <a href="audit.php" class="btn btn-ghost btn-sm">Clear</a>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Table</th>
                <th>Record</th>
                <th>Old Values</th>
                <th>New Values</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No log entries found.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>#<?php echo $log['log_id']; ?></td>
                    <td><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                    <td><span class="badge badge-info"><?php echo $log['action']; ?></span></td>
                    <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                    <td>#<?php echo $log['record_id'] ?? '—'; ?></td>
                    <td>
                        <?php if ($log['old_values']): ?>
                            <code style="font-size:0.7rem; color:var(--text-muted); word-break:break-all;">
                                <?php echo htmlspecialchars(substr($log['old_values'], 0, 60)); ?>
                                <?php echo strlen($log['old_values']) > 60 ? '…' : ''; ?>
                            </code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['new_values']): ?>
                            <code style="font-size:0.7rem; color:var(--text-muted); word-break:break-all;">
                                <?php echo htmlspecialchars(substr($log['new_values'], 0, 60)); ?>
                                <?php echo strlen($log['new_values']) > 60 ? '…' : ''; ?>
                            </code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
