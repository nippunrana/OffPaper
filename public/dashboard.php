<?php
require_once __DIR__ . '/../src/bootstrap.php';

require_login();
$user = current_user();
$display_name = $user['name'] ?: ucfirst(explode('@', $user['email'])[0]);

$page_title = 'Dashboard';
$page_nav = 'dashboard';
$page_css = ['dashboard.css', 'scan.css'];
$page_js = ['camera.js'];
$body_class = 'page-dashboard';

// Fetch user uploads from PostgreSQL
$uploads = [];
$stats = [
    'total' => 0,
    'bills' => 0,
    'health' => 0,
    'notes' => 0,
    'general' => 0,
];

try {
    $db = db();
    $stmt = $db->prepare('
        SELECT * FROM user_uploads 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ');
    $stmt->execute([':user_id' => $user['id']]);
    $rawUploads = $stmt->fetchAll();

    foreach ($rawUploads as $u) {
        $extracted = [];
        if (!empty($u['extracted_json'])) {
            if (is_array($u['extracted_json'])) {
                $extracted = $u['extracted_json'];
            } else {
                $decoded = json_decode($u['extracted_json'], true);
                if (is_array($decoded)) {
                    $extracted = $decoded;
                }
            }
        }

        $type = $u['doc_type'] ?? 'general';
        if (!in_array($type, ['bill', 'prescription', 'handwritten_note', 'receipt', 'general'], true)) {
            $type = 'general';
        }

        $stats['total']++;
        if ($type === 'bill') {
            $stats['bills']++;
        } elseif ($type === 'prescription') {
            $stats['health']++;
        } elseif ($type === 'handwritten_note') {
            $stats['notes']++;
        } else {
            $stats['general']++;
        }

        $u['extracted_data'] = $extracted;
        $uploads[] = $u;
    }
} catch (Throwable $e) {
    error_log('Dashboard upload query failed: ' . $e->getMessage());
}

$hasUploads = $stats['total'] > 0;

require VIEWS . '/header.php';
?>

<section class="dash">
  <div class="container">
    <header class="dash-header dash-header--flex">
      <div>
        <h1>Welcome, <?= e($display_name) ?>.</h1>
        <p><?= $hasUploads ? 'Here is the smart overview of all your paper documents.' : "Let's get your first piece of paper off your counter." ?></p>
      </div>

      <?php if ($hasUploads): ?>
      <div class="dash-header__action">
        <button type="button" class="btn btn--primary" data-open-scan-modal>
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
            <circle cx="12" cy="13" r="4"/>
          </svg>
          Scan New Document
        </button>
      </div>
      <?php endif; ?>
    </header>

    <!-- Onboarding Step Indicator -->
    <ol class="getting-started">
      <li class="getting-started__step is-done">
        <span class="getting-started__marker" aria-hidden="true">&check;</span>
        Account created
      </li>
      <li class="getting-started__connector" aria-hidden="true"></li>
      <li class="getting-started__step <?= $hasUploads ? 'is-done' : 'is-current' ?>">
        <span class="getting-started__marker" aria-hidden="true"><?= $hasUploads ? '&check;' : '' ?></span>
        <?= $hasUploads ? 'First document scanned' : 'Scan your first document' ?>
      </li>
    </ol>

    <?php if (!$hasUploads): ?>
      <!-- EMPTY STATE FOR FIRST TIME USERS -->
      <div class="primary-action-card">
        <span class="primary-action-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/>
            <circle cx="12" cy="13" r="3.5"/>
          </svg>
        </span>
        <h2>Scan your first document</h2>
        <p>Photograph a bill, prescription, or handwritten note and OffPaper will read it for you.</p>
        <button type="button" class="btn btn--primary btn--lg" data-open-scan-modal>Scan a document</button>
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
    <?php else: ?>
      <!-- ACTIVE DASHBOARD WITH UPLOADED DOCUMENTS -->
      
      <!-- Metrics summary grid -->
      <div class="dash-stats">
        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">📁</span>
            <span class="dash-stat-card__label">Total Scanned</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['total'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">⚡</span>
            <span class="dash-stat-card__label">Deadlines &amp; Bills</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['bills'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">💊</span>
            <span class="dash-stat-card__label">Health Records</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['health'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">✏️</span>
            <span class="dash-stat-card__label">Notes &amp; Plans</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['notes'] ?></div>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="dash-filter-bar">
        <ul class="dash-tabs" role="tablist">
          <li>
            <button type="button" class="dash-tab is-active" data-filter="all" role="tab" aria-selected="true">
              All Documents <span class="dash-tab__count"><?= $stats['total'] ?></span>
            </button>
          </li>
          <?php if ($stats['bills'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="bill" role="tab" aria-selected="false">
              ⚡ Bills &amp; Deadlines <span class="dash-tab__count"><?= $stats['bills'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['health'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="prescription" role="tab" aria-selected="false">
              💊 Health Records <span class="dash-tab__count"><?= $stats['health'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['notes'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="handwritten_note" role="tab" aria-selected="false">
              ✏️ Notes &amp; Plans <span class="dash-tab__count"><?= $stats['notes'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['general'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="general" role="tab" aria-selected="false">
              📄 General &amp; Receipts <span class="dash-tab__count"><?= $stats['general'] ?></span>
            </button>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Documents Grid -->
      <div class="doc-grid" id="docGrid">
        <?php foreach ($uploads as $doc): ?>
          <?php
            $docType = $doc['doc_type'] ?? 'general';
            $ext = $doc['extracted_data'] ?? [];
            $createdFormatted = date('M j, Y \a\t g:i a', strtotime($doc['created_at']));
            $status = $doc['status'] ?? 'pending';

            // Determine title and key highlights based on extracted data
            $docTitle = 'Document';
            $icon = '📄';
            $typeLabel = 'General Document';
            $typeClass = 'doc-type--general';

            if ($docType === 'bill') {
                $icon = '⚡';
                $typeLabel = 'Bill / Deadline';
                $typeClass = 'doc-type--bill';
                $vendor = $ext['biller_name'] ?? $ext['vendor_name'] ?? $ext['payee'] ?? $ext['vendor'] ?? null;
                $amount = $ext['amount_due'] ?? $ext['total_amount'] ?? $ext['amount'] ?? null;
                if ($vendor) {
                    $docTitle = $vendor . ($amount ? ' (' . e($amount) . ')' : '');
                } else {
                    $docTitle = 'Scanned Bill' . ($amount ? ' — ' . e($amount) : '');
                }
            } elseif ($docType === 'prescription') {
                $icon = '💊';
                $typeLabel = 'Health Record';
                $typeClass = 'doc-type--health';
                $doctor = $ext['doctor_name'] ?? $ext['prescriber'] ?? $ext['provider'] ?? null;
                $med = $ext['medication_name'] ?? $ext['medication'] ?? $ext['drug'] ?? null;
                if ($med) {
                    $docTitle = $med . ($doctor ? ' (by ' . e($doctor) . ')' : '');
                } else {
                    $docTitle = $doctor ? 'Prescription by ' . e($doctor) : 'Health Record';
                }
            } elseif ($docType === 'handwritten_note') {
                $icon = '✏️';
                $typeLabel = 'Handwritten Note';
                $typeClass = 'doc-type--note';
                $noteTitle = $ext['title'] ?? $ext['heading'] ?? $ext['subject'] ?? null;
                $docTitle = $noteTitle ? $noteTitle : 'Handwritten Note';
            } else {
                $docTitle = $ext['title'] ?? $ext['document_title'] ?? $doc['original_filename'];
            }

            // Preview image URL via secure file server endpoint
            $imgUrl = url('/file.php?uuid=' . $doc['uuid']);
            $docJsonAttr = htmlspecialchars(json_encode([
                'id' => $doc['id'],
                'uuid' => $doc['uuid'],
                'filename' => $doc['original_filename'],
                'file_path' => $imgUrl,
                'created_at' => $createdFormatted,
                'doc_type' => $docType,
                'status' => $status,
                'extracted' => $ext,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
          ?>

          <article class="doc-card" data-doc-type="<?= e($docType) ?>">
            <div class="doc-card__thumbnail">
              <img src="<?= e($imgUrl) ?>" alt="<?= e($docTitle) ?>" loading="lazy">
              <span class="doc-card__badge <?= e($typeClass) ?>">
                <span class="doc-card__badge-icon"><?= $icon ?></span>
                <?= e($typeLabel) ?>
              </span>
            </div>

            <div class="doc-card__body">
              <div class="doc-card__status">
                <?php if ($status === 'processed'): ?>
                  <span class="status-tag status-tag--success">✓ Processed</span>
                <?php elseif ($status === 'error'): ?>
                  <span class="status-tag status-tag--error">⚠️ Error processing</span>
                <?php else: ?>
                  <span class="status-tag status-tag--pending">⏳ Processing AI...</span>
                <?php endif; ?>
                <time class="doc-card__date"><?= e(date('M j, Y', strtotime($doc['created_at']))) ?></time>
              </div>

              <h3 class="doc-card__title"><?= e($docTitle) ?></h3>

              <!-- Extracted Fields Snippet -->
              <div class="doc-card__extracted">
                <?php if ($docType === 'bill'): ?>
                  <?php if (!empty($ext['due_date'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Due Date:</span>
                      <strong class="doc-field__value doc-field__value--urgent"><?= e($ext['due_date']) ?></strong>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($ext['amount_due']) || !empty($ext['total_amount'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Amount:</span>
                      <span class="doc-field__value"><?= e($ext['amount_due'] ?? $ext['total_amount']) ?></span>
                    </div>
                  <?php endif; ?>
                <?php elseif ($docType === 'prescription'): ?>
                  <?php if (!empty($ext['instructions']) || !empty($ext['dosage'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Instructions:</span>
                      <span class="doc-field__value"><?= e($ext['instructions'] ?? $ext['dosage']) ?></span>
                    </div>
                  <?php endif; ?>
                <?php elseif ($docType === 'handwritten_note'): ?>
                  <?php 
                    $textPreview = $ext['transcribed_text'] ?? $ext['summary'] ?? $ext['content'] ?? null;
                    if ($textPreview): 
                  ?>
                    <p class="doc-card__text-preview">
                      <?= e(mb_strimwidth(is_array($textPreview) ? implode(' ', $textPreview) : $textPreview, 0, 120, '...')) ?>
                    </p>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (empty($ext)): ?>
                  <p class="doc-card__text-preview">Document stored cleanly. AI scanning in progress.</p>
                <?php endif; ?>
              </div>

              <div class="doc-card__actions">
                <button type="button" class="btn btn--secondary btn--sm btn--block" data-open-doc-detail='<?= $docJsonAttr ?>'>
                  View Details &amp; AI Summary
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Document Details Modal -->
<div id="docDetailModal" class="scan-modal doc-detail-modal" role="dialog" aria-modal="true" aria-labelledby="docDetailTitle" style="display: none;">
  <div class="scan-modal__dialog doc-detail-modal__dialog">
    <div class="scan-modal__header">
      <h2 id="docDetailTitle">Document Details</h2>
      <button type="button" class="scan-modal__close" id="closeDocDetailBtn" aria-label="Close modal">&times;</button>
    </div>

    <div class="scan-modal__body doc-detail-modal__body">
      <div class="doc-detail-grid">
        <!-- Left column: Image view -->
        <div class="doc-detail-image-wrapper">
          <img id="detailDocImage" src="" alt="Document Image" class="doc-detail-image">
        </div>

        <!-- Right column: Extracted fields & OCR summary -->
        <div class="doc-detail-content">
          <div class="doc-detail-meta-header">
            <span id="detailDocBadge" class="doc-card__badge">📄 General</span>
            <span id="detailDocStatus" class="status-tag">Status</span>
            <time id="detailDocDate" class="doc-card__date"></time>
          </div>

          <h3 id="detailDocHeading" class="doc-detail-heading">Document Title</h3>

          <div id="detailFieldsContainer" class="doc-detail-fields">
            <!-- Dynamic key-value fields rendered via JS -->
          </div>

          <div class="doc-detail-actions">
            <a id="detailDownloadLink" href="#" target="_blank" download class="btn btn--secondary btn--sm">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              View Full Original Image
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require VIEWS . '/scan_modal.php'; ?>

<?php require VIEWS . '/footer.php'; ?>

