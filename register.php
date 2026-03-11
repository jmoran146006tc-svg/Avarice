<?php
/**
 * Avaritia — Register
 */
$pageTitle = 'Create Account';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        if (!$username || !$email || !$firstName || !$lastName || !$password) {
            $message = 'All fields are required.';
            $messageType = 'error';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
            $messageType = 'error';
        } elseif ($password !== $confirm) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetchColumn() > 0) {
                $message = 'Username or email already exists.';
                $messageType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hash, $firstName, $lastName]);

                // Auto-login
                loginUser($username, $password);
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

$csrfToken = generateCSRFToken();
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p class="subtitle">Join the world's premier artifact auction house.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       minlength="8" placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-gold" style="width:100%;">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
