<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

$state = $_GET['state'] ?? '';
$expected = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);

if (isset($_GET['error']) || empty($expected) || empty($state) || !hash_equals($expected, $state) || empty($_GET['code'])) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$tokenData = google_exchange_code($_GET['code']);
if ($tokenData === null || empty($tokenData['access_token'])) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$profile = google_fetch_profile($tokenData['access_token']);
if ($profile === null) {
    flash_set('error', 'Google sign-in failed. Please try again.');
    redirect('/login.php');
}

$result = auth_login_with_google($profile, $tokenData);
if (!$result['ok']) {
    flash_set('error', $result['error']);
    redirect('/login.php');
}

redirect('/dashboard.php');
