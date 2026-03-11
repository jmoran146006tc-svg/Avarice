<?php
/**
 * Avaritia Admin — Auctions Management
 */
$pageTitle = 'Manage Auctions';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $stmt = $db->prepare("UPDATE auctions SET status = 'cancelled' WHERE auction_id = ?");
        $stmt->execute([$_POST['auction_id']]);
        $message = 'Auction cancelled.';
        $messageType = 'success';
    } elseif ($action === 'close') {
        $stmt = $db->prepare("UPDATE auctions SET status = 'closed' WHERE auction_id = ?");
        $stmt->execute([$_POST['auction_id']]);
        $message = 'Auction closed.';
        $messageType = 'success';
    }
}

$filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];

if (in_array($filter, ['pending', 'active', 'closed', 'cancelled'])) {
    $where = 'WHERE a.status = ?';
    $params = [$filter];
}

$stmt = $db->prepare("
    SELECT a.*, art.title AS artifact_title, u.username AS seller_name,
           (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.auction_id) AS bid_count
    FROM auctions a
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    JOIN users u ON a.seller_id = u.user_id
    $where ORDER BY a.created_at DESC
");
$stmt->execute($params);
$auctions = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Auctions</h1>
    <p>Manage all auctions on the platform.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="flex gap-1 mb-2">
    <a href="?status=all" class="btn <?php echo $filter === 'all' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">All</a>
    <a href="?status=active" class="btn <?php echo $filter === 'active' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Active</a>
    <a href="?status=pending" class="btn <?php echo $filter === 'pending' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Pending</a>
    <a href="?status=closed" class="btn <?php echo $filter === 'closed' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Closed</a>
    <a href="?status=cancelled" class="btn <?php echo $filter === 'cancelled' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Cancelled</a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Seller</th>
                <th>Starting</th>
                <th>Current</th>
                <th>Bids</th>
                <th>Status</th>
                <th>Ends</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($auctions)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No auctions found.</td></tr>
            <?php else: ?>
                <?php foreach ($auctions as $auction): ?>
                <tr>
                    <td>#<?php echo $auction['auction_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($auction['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($auction['seller_name']); ?></td>
                    <td>$<?php echo number_format($auction['starting_price'], 2); ?></td>
                    <td>$<?php echo number_format($auction['current_price'], 2); ?></td>
                    <td><?php echo $auction['bid_count']; ?></td>
                    <td><span class="badge badge-<?php echo $auction['status']; ?>"><?php echo ucfirst($auction['status']); ?></span></td>
                    <td><?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></td>
                    <td>
                        <?php if ($auction['status'] === 'active'): ?>
                        <div class="flex gap-1">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="close">
                                <input type="hidden" name="auction_id" value="<?php echo $auction['auction_id']; ?>">
                                <button class="btn btn-ghost btn-sm">Close</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="auction_id" value="<?php echo $auction['auction_id']; ?>">
                                <button class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                        </div>
                        <?php elseif ($auction['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="auction_id" value="<?php echo $auction['auction_id']; ?>">
                            <button class="btn btn-danger btn-sm">Cancel</button>
                        </form>
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
