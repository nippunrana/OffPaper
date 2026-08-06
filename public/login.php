<?php
require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('/login.php');
    }

    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action === 'signup') {
        $result = auth_signup($email, $password);
        if ($result['ok']) {
            redirect('/dashboard.php');
        }

        flash_set('error', $result['error']);
        $_SESSION['prefill_email'] = $email;
        redirect('/login.php?mode=signup');
    }

    if ($action === 'login') {
        $result = auth_login($email, $password);
        if ($result['ok']) {
            redirect('/dashboard.php');
        }

        flash_set('error', $result['error']);
        $_SESSION['prefill_email'] = $email;
        redirect('/login.php?mode=login');
    }

    redirect('/login.php');
}

if (current_user()) {
    redirect('/dashboard.php');
}

$mode = ($_GET['mode'] ?? 'login') === 'signup' ? 'signup' : 'login';
$prefill_email = $_SESSION['prefill_email'] ?? '';
unset($_SESSION['prefill_email']);

$page_title = $mode === 'signup' ? 'Sign up' : 'Log in';
$page_nav = 'login';
$page_css = ['login.css'];
$page_js = ['login.js'];
$body_class = 'page-login';

require VIEWS . '/header.php';
?>

<section class="auth">
  <div class="container auth__grid">
    <div class="auth__panel">
      <a class="btn btn--google btn--block" href="<?= url('/auth/google/start.php') ?>">
        <svg class="google-icon" viewBox="0 0 18 18" aria-hidden="true">
          <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
          <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
          <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
          <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
        </svg>
        Continue with Google
      </a>
    </div>

    <aside class="trust-panel">
      <p class="trust-panel__quote">&ldquo;Everything in one place, five seconds at a time.&rdquo;</p>
      <ul class="trust-panel__list">
        <li>Free to start — no credit card needed</li>
        <li>Your documents stay private, always</li>
        <li>Fix anything EarlySnap gets wrong in one tap</li>
      </ul>
    </aside>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
