<?php
/**
 * Avaritia — Logout
 */
require_once 'includes/auth.php';

logoutUser();
header('Location: index.php');
exit;
