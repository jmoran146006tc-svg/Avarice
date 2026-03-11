<?php
/**
 * Avaritia — Homepage
 */
$pageTitle = 'Home';
require_once 'includes/auth.php';

$db = getDB();

// Featured auctions (active, highest value)
$stmt = $db->query("
    SELECT a.*, art.title AS artifact_title, art.image_url, art.category, art.origin,
           (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.auction_id) AS bid_count
    FROM auctions a
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    WHERE a.status = 'active'
    ORDER BY a.current_price DESC
    LIMIT 6
");
$featuredAuctions = $stmt->fetchAll();

// Platform stats
$totalArtifacts = $db->query("SELECT COUNT(*) FROM artifacts")->fetchColumn();
$activeAuctions = $db->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBids = $db->query("SELECT COUNT(*) FROM bids")->fetchColumn();

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <p class="hero-subtitle">The Premier Artifact Auction House</p>
        <h1>Discover Timeless Treasures</h1>
        <p>Unearth rare artifacts, ancient relics, and extraordinary collectibles from every corner of history. Bid with confidence on authenticated pieces curated by experts.</p>
        <div class="hero-actions">
            <a href="catalog.php" class="btn btn-gold btn-lg">Explore Catalog</a>
            <a href="about.html" class="btn btn-outline btn-lg">Learn More</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-number"><?php echo number_format($totalArtifacts); ?></span>
                <span class="hero-stat-label">Artifacts</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-number"><?php echo number_format($activeAuctions); ?></span>
                <span class="hero-stat-label">Live Auctions</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-number"><?php echo number_format($totalUsers); ?></span>
                <span class="hero-stat-label">Collectors</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-number"><?php echo number_format($totalBids); ?></span>
                <span class="hero-stat-label">Bids Placed</span>
            </div>
        </div>
    </div>
</section>

<!-- Featured Auctions -->
<section class="section">
    <div class="container">
        <div class="section-header reveal">
            <h2>Featured Auctions</h2>
            <div class="section-divider"></div>
            <p>Handpicked lots currently open for bidding. Don't miss your chance to own a piece of history.</p>
        </div>
        <div class="card-grid">
            <?php foreach ($featuredAuctions as $auction): ?>
            <div class="card reveal">
                <div class="card-image">
                    <?php if ($auction['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($auction['image_url']); ?>" alt="<?php echo htmlspecialchars($auction['artifact_title']); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:3rem;color:var(--text-muted);">🏺</div>
                    <?php endif; ?>
                    <span class="card-badge"><?php echo ucfirst($auction['category']); ?></span>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($auction['title']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars($auction['description'] ?? ''); ?></p>
                    <div class="auction-timer" data-countdown="<?php echo $auction['end_time']; ?>">
                        <span class="timer-segment">Loading...</span>
                    </div>
                    <div class="card-meta">
                        <div class="card-price">
                            $<?php echo number_format($auction['current_price'], 2); ?>
                            <small><?php echo $auction['bid_count']; ?> bids</small>
                        </div>
                        <a href="auction.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-gold btn-sm">Bid Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-2">
            <a href="catalog.php" class="btn btn-outline">View Full Catalog →</a>
        </div>
    </div>
</section>

<!-- Why Avaritia -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header reveal">
            <h2>Why Avaritia</h2>
            <div class="section-divider"></div>
            <p>A trusted marketplace for the world's most coveted artifacts.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-icon">🔒</div>
                <h3>Authenticated</h3>
                <p>Every artifact undergoes rigorous expert verification before listing.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">🌍</div>
                <h3>Global Reach</h3>
                <p>Connect with collectors and sellers from every continent.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">⚡</div>
                <h3>Real-time Bidding</h3>
                <p>Live countdown timers and instant bid updates keep you in the action.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">🛡️</div>
                <h3>Secure Transactions</h3>
                <p>Protected payments and dispute resolution for buyer and seller confidence.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section">
    <div class="container">
        <div class="text-center reveal">
            <h2>Ready to Start Collecting?</h2>
            <div class="section-divider"></div>
            <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto var(--space-xl);">
                Join thousands of collectors and start bidding on extraordinary artifacts today.
            </p>
            <a href="register.php" class="btn btn-gold btn-lg">Create Your Account</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
