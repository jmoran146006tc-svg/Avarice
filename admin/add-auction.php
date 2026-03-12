<?php
/**
 * Avaritia Admin — Add Auction
 */
$pageTitle = 'Add Auction';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $artifact_id     = (int)($_POST['artifact_id'] ?? 0);
        $title           = trim($_POST['title'] ?? '');
        $description     = trim($_POST['description'] ?? '');
        $starting_price  = (float)($_POST['starting_price'] ?? 0);
        $bid_increment   = (float)($_POST['bid_increment'] ?? 10);
        $end_time        = $_POST['end_time'] ?? '';

        $errors = [];
        if (!$artifact_id)       $errors[] = 'Please select an artifact.';
        if (!$title)             $errors[] = 'Title is required.';
        if ($starting_price <= 0) $errors[] = 'Starting price must be greater than 0.';
        if ($bid_increment <= 0)  $errors[] = 'Bid increment must be greater than 0.';
        if (!$end_time)          $errors[] = 'End date/time is required.';
        if ($end_time && strtotime($end_time) <= time()) $errors[] = 'End time must be in the future.';

        if ($errors) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("
INSERT INTO auctions
    (artifact_id, seller_id, title, description, starting_price, current_price, bid_increment, start_time, end_time, status, created_at)
VALUES
    (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'active', NOW())
            ");
            $stmt->execute([
                $artifact_id,
                getCurrentUserId(),
                $title,
                $description ?: null,
                $starting_price,
                $starting_price,
                $bid_increment,
                $end_time,
            ]);

            $message = 'Auction created successfully.';
            $messageType = 'success';
        }
    }
}

// Only show verified artifacts that don't already have an active auction
$artifacts = $db->query("
    SELECT a.artifact_id, a.title, a.category, a.condition_rating
    FROM artifacts a
    WHERE a.is_verified = 1
      AND a.is_flagged = 0
      AND a.artifact_id NOT IN (
          SELECT artifact_id FROM auctions WHERE status = 'active'
      )
    ORDER BY a.title ASC
")->fetchAll();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header flex-between">
    <div>
        <h1>Add Auction</h1>
        <p>Create a new auction for a verified artifact.</p>
    </div>
    <a href="auctions.php" class="btn btn-ghost">← Back to Auctions</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (empty($artifacts)): ?>
    <div class="alert alert-warning">
        No verified artifacts are available for auction. Either all verified artifacts already have active auctions, or no artifacts have been verified yet.
        <a href="artifacts.php" class="btn btn-ghost btn-sm" style="margin-left:1rem;">Go to Artifacts</a>
    </div>
<?php else: ?>

<div class="card" style="max-width: 760px;">
    <div class="card-body">
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <!-- Artifact -->
            <div class="form-group">
                <label class="form-label" for="artifact_id">Artifact <span style="color:var(--gold);">*</span></label>
                <select id="artifact_id" name="artifact_id" class="form-control" required
                        onchange="prefillTitle(this)">
                    <option value="">Select a verified artifact…</option>
                    <?php foreach ($artifacts as $a): ?>
                        <option value="<?php echo $a['artifact_id']; ?>"
                                data-title="<?php echo htmlspecialchars($a['title']); ?>"
                                <?php echo (($_POST['artifact_id'] ?? '') == $a['artifact_id']) ? 'selected' : ''; ?>>
                            #<?php echo $a['artifact_id']; ?> — <?php echo htmlspecialchars($a['title']); ?>
                            (<?php echo ucfirst($a['category']); ?>, <?php echo ucfirst($a['condition_rating']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label class="form-label" for="title">Auction Title <span style="color:var(--gold);">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       placeholder="e.g. Rare Bronze Helmet — No Reserve"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                <small class="text-muted">Auto-filled from artifact name. Feel free to customise.</small>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Auction Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"
                          placeholder="Optional extra details for bidders…"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <!-- Starting Price & Bid Increment -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-md);">
                <div class="form-group">
                    <label class="form-label" for="starting_price">Starting Price ($) <span style="color:var(--gold);">*</span></label>
                    <input type="number" id="starting_price" name="starting_price" class="form-control"
                           min="1" step="0.01" placeholder="100.00"
                           value="<?php echo htmlspecialchars($_POST['starting_price'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="bid_increment">Bid Increment ($) <span style="color:var(--gold);">*</span></label>
                    <input type="number" id="bid_increment" name="bid_increment" class="form-control"
                           min="1" step="0.01" placeholder="10.00"
                           value="<?php echo htmlspecialchars($_POST['bid_increment'] ?? '10'); ?>" required>
                </div>
            </div>

            <!-- End Time -->
            <div class="form-group">
                <label class="form-label" for="end_time">End Date & Time <span style="color:var(--gold);">*</span></label>
                <input type="datetime-local" id="end_time" name="end_time" class="form-control"
                       min="<?php echo date('Y-m-d\TH:i'); ?>"
                       value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>" required>
            </div>

            <div class="flex gap-1 mt-2">
                <button type="submit" class="btn btn-gold">Create Auction</button>
                <a href="auctions.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function prefillTitle(select) {
    const titleInput = document.getElementById('title');
    const selected = select.options[select.selectedIndex];
    if (selected && selected.dataset.title && !titleInput.value) {
        titleInput.value = selected.dataset.title;
    }
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
