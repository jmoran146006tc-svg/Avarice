<?php
/**
 * Avaritia Admin — Add Artifact
 */
$pageTitle = 'Add Artifact';
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
        $title       = trim($_POST['title'] ?? '');
        $category    = $_POST['category'] ?? '';
        $origin      = trim($_POST['origin'] ?? '');
        $era         = trim($_POST['era'] ?? '');
        $condition   = $_POST['condition_rating'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $provenance  = trim($_POST['provenance'] ?? '');
        $image_url   = trim($_POST['image_url'] ?? '');
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;

        $categories = ['sculpture','painting','jewelry','pottery','weapon','manuscript','textile','coin','fossil','other'];
        $conditions = ['poor','fair','good','excellent','mint'];

        $errors = [];
        if (!$title)                            $errors[] = 'Title is required.';
        if (!in_array($category, $categories))  $errors[] = 'Please select a valid category.';
        if (!in_array($condition, $conditions)) $errors[] = 'Please select a valid condition.';
        if (!$description)                      $errors[] = 'Description is required.';

        if ($errors) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("
                INSERT INTO artifacts
                    (title, category, origin, era, condition_rating, description, provenance, image_url, added_by, is_verified, is_flagged, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([
                $title,
                $category,
                $origin ?: null,
                $era ?: null,
                $condition,
                $description,
                $provenance ?: null,
                $image_url ?: null,
                getCurrentUserId(),
                $is_verified,
            ]);

            $message = 'Artifact added successfully.';
            $messageType = 'success';
        }
    }
}

$csrfToken = generateCSRFToken();
$categories = ['sculpture','painting','jewelry','pottery','weapon','manuscript','textile','coin','fossil','other'];
$conditions = ['poor','fair','good','excellent','mint'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Add Artifact</h1>
    <p>Add a new artifact directly to the catalogue.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card" style="max-width: 760px;">
    <div class="card-body">
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color:var(--gold);">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       placeholder="e.g. Bronze Ceremonial Helmet"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-md);">
                <div class="form-group">
                    <label class="form-label" for="category">Category <span style="color:var(--gold);">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select category…</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (($_POST['category'] ?? '') === $cat) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="condition_rating">Condition <span style="color:var(--gold);">*</span></label>
                    <select id="condition_rating" name="condition_rating" class="form-control" required>
                        <option value="">Select condition…</option>
                        <?php foreach ($conditions as $cond): ?>
                            <option value="<?php echo $cond; ?>" <?php echo (($_POST['condition_rating'] ?? '') === $cond) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cond); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-md);">
                <div class="form-group">
                    <label class="form-label" for="origin">Origin</label>
                    <input type="text" id="origin" name="origin" class="form-control"
                           placeholder="e.g. Ancient Greece"
                           value="<?php echo htmlspecialchars($_POST['origin'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="era">Era / Period</label>
                    <input type="text" id="era" name="era" class="form-control"
                           placeholder="e.g. 5th century BC"
                           value="<?php echo htmlspecialchars($_POST['era'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span style="color:var(--gold);">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5"
                          placeholder="Describe the artifact in detail…" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="provenance">Provenance</label>
                <textarea id="provenance" name="provenance" class="form-control" rows="3"
                          placeholder="Known history of ownership, excavation details, certificates…"><?php echo htmlspecialchars($_POST['provenance'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url" class="form-control"
                       placeholder="https://example.com/image.jpg"
                       value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>">
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:0.75rem;">
                <input type="checkbox" id="is_verified" name="is_verified" value="1" checked>
                <label for="is_verified" class="form-label" style="margin:0; cursor:pointer;">
                    Mark as Verified immediately
                </label>
            </div>

            <div class="flex gap-1 mt-2">
                <button type="submit" class="btn btn-gold">Add Artifact</button>
                <a href="artifacts.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
