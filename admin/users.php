<?php
/**
 * Avaritia Admin — Users Management
 */
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active') {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = 'User status toggled.';
        $messageType = 'success';
    } elseif ($action === 'make_admin') {
        $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = 'User promoted to admin.';
        $messageType = 'success';
    } elseif ($action === 'remove_admin') {
        $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = 'Admin role removed.';
        $messageType = 'success';
    }
}

$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$stmt = $db->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM bids WHERE bidder_id = u.user_id) AS total_bids,
           (SELECT COUNT(*) FROM wins WHERE winner_id = u.user_id) AS total_wins
    FROM users u $where ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Users</h1>
    <p>Manage all registered users and their roles.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="flex-between mb-2">
    <div class="stat-card" style="display: inline-flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem;">
        <span style="font-size:1.2rem;">👥</span>
        <div>
            <div class="stat-card-value" style="font-size:1.2rem;"><?php echo count($users); ?></div>
            <div class="stat-card-label" style="font-size:0.7rem;">Total Users</div>
        </div>
    </div>
    <form method="GET" class="flex gap-1">
        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 250px;">
        <button type="submit" class="btn btn-outline btn-sm">Search</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Name</th>
                <th>Role</th>
                <th>Bids</th>
                <th>Wins</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>#<?php echo $user['user_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td>
                    <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-critical' : 'badge-info'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </td>
                <td><?php echo $user['total_bids']; ?></td>
                <td><?php echo $user['total_wins']; ?></td>
                <td>
                    <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-closed'; ?>">
                        <?php echo $user['is_active'] ? 'Active' : 'Disabled'; ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <div class="flex gap-1">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button class="btn btn-ghost btn-sm"><?php echo $user['is_active'] ? 'Disable' : 'Enable'; ?></button>
                        </form>
                        <?php if ($user['role'] !== 'admin'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="make_admin">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button class="btn btn-outline btn-sm">↑ Admin</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="remove_admin">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button class="btn btn-danger btn-sm">↓ User</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
