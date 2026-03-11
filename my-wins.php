<?php
/**
 * Avaritia — My Wins
 */
$pageTitle = 'My Wins';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();

$stmt = $db->prepare("
    SELECT w.*, a.title AS auction_title, art.title AS artifact_title,
           art.category, art.image_url, a.end_time
    FROM wins w
    JOIN auctions a ON w.auction_id = a.auction_id
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    WHERE w.winner_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$wins = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 style="margin-bottom: var(--space-xs);">My Wins</h1>
        <p class="text-muted mb-2">Auctions you've won. Congratulations!</p>

        <?php if (empty($wins)): ?>
            <div class="text-center" style="padding:var(--space-3xl); background:var(--bg-card); border-radius:var(--radius-lg);">
                <p style="font-size:3rem; margin-bottom:var(--space-md);">🏆</p>
                <h3>No wins yet</h3>
                <p class="text-muted mb-1">Keep bidding — your first win is just around the corner!</p>
                <a href="catalog.php" class="btn btn-gold">Browse Active Auctions</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($wins as $win): ?>
                <div class="card">
                    <div class="card-image">
                        <?php if ($win['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($win['image_url']); ?>" alt="<?php echo htmlspecialchars($win['artifact_title']); ?>" loading="lazy">
                        <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:3rem;color:var(--text-muted);">🏆</div>
                        <?php endif; ?>
                        <span class="card-badge"><?php echo ucfirst($win['category']); ?></span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($win['auction_title']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($win['artifact_title']); ?></p>
                        <div class="card-meta">
                            <div class="card-price">
                                $<?php echo number_format($win['winning_bid'], 2); ?>
                                <small>Winning Bid</small>
                            </div>
                            <div>
                                <span class="badge badge-<?php echo $win['payment_status'] === 'paid' ? 'active' : 'pending'; ?>">
                                    <?php echo ucfirst($win['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-muted" style="font-size:0.8rem; margin-top:var(--space-sm);">
                            Won on <?php echo date('M j, Y', strtotime($win['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
