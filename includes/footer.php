<?php
/**
 * Avaritia — Shared Footer
 */
$isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>

<?php if ($isAdminPage): ?>
    </div><!-- /.admin-content -->
</main><!-- /.admin-main -->
<?php else: ?>
</main><!-- /.main-content -->

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4 class="footer-brand">
                    <span class="brand-icon">♔</span> Avaritia
                </h4>
                <p class="footer-desc">The premier online auction house for rare artifacts, antiques, and collectibles from across the ages.</p>
            </div>
            <div class="footer-col">
                <h5>Explore</h5>
                <ul>
                    <li><a href="catalog.php">Catalog</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="register.php">Create Account</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Account</h5>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="my-bids.php">My Bids</a></li>
                    <li><a href="my-wins.php">My Wins</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Contact</h5>
                <ul>
                    <li>contact@avaritia.com</li>
                    <li>+1 (555) 123-4567</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Avaritia. All rights reserved.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php if ($isAdminPage): ?>
    <script src="/assets/js/main.js"></script>
<?php else: ?>
    <script src="assets/js/main.js"></script>
<?php endif; ?>
</body>
</html>
