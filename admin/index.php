<?php
/**
 * Avaritia Admin — Dashboard
 */
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Dashboard statistics
$stats = [];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$stats['users'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM artifacts");
$stats['artifacts'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM auctions WHERE status = 'active'");
$stats['active_auctions'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COALESCE(SUM(winning_bid), 0) as total FROM wins");
$stats['total_revenue'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'open'");
$stats['open_reports'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM flagged_items WHERE status = 'pending'");
$stats['pending_flags'] = $stmt->fetch()['count'];

// Recent auctions
$stmt = $db->query("SELECT a.*, art.title AS artifact_title FROM auctions a JOIN artifacts art ON a.artifact_id = art.artifact_id ORDER BY a.created_at DESC LIMIT 5");
$recentAuctions = $stmt->fetchAll();

// Recent audit log
$stmt = $db->query("SELECT al.*, u.username FROM audit_log al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 10");
$recentLogs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>. Here's your platform overview.</p>
</div>

<!-- Stats Grid -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-icon">👥</div>
        <div class="stat-card-value"><?php echo number_format($stats['users']); ?></div>
        <div class="stat-card-label">Active Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🏺</div>
        <div class="stat-card-value"><?php echo number_format($stats['artifacts']); ?></div>
        <div class="stat-card-label">Artifacts</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🔨</div>
        <div class="stat-card-value"><?php echo number_format($stats['active_auctions']); ?></div>
        <div class="stat-card-label">Active Auctions</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">💰</div>
        <div class="stat-card-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
        <div class="stat-card-label">Total Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">📋</div>
        <div class="stat-card-value"><?php echo number_format($stats['open_reports']); ?></div>
        <div class="stat-card-label">Open Reports</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🚩</div>
        <div class="stat-card-value"><?php echo number_format($stats['pending_flags']); ?></div>
        <div class="stat-card-label">Pending Flags</div>
    </div>
</div>

<!-- Recent Auctions -->
<div class="mb-2">
    <div class="flex-between mb-1">
        <h3>Recent Auctions</h3>
        <a href="auctions.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Starting Price</th>
                    <th>Current Price</th>
                    <th>Status</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAuctions as $auction): ?>
                <tr>
                    <td>#<?php echo $auction['auction_id']; ?></td>
                    <td><?php echo htmlspecialchars($auction['title']); ?></td>
                    <td>$<?php echo number_format($auction['starting_price'], 2); ?></td>
                    <td>$<?php echo number_format($auction['current_price'], 2); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $auction['status']; ?>">
                            <?php echo ucfirst($auction['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Audit Log -->
<div>
    <div class="flex-between mb-1">
        <h3>Recent Activity</h3>
        <a href="audit.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                    <td><span class="badge badge-info"><?php echo $log['action']; ?></span></td>
                    <td><?php echo $log['table_name']; ?></td>
                    <td>#<?php echo $log['record_id'] ?? '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
