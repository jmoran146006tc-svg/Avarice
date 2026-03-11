<?php
/**
 * Avaritia — Artifact Detail Page
 */
require_once 'includes/auth.php';

$db = getDB();
$artifactId = (int)($_GET['id'] ?? 0);

if (!$artifactId) {
    header('Location: catalog.php');
    exit;
}

$stmt = $db->prepare("
    SELECT a.*, u.username AS added_by_name
    FROM artifacts a
    JOIN users u ON a.added_by = u.user_id
    WHERE a.artifact_id = ?
");
$stmt->execute([$artifactId]);
$artifact = $stmt->fetch();

if (!$artifact) {
    header('Location: catalog.php');
    exit;
}

$pageTitle = $artifact['title'];

// Get related auctions
$stmt = $db->prepare("
    SELECT auc.*, (SELECT COUNT(*) FROM bids WHERE auction_id = auc.auction_id) AS bid_count
    FROM auctions auc
    WHERE auc.artifact_id = ? AND auc.status = 'active'
    ORDER BY auc.end_time ASC
");
$stmt->execute([$artifactId]);
$relatedAuctions = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="detail-grid">
            <!-- Image -->
            <div class="detail-image">
                <?php if ($artifact['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($artifact['image_url']); ?>" alt="<?php echo htmlspecialchars($artifact['title']); ?>">
                <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:5rem;color:var(--text-muted);">🏺</div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="detail-info">
                <div class="flex gap-1 mb-1">
                    <span class="badge badge-info"><?php echo ucfirst($artifact['category']); ?></span>
                    <?php if ($artifact['is_verified']): ?>
                        <span class="badge badge-active">✓ Verified</span>
                    <?php endif; ?>
                </div>
                <h1><?php echo htmlspecialchars($artifact['title']); ?></h1>
                
                <div class="detail-meta">
                    <?php if ($artifact['origin']): ?>
                    <div class="detail-meta-item">
                        <strong>Origin</strong>
                        <?php echo htmlspecialchars($artifact['origin']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($artifact['era']): ?>
                    <div class="detail-meta-item">
                        <strong>Era</strong>
                        <?php echo htmlspecialchars($artifact['era']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="detail-meta-item">
                        <strong>Condition</strong>
                        <?php echo ucfirst($artifact['condition_rating']); ?>
                    </div>
                    <div class="detail-meta-item">
                        <strong>Listed By</strong>
                        <?php echo htmlspecialchars($artifact['added_by_name']); ?>
                    </div>
                </div>

                <h4>Description</h4>
                <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--space-xl);">
                    <?php echo nl2br(htmlspecialchars($artifact['description'])); ?>
                </p>

                <?php if ($artifact['provenance']): ?>
                <h4>Provenance</h4>
                <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--space-xl);">
                    <?php echo nl2br(htmlspecialchars($artifact['provenance'])); ?>
                </p>
                <?php endif; ?>

                <!-- Active Auctions for this Artifact -->
                <?php if ($relatedAuctions): ?>
                <h4>Active Auctions</h4>
                <?php foreach ($relatedAuctions as $auc): ?>
                <div class="card" style="margin-bottom: var(--space-md);">
                    <div class="card-body">
                        <div class="flex-between">
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($auc['title']); ?></h5>
                                <div class="card-price">$<?php echo number_format($auc['current_price'], 2); ?></div>
                                <div class="auction-timer" data-countdown="<?php echo $auc['end_time']; ?>">
                                    <span class="timer-segment">Loading...</span>
                                </div>
                            </div>
                            <a href="auction.php?id=<?php echo $auc['auction_id']; ?>" class="btn btn-gold">Bid Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted">No active auctions for this artifact at the moment.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-2">
            <a href="catalog.php" class="btn btn-ghost">← Back to Catalog</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
