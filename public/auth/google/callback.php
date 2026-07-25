<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

$state = $_GET['state'] ?? '';
$expected = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);

if (isset($_GET['error']) || !hash_equals($expected, $state) || empty($_GET['code'])) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$accessToken = google_exchange_code($_GET['code']);
if ($accessToken === null) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$profile = google_fetch_profile($accessToken);
if ($profile === null) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$result = auth_login_with_google($profile);
if (!$result['ok']) {
    flash_set('error', $result['error']);
    redirect('/login.php');
}

redirect('/dashboard.php');
