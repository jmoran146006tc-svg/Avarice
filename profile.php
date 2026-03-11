<?php
/**
 * Avaritia — User Profile
 */
$pageTitle = 'Profile';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $bio       = trim($_POST['bio'] ?? '');
            $email     = trim($_POST['email'] ?? '');

            if (!$firstName || !$lastName || !$email) {
                $message = 'Name and email are required.';
                $messageType = 'error';
            } else {
                // Check email uniqueness
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'Email is already in use.';
                    $messageType = 'error';
                } else {
                    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ? WHERE user_id = ?");
                    $stmt->execute([$firstName, $lastName, $email, $bio, $userId]);
                    $message = 'Profile updated successfully.';
                    $messageType = 'success';
                }
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_new_password'] ?? '';

            $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password_hash'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            } elseif (strlen($newPassword) < 8) {
                $message = 'New password must be at least 8 characters.';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'New passwords do not match.';
                $messageType = 'error';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$hash, $userId]);
                $message = 'Password changed successfully.';
                $messageType = 'success';
            }
        }
    }
}

// Get fresh user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$csrfToken = generateCSRFToken();
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 800px;">
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <p>@<?php echo htmlspecialchars($user['username']); ?> · Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Profile Form -->
        <div class="card" style="margin-bottom: var(--space-xl);">
            <div class="card-body">
                <h3 style="margin-bottom: var(--space-lg);">Profile Information</h3>
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3"
                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-gold">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-body">
                <h3 style="margin-bottom: var(--space-lg);">Change Password</h3>
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-outline">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
