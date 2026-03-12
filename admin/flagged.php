<?php
/**
 * Avaritia Admin — Flagged Items
 */
$pageTitle = 'Flagged Items';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $flagId = (int)($_POST['flag_id'] ?? 0);

    if ($action === 'review') {
        $stmt = $db->prepare("UPDATE flagged_items SET status = 'reviewed' WHERE flag_id = ?");
        $stmt->execute([$flagId]);
        $message = 'Item marked as reviewed.';
        $messageType = 'success';
    } elseif ($action === 'action') {
        $stmt = $db->prepare("UPDATE flagged_items SET status = 'actioned', notes = ? WHERE flag_id = ?");
        $stmt->execute([$_POST['notes'] ?? 'Action taken by admin.', $flagId]);
        $message = 'Action taken on flagged item.';
        $messageType = 'success';
    } elseif ($action === 'dismiss') {
        $stmt = $db->prepare("UPDATE flagged_items SET status = 'dismissed' WHERE flag_id = ?");
        $stmt->execute([$flagId]);
        $message = 'Flag dismissed.';
        $messageType = 'success';
    }
}

$filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];

if (in_array($filter, ['pending', 'reviewed', 'actioned', 'dismissed'])) {
    $where = 'WHERE fi.status = ?';
    $params = [$filter];
}

$stmt = $db->prepare("
    SELECT fi.*, u.username AS flagged_by_name
    FROM flagged_items fi
    LEFT JOIN users u ON fi.flagged_by = u.user_id
    $where ORDER BY fi.created_at DESC
");
$stmt->execute($params);
$flaggedItems = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Flagged Items</h1>
    <p>Review items automatically or manually flagged for suspicious activity.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="flex gap-1 mb-2">
    <a href="?status=all" class="btn <?php echo $filter === 'all' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">All</a>
    <a href="?status=pending" class="btn <?php echo $filter === 'pending' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Pending</a>
    <a href="?status=reviewed" class="btn <?php echo $filter === 'reviewed' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Reviewed</a>
    <a href="?status=actioned" class="btn <?php echo $filter === 'actioned' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Actioned</a>
    <a href="?status=dismissed" class="btn <?php echo $filter === 'dismissed' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Dismissed</a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Item ID</th>
                <th>Reason</th>
                <th>Severity</th>
                <th>Flagged By</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($flaggedItems)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No flagged items found.</td></tr>
            <?php else: ?>
                <?php foreach ($flaggedItems as $item): ?>
                <tr>
                    <td>#<?php echo $item['flag_id']; ?></td>
                    <td><span class="badge badge-info"><?php echo ucfirst($item['item_type']); ?></span></td>
                    <td>#<?php echo $item['item_id']; ?></td>
                    <td><?php echo htmlspecialchars($item['flag_reason']); ?></td>
                    <td>
                        <?php
                        $sevClass = match($item['severity']) {
                            'low' => 'badge-active',
                            'medium' => 'badge-pending',
                            'high' => 'badge-critical',
                            'critical' => 'badge-critical',
                            default => 'badge-info'
                        };
                        ?>
                        <span class="badge <?php echo $sevClass; ?>"><?php echo ucfirst($item['severity']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($item['flagged_by_name'] ?? 'System'); ?></td>
                    <td>
                        <?php
                        $statusClass = match($item['status']) {
                            'pending' => 'badge-pending',
                            'reviewed' => 'badge-info',
                            'actioned' => 'badge-active',
                            'dismissed' => 'badge-closed',
                            default => 'badge-info'
                        };
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($item['status']); ?></span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                    <td>
                        <?php if ($item['status'] === 'pending' || $item['status'] === 'reviewed'): ?>
                        <div class="flex gap-1">
                            <?php if ($item['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="flag_id" value="<?php echo $item['flag_id']; ?>">
                                <button class="btn btn-ghost btn-sm">👁 Review</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="action">
                                <input type="hidden" name="flag_id" value="<?php echo $item['flag_id']; ?>">
                                <button class="btn btn-outline btn-sm">⚡ Action</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="dismiss">
                                <input type="hidden" name="flag_id" value="<?php echo $item['flag_id']; ?>">
                                <button class="btn btn-danger btn-sm">✕</button>
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
