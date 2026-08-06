<?php
/**
 * Shared header partial. Pages set $page_title / $page_nav / $page_desc /
 * $page_css / $body_class before requiring this file. All are optional.
 */

$page_title = $page_title ?? 'EarlySnap';
$page_nav = $page_nav ?? '';
$page_desc = $page_desc ?? 'Snap a photo of any paper — a bill, a prescription, a handwritten note — and EarlySnap turns it into a reminder, a record, or editable text.';
$page_css = $page_css ?? [];
$body_class = $body_class ?? '';

$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — EarlySnap</title>
<meta name="description" content="<?= e($page_desc) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components.css') ?>">
<?php foreach ($page_css as $file): ?>
<link rel="stylesheet" href="<?= asset('css/' . e($file)) ?>">
<?php endforeach; ?>
</head>
<body class="<?= e($body_class) ?>" data-upload-url="<?= url('/upload.php') ?>" data-calendar-url="<?= url('/api/add_to_calendar.php') ?>" data-delete-url="<?= url('/api/delete_document.php') ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="logo" href="<?= url('/') ?>">
      <img class="logo__mark" src="<?= asset('images/logo.webp') ?>" alt="EarlySnap" width="1065" height="262">
    </a>

    <nav class="site-nav" aria-label="Primary">
      <ul class="site-nav__links">
        <li><a href="<?= url('/') ?>" <?= $page_nav === 'home' ? 'class="is-active" aria-current="page"' : '' ?>>Home</a></li>
        <?php if ($user): ?>
        <li><a href="<?= url('/dashboard.php') ?>" <?= $page_nav === 'dashboard' ? 'class="is-active" aria-current="page"' : '' ?>>Dashboard</a></li>
        <?php endif; ?>
      </ul>

      <div class="site-nav__auth">
        <?php if ($user): ?>
          <span class="site-nav__user"><?= e($user['name'] ?? $user['email']) ?></span>
          <form method="post" action="<?= url('/logout.php') ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn--ghost">Log out</button>
          </form>
        <?php else: ?>
          <a class="btn btn--ghost" href="<?= url('/login.php') ?>">Log in</a>
          <a class="btn btn--primary" href="<?= url('/login.php?mode=signup') ?>">Get started</a>
        <?php endif; ?>
      </div>

      <button type="button" class="site-nav__toggle" id="navToggle" aria-expanded="false" aria-controls="navMobilePanel" aria-label="Toggle menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </nav>
  </div>

  <div class="site-nav__mobile-panel" id="navMobilePanel">
    <a href="<?= url('/') ?>">Home</a>
    <?php if ($user): ?>
      <a href="<?= url('/dashboard.php') ?>">Dashboard</a>
      <form method="post" action="<?= url('/logout.php') ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="btn btn--secondary btn--block">Log out</button>
      </form>
    <?php else: ?>
      <a href="<?= url('/login.php') ?>">Log in</a>
      <a class="btn btn--primary btn--block" href="<?= url('/login.php?mode=signup') ?>">Get started</a>
    <?php endif; ?>
  </div>
</header>

<?php $flash = flash_take(); if ($flash): ?>
<div class="container" style="padding-top: var(--space-5);">
  <div class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
</div>
<?php endif; ?>

<main id="main" class="site-main">
