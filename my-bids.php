<?php
/**
 * Avaritia — My Bids
 */
$pageTitle = 'My Bids';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();

$filter = $_GET['filter'] ?? 'all';

$where = "WHERE b.bidder_id = ?";
$params = [$userId];

if ($filter === 'winning') {
    $where .= " AND b.is_winning = 1";
} elseif ($filter === 'active') {
    $where .= " AND a.status = 'active'";
} elseif ($filter === 'closed') {
    $where .= " AND a.status = 'closed'";
}

$stmt = $db->prepare("
    SELECT b.*, a.title AS auction_title, a.status AS auction_status,
           a.current_price, a.end_time, art.category
    FROM bids b
    JOIN auctions a ON b.auction_id = a.auction_id
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bids = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 style="margin-bottom: var(--space-xs);">My Bids</h1>
        <p class="text-muted mb-2">Track all your bids across auctions.</p>

        <div class="flex gap-1 mb-2">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">All</a>
            <a href="?filter=winning" class="btn <?php echo $filter === 'winning' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Winning</a>
            <a href="?filter=active" class="btn <?php echo $filter === 'active' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Active Auctions</a>
            <a href="?filter=closed" class="btn <?php echo $filter === 'closed' ? 'btn-gold' : 'btn-ghost'; ?> btn-sm">Closed</a>
        </div>

        <?php if (empty($bids)): ?>
            <div class="text-center" style="padding:var(--space-3xl); background:var(--bg-card); border-radius:var(--radius-lg);">
                <p style="font-size:3rem; margin-bottom:var(--space-md);">🎯</p>
                <h3>No bids found</h3>
                <p class="text-muted mb-1">Start bidding on artifacts to see them here.</p>
                <a href="catalog.php" class="btn btn-gold">Browse Catalog</a>
            </div>
        <?php else: ?>
            <p class="text-muted mb-1"><?php echo count($bids); ?> bid<?php echo count($bids) !== 1 ? 's' : ''; ?></p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Auction</th>
                            <th>Category</th>
                            <th>Your Bid</th>
                            <th>Current Price</th>
                            <th>Status</th>
                            <th>Leading?</th>
                            <th>Ends</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bids as $bid): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($bid['auction_title']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($bid['category']); ?></span></td>
                            <td>$<?php echo number_format($bid['bid_amount'], 2); ?></td>
                            <td class="text-gold">$<?php echo number_format($bid['current_price'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $bid['auction_status']; ?>"><?php echo ucfirst($bid['auction_status']); ?></span></td>
                            <td>
                                <?php if ($bid['is_winning']): ?>
                                    <span class="badge badge-active">✓ Yes</span>
                                <?php else: ?>
                                    <span class="text-muted">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bid['auction_status'] === 'active'): ?>
                                    <div class="auction-timer" data-countdown="<?php echo $bid['end_time']; ?>">
                                        <span class="timer-segment">...</span>
                                    </div>
                                <?php else: ?>
                                    <?php echo date('M j, Y', strtotime($bid['end_time'])); ?>
                                <?php endif; ?>
                            </td>
                            <td><a href="auction.php?id=<?php echo $bid['auction_id']; ?>" class="btn btn-ghost btn-sm">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
