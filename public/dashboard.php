<?php
require_once __DIR__ . '/../src/bootstrap.php';

require_login();
$user = current_user();
$display_name = $user['name'] ?: ucfirst(explode('@', $user['email'])[0]);

$page_title = 'Dashboard';
$page_nav = 'dashboard';
$page_css = ['dashboard.css'];
$body_class = 'page-dashboard';

require VIEWS . '/header.php';
?>

<section class="dash">
  <div class="container">
    <header class="dash-header">
      <h1>Welcome, <?= e($display_name) ?>.</h1>
      <p>Let's get your first piece of paper off your counter.</p>
    </header>

    <ol class="getting-started">
      <li class="getting-started__step is-done">
        <span class="getting-started__marker" aria-hidden="true">&check;</span>
        Account created
      </li>
      <li class="getting-started__connector" aria-hidden="true"></li>
      <li class="getting-started__step is-current">
        <span class="getting-started__marker" aria-hidden="true"></span>
        Scan your first document
      </li>
    </ol>

    <div class="primary-action-card">
      <span class="primary-action-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/>
          <circle cx="12" cy="13" r="3.5"/>
        </svg>
      </span>
      <h2>Scan your first document</h2>
      <p>Photograph a bill, prescription, or handwritten note and OffPaper will read it for you.</p>
      <button type="button" class="btn btn--primary btn--lg" disabled aria-disabled="true">Scan a document</button>
      <span class="primary-action-card__badge">Coming soon</span>
    </div>

    <div class="category-grid">
      <article class="category-card">
        <span class="category-card__icon" aria-hidden="true">⚡</span>
        <h3>Deadlines</h3>
        <p>Once you scan a bill, it'll show up here with the due date already filled in.</p>
      </article>
      <article class="category-card">
        <span class="category-card__icon" aria-hidden="true">💊</span>
        <h3>Health records</h3>
        <p>Prescriptions and lab reports will appear here as clean, searchable records.</p>
      </article>
      <article class="category-card">
        <span class="category-card__icon" aria-hidden="true">✎</span>
        <h3>Notes</h3>
        <p>Handwritten notes you scan will turn into editable text here.</p>
      </article>
    </div>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
