<?php
/**
 * Avaritia — Catalog (Browse All Auctions)
 */
$pageTitle = 'Catalog';
require_once 'includes/auth.php';

$db = getDB();

// Filters
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

$where = ["a.status = 'active'"];
$params = [];

if ($category) {
    $where[] = "art.category = ?";
    $params[] = $category;
}

if ($search) {
    $where[] = "(a.title LIKE ? OR art.title LIKE ? OR art.origin LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$orderBy = match($sort) {
    'price_asc' => 'a.current_price ASC',
    'price_desc' => 'a.current_price DESC',
    'ending_soon' => 'a.end_time ASC',
    'most_bids' => 'bid_count DESC',
    default => 'a.created_at DESC'
};

$stmt = $db->prepare("
    SELECT a.*, art.title AS artifact_title, art.image_url, art.category, art.origin,
           (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.auction_id) AS bid_count
    FROM auctions a
    JOIN artifacts art ON a.artifact_id = art.artifact_id
    $whereClause
    ORDER BY $orderBy
");
$stmt->execute($params);
$auctions = $stmt->fetchAll();

$categories = ['sculpture','painting','jewelry','pottery','weapon','manuscript','textile','coin','fossil','other'];

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h1>Auction Catalog</h1>
            <div class="section-divider"></div>
            <p>Browse all active auctions and find your next treasure.</p>
        </div>

        <!-- Filters -->
        <form method="GET" class="flex-between mb-2 flex-wrap gap-1">
            <div class="flex gap-1 flex-wrap">
                <select name="category" class="form-control" style="max-width:180px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="sort" class="form-control" style="max-width:180px;">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low → High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High → Low</option>
                    <option value="most_bids" <?php echo $sort === 'most_bids' ? 'selected' : ''; ?>>Most Bids</option>
                </select>
            </div>
            <div class="flex gap-1">
                <input type="text" name="search" class="form-control" placeholder="Search auctions..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:250px;">
                <button type="submit" class="btn btn-gold btn-sm">Search</button>
            </div>
        </form>

        <!-- Results -->
        <?php if (empty($auctions)): ?>
            <div class="text-center" style="padding: var(--space-3xl);">
                <p style="font-size:3rem; margin-bottom:var(--space-md);">🔍</p>
                <h3>No auctions found</h3>
                <p class="text-muted">Try adjusting your filters or search terms.</p>
            </div>
        <?php else: ?>
            <p class="text-muted mb-1"><?php echo count($auctions); ?> auction<?php echo count($auctions) !== 1 ? 's' : ''; ?> found</p>
            <div class="card-grid">
                <?php foreach ($auctions as $auction): ?>
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
                            <a href="auction.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-gold btn-sm">View</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
