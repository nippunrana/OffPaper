<?php
/**
 * Shared footer partial. Pages may set $page_js (array of files in
 * /assets/js/) before requiring this file.
 */

$page_js = $page_js ?? [];
?>
</main>

<footer class="site-footer">
  <div class="container site-footer__inner">
    <p>&copy; <?= date('Y') ?> EarlySnap. Get your life off paper.</p>
    <ul class="site-footer__links">
      <li><a href="<?= url('/') ?>">Home</a></li>
      <li><a href="<?= url('/login.php') ?>">Log in</a></li>
      <li><a href="<?= url('/login.php?mode=signup') ?>">Get started</a></li>
      <li><a href="<?= url('/privacy.php') ?>">Privacy Policy</a></li>
      <li><a href="<?= url('/terms.php') ?>">Terms of Service</a></li>
    </ul>
  </div>
</footer>

<script src="<?= asset('js/nav.js') ?>" defer></script>
<?php foreach ($page_js as $file): ?>
<script src="<?= asset('js/' . e($file)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
