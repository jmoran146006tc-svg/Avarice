<?php
/**
 * Avaritia — Shared Header
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Avaritia — Premier online auction house for rare artifacts, antiques, and collectibles.">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Avaritia' : 'Avaritia — Artifact Auctions'; ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($isAdminPage): ?>
                <link rel="stylesheet" href="/assets/css/styles.css">
    <?php else: ?>
                <link rel="stylesheet" href="assets/css/styles.css">
    <?php endif; ?>
</head>
<body class="<?php echo $isAdminPage ? 'admin-layout' : ''; ?>">

<?php if ($isAdminPage): ?>
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                <a href="/admin/index.php">
                     <img 
                src="/assets/images/ORACLE_sigil.png" 
                alt="Avaritia" 
                class="brand-icon"
                style="width:28px; height:28px; object-fit:contain; vertical-align:middle;"
            >
                    <span class="brand-text">Avaritia</span>
                </a>
                <span class="brand-badge">Admin</span>
            </div>
            <nav class="sidebar-nav">

                <a href="/admin/index.php" class="sidebar-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                     Dashboard
                </a>
                <a href="/admin/artifacts.php" class="sidebar-link <?php echo $currentPage === 'artifacts' ? 'active' : ''; ?>">
                     Artifacts
                </a>
                <a href="/admin/auctions.php" class="sidebar-link <?php echo $currentPage === 'auctions' ? 'active' : ''; ?>">
                     Auctions
                </a>
                <a href="/admin/users.php" class="sidebar-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                     Users
                </a>
                <a href="/admin/artifacts.php#flagged" class="sidebar-link <?php echo $currentPage === 'flagged' ? 'active' : ''; ?>">
                     Flagged
                </a>
                <a href="/admin/audit.php" class="sidebar-link <?php echo $currentPage === 'audit' ? 'active' : ''; ?>">
                     Audit Log
                </a>
                <hr class="sidebar-divider">
                <a href="/index.php" class="sidebar-link">
                     View Site
                </a>
                <a href="/logout.php" class="sidebar-link">
                     Sign Out
                </a>
            </nav>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
                <div class="topbar-right">
                    <span class="topbar-user">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </span>
                </div>
            </header>
            <div class="admin-content">

<?php else: ?>
        <header class="navbar" id="navbar">
            <div class="container navbar-inner">
                <a href="index.php" class="navbar-brand">
                     <img 
            src="/assets/images/ORACLE_sigil.png" 
            alt="Avaritia" 
            class="brand-icon"
            style="width:28px; height:28px; object-fit:contain; vertical-align:middle;"
        >
                    <span class="brand-text">Avaritia</span>
                </a>
                <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>
                <nav class="nav-menu" id="navMenu">
            
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a>
                                <a href="about.html" class="nav-link <?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a>
                                <a href="catalog.php" class="nav-link <?php echo $currentPage === 'catalog' ? 'active' : ''; ?>">Catalog</a>
                                <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                                <a href="my-bids.php" class="nav-link <?php echo $currentPage === 'my-bids' ? 'active' : ''; ?>">My Bids</a>
                                <div class="nav-user-menu">
                                    <button class="nav-user-btn" id="userMenuBtn">
                                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> ▾
                                    </button>
                                    <div class="nav-dropdown" id="userDropdown">
                                        <a href="profile.php">Profile</a>
                                        <a href="my-wins.php">My Wins</a>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                    <a href="admin/index.php">Admin Panel</a>
                                        <?php endif; ?>
                                        <hr>
                                        <a href="logout.php">Sign Out</a>
                                    </div>
                                </div>
                    <?php else: ?>
                                <a href="login.php" class="nav-link <?php echo $currentPage === 'login' ? 'active' : ''; ?>">Sign In</a>
                                <a href="register.php" class="btn btn-gold btn-sm">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        <main class="main-content">
<?php endif; ?>
