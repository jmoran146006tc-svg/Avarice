<?php
/**
 * Avaritia — Auction Detail & Bidding Page
 */
require_once 'includes/auth.php';

$db = getDB();
$auctionId = (int)($_GET['id'] ?? 0);

if (!$auctionId) {
    header('Location: catalog.php');
    exit;
}

// Handle bid submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $bidAmount = (float)$_POST['bid_amount'];
        $userId = getCurrentUserId();

        // Get auction info
        $stmt = $db->prepare("SELECT current_price, bid_increment, status, seller_id FROM auctions WHERE auction_id = ?");
        $stmt->execute([$auctionId]);
        $auc = $stmt->fetch();

        if (!$auc || $auc['status'] !== 'active') {
            $message = 'This auction is not currently active.';
            $messageType = 'error';
        } elseif ($userId == $auc['seller_id']) {
            $message = 'You cannot bid on your own auction.';
            $messageType = 'error';
        } elseif ($bidAmount < ($auc['current_price'] + $auc['bid_increment'])) {
            $message = 'Your bid must be at least $' . number_format($auc['current_price'] + $auc['bid_increment'], 2);
            $messageType = 'error';
        } else {
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE bids SET is_winning = 0 WHERE auction_id = ? AND is_winning = 1")->execute([$auctionId]);
                $db->prepare("INSERT INTO bids (auction_id, bidder_id, bid_amount, is_winning) VALUES (?, ?, ?, 1)")->execute([$auctionId, $userId, $bidAmount]);
                $db->prepare("UPDATE auctions SET current_price = ? WHERE auction_id = ?")->execute([$bidAmount, $auctionId]);
                $db->commit();
                $message = 'Your bid of $' . number_format($bidAmount, 2) . ' has been placed!';
                $messageType = 'success';
            } catch (Exception $e) {
                $db->rollBack();
                $message = 'Failed to place bid. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Fetch auction details
$stmt = $db->prepare("
    SELECT a.*, art.title AS artifact_title, art.description AS artifact_desc,
           art.image_url, art.category, art.origin, art.era, art.condition_rating, art.provenance,
           u.username AS seller_name
    FROM auctions a
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    JOIN users u ON a.seller_id = u.user_id
    WHERE a.auction_id = ?
");
$stmt->execute([$auctionId]);
$auction = $stmt->fetch();

if (!$auction) {
    header('Location: catalog.php');
    exit;
}

$pageTitle = $auction['title'];

// Bid history
$stmt = $db->prepare("
    SELECT b.*, u.username
    FROM bids b JOIN users u ON b.bidder_id = u.user_id
    WHERE b.auction_id = ?
    ORDER BY b.bid_amount DESC
    LIMIT 10
");
$stmt->execute([$auctionId]);
$bidHistory = $stmt->fetchAll();

$bidCount = count($bidHistory);
$minBid = $auction['current_price'] + $auction['bid_increment'];
$csrfToken = generateCSRFToken();

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="detail-grid">
            <!-- Artifact Image -->
            <div>
                <div class="detail-image">
                    <?php if ($auction['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($auction['image_url']); ?>" alt="<?php echo htmlspecialchars($auction['artifact_title']); ?>">
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:5rem;color:var(--text-muted);">🏺</div>
                    <?php endif; ?>
                </div>

                <!-- Artifact Details -->
                <div class="mt-2">
                    <h3>About This Artifact</h3>
                    <p style="color:var(--text-secondary);line-height:1.8;margin:var(--space-md) 0;">
                        <?php echo nl2br(htmlspecialchars($auction['artifact_desc'])); ?>
                    </p>
                    <div class="detail-meta">
                        <?php if ($auction['origin']): ?>
                        <div class="detail-meta-item"><strong>Origin</strong><?php echo htmlspecialchars($auction['origin']); ?></div>
                        <?php endif; ?>
                        <?php if ($auction['era']): ?>
                        <div class="detail-meta-item"><strong>Era</strong><?php echo htmlspecialchars($auction['era']); ?></div>
                        <?php endif; ?>
                        <div class="detail-meta-item"><strong>Condition</strong><?php echo ucfirst($auction['condition_rating']); ?></div>
                        <div class="detail-meta-item"><strong>Category</strong><?php echo ucfirst($auction['category']); ?></div>
                    </div>
                    <?php if ($auction['provenance']): ?>
                    <h4 class="mt-1">Provenance</h4>
                    <p style="color:var(--text-secondary);line-height:1.8;"><?php echo nl2br(htmlspecialchars($auction['provenance'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bid Panel -->
            <div>
                <div class="bid-panel">
                    <div class="flex-between mb-1">
                        <span class="badge badge-<?php echo $auction['status']; ?>"><?php echo ucfirst($auction['status']); ?></span>
                        <span class="text-muted" style="font-size:0.85rem;">Seller: <?php echo htmlspecialchars($auction['seller_name']); ?></span>
                    </div>
                    <h2 style="font-size:1.3rem; margin-bottom:var(--space-md);"><?php echo htmlspecialchars($auction['title']); ?></h2>

                    <div class="bid-current">
                        <span class="bid-current-label">Current Bid</span>
                        <div class="bid-current-amount">$<?php echo number_format($auction['current_price'], 2); ?></div>
                        <span class="text-muted" style="font-size:0.85rem;"><?php echo $bidCount; ?> bid<?php echo $bidCount !== 1 ? 's' : ''; ?></span>
                    </div>

                    <div class="flex-between mb-1" style="font-size:0.85rem;">
                        <span class="text-muted">Time Remaining</span>
                        <div class="auction-timer" data-countdown="<?php echo $auction['end_time']; ?>">
                            <span class="timer-segment">Loading...</span>
                        </div>
                    </div>

                    <div class="flex-between mb-2" style="font-size:0.85rem;">
                        <span class="text-muted">Minimum Bid</span>
                        <span class="text-gold">$<?php echo number_format($minBid, 2); ?></span>
                    </div>

                    <?php if ($auction['status'] === 'active'): ?>
                        <?php if (isLoggedIn()): ?>
                        <form method="POST" data-validate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <div class="bid-input-row">
                                <input type="number" name="bid_amount" class="form-control"
                                       min="<?php echo $minBid; ?>" step="<?php echo $auction['bid_increment']; ?>"
                                       value="<?php echo $minBid; ?>" required>
                                <button type="submit" class="btn btn-gold">Place Bid</button>
                            </div>
                            <p class="text-muted" style="font-size:0.75rem;">Bid increment: $<?php echo number_format($auction['bid_increment'], 2); ?></p>
                        </form>
                        <?php else: ?>
                        <div class="text-center" style="padding:var(--space-lg);background:var(--bg-tertiary);border-radius:var(--radius-md);">
                            <p style="margin-bottom:var(--space-md);color:var(--text-secondary);">Sign in to place a bid</p>
                            <a href="login.php" class="btn btn-gold">Sign In</a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">This auction is <?php echo $auction['status']; ?>.</div>
                    <?php endif; ?>
                </div>

                <!-- Bid History -->
                <?php if ($bidHistory): ?>
                <div class="mt-2">
                    <h4>Bid History</h4>
                    <div class="table-container" style="margin-top:var(--space-md);">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bidder</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bidHistory as $bid): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($bid['username']); ?>
                                        <?php if ($bid['is_winning']): ?>
                                            <span class="badge badge-active" style="margin-left:0.3rem;">Leading</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-gold">$<?php echo number_format($bid['bid_amount'], 2); ?></td>
                                    <td><?php echo date('M j, H:i', strtotime($bid['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-2">
            <a href="catalog.php" class="btn btn-ghost">← Back to Catalog</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
