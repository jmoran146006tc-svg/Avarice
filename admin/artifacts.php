<?php
/**
 * Avaritia Admin — Artifacts Management
 */
$pageTitle = 'Manage Artifacts';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'verify') {
            $stmt = $db->prepare("UPDATE artifacts SET is_verified = 1 WHERE artifact_id = ?");
            $stmt->execute([$_POST['artifact_id']]);
            $message = 'Artifact verified successfully.';
            $messageType = 'success';
        } elseif ($action === 'flag') {
            $stmt = $db->prepare("UPDATE artifacts SET is_flagged = 1 WHERE artifact_id = ?");
            $stmt->execute([$_POST['artifact_id']]);
            $message = 'Artifact flagged for review.';
            $messageType = 'warning';
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM artifacts WHERE artifact_id = ?");
            $stmt->execute([$_POST['artifact_id']]);
            $message = 'Artifact deleted.';
            $messageType = 'success';
        }
    }
}

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($filter === 'verified') { $where[] = 'a.is_verified = 1'; }
elseif ($filter === 'unverified') { $where[] = 'a.is_verified = 0'; }
elseif ($filter === 'flagged') { $where[] = 'a.is_flagged = 1'; }

if ($search) {
    $where[] = "(a.title LIKE ? OR a.origin LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT a.*, u.username AS added_by_name FROM artifacts a JOIN users u ON a.added_by = u.user_id $whereClause ORDER BY a.created_at DESC");
$stmt->execute($params);
$artifacts = $stmt->fetchAll();

$csrfToken = generateCSRFToken();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Artifacts</h1>
    <p>Manage, verify, and review all artifacts on the platform.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="flex-between mb-2 flex-wrap gap-1">
    <div class="flex gap-1">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">All</a>
        <a href="?filter=verified" class="btn <?php echo $filter === 'verified' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Verified</a>
        <a href="?filter=unverified" class="btn <?php echo $filter === 'unverified' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Unverified</a>
        <a href="?filter=flagged" class="btn <?php echo $filter === 'flagged' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Flagged</a>
    </div>
    <form method="GET" class="flex gap-1">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <input type="text" name="search" class="form-control" placeholder="Search artifacts..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 250px;">
        <button type="submit" class="btn btn-outline btn-sm">Search</button>
    </form>
</div>

<!-- Artifacts Table -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Origin</th>
                <th>Era</th>
                <th>Condition</th>
                <th>Added By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($artifacts)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No artifacts found.</td></tr>
            <?php else: ?>
                <?php foreach ($artifacts as $artifact): ?>
                <tr>
                    <td>#<?php echo $artifact['artifact_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($artifact['title']); ?></strong></td>
                    <td><span class="badge badge-info"><?php echo ucfirst($artifact['category']); ?></span></td>
                    <td><?php echo htmlspecialchars($artifact['origin'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($artifact['era'] ?? '—'); ?></td>
                    <td><?php echo ucfirst($artifact['condition_rating']); ?></td>
                    <td><?php echo htmlspecialchars($artifact['added_by_name']); ?></td>
                    <td>
                        <?php if ($artifact['is_flagged']): ?>
                            <span class="badge badge-critical">Flagged</span>
                        <?php elseif ($artifact['is_verified']): ?>
                            <span class="badge badge-active">Verified</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Unverified</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <?php if (!$artifact['is_verified']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="verify">
                                <input type="hidden" name="artifact_id" value="<?php echo $artifact['artifact_id']; ?>">
                                <button type="submit" class="btn btn-ghost btn-sm">✓ Verify</button>
                            </form>
                            <?php endif; ?>
                            <?php if (!$artifact['is_flagged']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="flag">
                                <input type="hidden" name="artifact_id" value="<?php echo $artifact['artifact_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🚩 Flag</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($artifact['title']); ?>')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="artifact_id" value="<?php echo $artifact['artifact_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
