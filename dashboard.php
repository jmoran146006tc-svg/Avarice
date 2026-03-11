<?php
/**
 * Avaritia — User Dashboard
 */
$pageTitle = 'Dashboard';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();

// User stats
$totalBids = $db->prepare("SELECT COUNT(*) FROM bids WHERE bidder_id = ?");
$totalBids->execute([$userId]);
$totalBids = $totalBids->fetchColumn();

$activeBids = $db->prepare("
    SELECT COUNT(DISTINCT b.auction_id)
    FROM bids b JOIN auctions a ON b.auction_id = a.auction_id
    WHERE b.bidder_id = ? AND a.status = 'active'
");
$activeBids->execute([$userId]);
$activeBids = $activeBids->fetchColumn();

$totalWins = $db->prepare("SELECT COUNT(*) FROM wins WHERE winner_id = ?");
$totalWins->execute([$userId]);
$totalWins = $totalWins->fetchColumn();

$totalSpent = $db->prepare("SELECT COALESCE(SUM(winning_bid), 0) FROM wins WHERE winner_id = ?");
$totalSpent->execute([$userId]);
$totalSpent = $totalSpent->fetchColumn();

// Recent bids
$stmt = $db->prepare("
    SELECT b.*, a.title AS auction_title, a.status AS auction_status, a.end_time
    FROM bids b
    JOIN auctions a ON b.auction_id = a.auction_id
    WHERE b.bidder_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentBids = $stmt->fetchAll();

// Watchlist (auctions where user has bid, still active)
$stmt = $db->prepare("
    SELECT DISTINCT a.*, art.title AS artifact_title,
           (SELECT MAX(b2.bid_amount) FROM bids b2 WHERE b2.auction_id = a.auction_id AND b2.bidder_id = ?) AS my_max_bid
    FROM auctions a
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    JOIN bids b ON a.auction_id = b.auction_id AND b.bidder_id = ?
    WHERE a.status = 'active'
    ORDER BY a.end_time ASC
    LIMIT 5
");
$stmt->execute([$userId, $userId]);
$watchlist = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 style="margin-bottom: var(--space-xs);">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p class="text-muted mb-2">Here's an overview of your auction activity.</p>

        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-icon">🎯</div>
                <div class="stat-card-value"><?php echo $totalBids; ?></div>
                <div class="stat-card-label">Total Bids</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">⚡</div>
                <div class="stat-card-value"><?php echo $activeBids; ?></div>
                <div class="stat-card-label">Active Auctions</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">🏆</div>
                <div class="stat-card-value"><?php echo $totalWins; ?></div>
                <div class="stat-card-label">Wins</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">💎</div>
                <div class="stat-card-value">$<?php echo number_format($totalSpent, 2); ?></div>
                <div class="stat-card-label">Total Spent</div>
            </div>
        </div>

        <!-- Active Watchlist -->
        <?php if ($watchlist): ?>
        <div class="mb-2">
            <div class="flex-between mb-1">
                <h3>Active Auctions You've Bid On</h3>
                <a href="my-bids.php" class="btn btn-ghost btn-sm">View All →</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Auction</th>
                            <th>Your Max Bid</th>
                            <th>Current Price</th>
                            <th>Status</th>
                            <th>Ends</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($watchlist as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                            <td>$<?php echo number_format($item['my_max_bid'], 2); ?></td>
                            <td class="text-gold">$<?php echo number_format($item['current_price'], 2); ?></td>
                            <td>
                                <?php if ($item['my_max_bid'] >= $item['current_price']): ?>
                                    <span class="badge badge-active">Leading</span>
                                <?php else: ?>
                                    <span class="badge badge-critical">Outbid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="auction-timer" data-countdown="<?php echo $item['end_time']; ?>">
                                    <span class="timer-segment">Loading...</span>
                                </div>
                            </td>
                            <td><a href="auction.php?id=<?php echo $item['auction_id']; ?>" class="btn btn-gold btn-sm">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Bids -->
        <div>
            <div class="flex-between mb-1">
                <h3>Recent Bids</h3>
                <a href="my-bids.php" class="btn btn-ghost btn-sm">View All →</a>
            </div>
            <?php if (empty($recentBids)): ?>
                <div class="text-center" style="padding:var(--space-2xl); background:var(--bg-card); border-radius:var(--radius-lg);">
                    <p style="font-size:2rem; margin-bottom:var(--space-md);">🏺</p>
                    <p class="text-muted">You haven't placed any bids yet.</p>
                    <a href="catalog.php" class="btn btn-gold btn-sm mt-1">Browse Catalog</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Auction</th>
                                <th>Your Bid</th>
                                <th>Auction Status</th>
                                <th>Winning?</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBids as $bid): ?>
                            <tr>
                                <td><a href="auction.php?id=<?php echo $bid['auction_id']; ?>"><?php echo htmlspecialchars($bid['auction_title']); ?></a></td>
                                <td>$<?php echo number_format($bid['bid_amount'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $bid['auction_status']; ?>"><?php echo ucfirst($bid['auction_status']); ?></span></td>
                                <td><?php echo $bid['is_winning'] ? '<span class="badge badge-active">Yes</span>' : '<span class="text-muted">No</span>'; ?></td>
                                <td><?php echo date('M j, H:i', strtotime($bid['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
