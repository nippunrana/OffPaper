<?php
require_once __DIR__ . '/../src/bootstrap.php';

require_login();
$user = current_user();
$display_name = $user['name'] ?: ucfirst(explode('@', $user['email'])[0]);

$page_title = 'Dashboard';
$page_nav = 'dashboard';
$page_css = ['dashboard.css', 'scan.css', 'chat.css'];
$page_js = ['camera.js', 'doc-chat.js'];
$body_class = 'page-dashboard';

// Fetch user uploads from PostgreSQL
$uploads = [];
$stats = [
    'total' => 0,
    'bills' => 0,
    'deadline' => 0,
    'prescription' => 0,
    'labreport' => 0,
    'plan' => 0,
];

try {
    $db = db();
    $stmt = $db->prepare('
        SELECT u.*, kb.summary AS plan_summary
        FROM user_uploads u
        LEFT JOIN user_uploads_knowledgebase kb ON kb.user_upload_id = u.id
        WHERE u.user_id = :user_id 
        ORDER BY u.created_at DESC
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

        // Extract Summary & Multi-Category list
        $summary = $extracted['summary'] ?? '';
        $rawCategories = $extracted['categories'] ?? [];
        $categories = [];

        if (is_array($rawCategories) && !empty($rawCategories)) {
            $categories = array_values(array_unique($rawCategories));
        } else {
            $singleType = $u['doc_type'] ?? 'plan';
            $mapped = match ($singleType) {
                'bill', 'bills', 'receipt' => ['bills'],
                'deadline' => ['deadline'],
                'prescription' => ['prescription'],
                'labreport', 'lab_report' => ['labreport'],
                'plan', 'handwritten_note' => ['plan'],
                default => ['plan'],
            };
            $categories = $mapped;
        }

        // Extract Suggested Questions (from DB column or extracted_json fallback)
        $suggestedQuestions = [];
        if (!empty($u['suggested_questions'])) {
            if (is_array($u['suggested_questions'])) {
                $suggestedQuestions = $u['suggested_questions'];
            } else {
                $decodedQ = json_decode($u['suggested_questions'], true);
                if (is_array($decodedQ)) {
                    $suggestedQuestions = $decodedQ;
                }
            }
        }
        if (empty($suggestedQuestions) && !empty($extracted['suggested_questions'])) {
            $suggestedQuestions = is_array($extracted['suggested_questions']) ? $extracted['suggested_questions'] : [];
        }

        // Increment stats
        $stats['total']++;
        foreach ($categories as $cat) {
            if (isset($stats[$cat])) {
                $stats[$cat]++;
            }
        }

        $u['summary'] = $summary;
        $u['categories'] = $categories;
        $u['suggested_questions'] = $suggestedQuestions;
        $u['extracted_data'] = $extracted['data'] ?? $extracted;
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

    <?php if (!$hasUploads): ?>
    <!-- Onboarding Step Indicator -->
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
    <?php endif; ?>

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
        <p>Photograph a bill, prescription, lab report, plan, or deadline notice, and EarlySnap will automatically classify and turn it into actionable data.</p>
        <button type="button" class="btn btn--primary btn--lg" data-open-scan-modal>Scan a document</button>
      </div>

      <div class="category-grid">
        <article class="category-card">
          <span class="category-card__icon" aria-hidden="true">⚡</span>
          <h3>Bills</h3>
          <p>Itemized vendor items, prices, tax, and total bill amounts.</p>
        </article>
        <article class="category-card">
          <span class="category-card__icon" aria-hidden="true">⏰</span>
          <h3>Deadlines</h3>
          <p>Submission dates, payment due dates, doctor appointments, and handwritten prescription follow-ups.</p>
        </article>
        <article class="category-card">
          <span class="category-card__icon" aria-hidden="true">💊</span>
          <h3>Prescriptions</h3>
          <p>Doctor instructions, medication dosages, and intake schedules.</p>
        </article>
        <article class="category-card">
          <span class="category-card__icon" aria-hidden="true">🔬</span>
          <h3>Lab Reports</h3>
          <p>Diagnostic test panels, reference ranges, and abnormal flags.</p>
        </article>
        <article class="category-card">
          <span class="category-card__icon" aria-hidden="true">📋</span>
          <h3>Plans &amp; Notes</h3>
          <p>Handwritten notes, goals, and step-by-step action checklists.</p>
        </article>
      </div>
    <?php else: ?>
      <!-- ACTIVE DASHBOARD WITH UPLOADED DOCUMENTS -->
      
      <!-- Metrics summary grid -->
      <div class="dash-stats">
        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">📁</span>
            <span class="dash-stat-card__label">Total</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['total'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">⚡</span>
            <span class="dash-stat-card__label">Bills</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['bills'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">⏰</span>
            <span class="dash-stat-card__label">Deadlines</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['deadline'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">💊</span>
            <span class="dash-stat-card__label">Prescriptions</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['prescription'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">🔬</span>
            <span class="dash-stat-card__label">Lab Reports</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['labreport'] ?></div>
        </div>

        <div class="dash-stat-card">
          <div class="dash-stat-card__header">
            <span class="dash-stat-card__icon">📋</span>
            <span class="dash-stat-card__label">Plans</span>
          </div>
          <div class="dash-stat-card__value"><?= $stats['plan'] ?></div>
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
            <button type="button" class="dash-tab" data-filter="bills" role="tab" aria-selected="false">
              ⚡ Bills <span class="dash-tab__count"><?= $stats['bills'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['deadline'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="deadline" role="tab" aria-selected="false">
              ⏰ Deadlines <span class="dash-tab__count"><?= $stats['deadline'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['prescription'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="prescription" role="tab" aria-selected="false">
              💊 Prescriptions <span class="dash-tab__count"><?= $stats['prescription'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['labreport'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="labreport" role="tab" aria-selected="false">
              🔬 Lab Reports <span class="dash-tab__count"><?= $stats['labreport'] ?></span>
            </button>
          </li>
          <?php endif; ?>
          <?php if ($stats['plan'] > 0): ?>
          <li>
            <button type="button" class="dash-tab" data-filter="plan" role="tab" aria-selected="false">
              📋 Plans &amp; Notes <span class="dash-tab__count"><?= $stats['plan'] ?></span>
            </button>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Documents Grid -->
      <div class="doc-grid" id="docGrid">
        <?php foreach ($uploads as $doc): ?>
          <?php
            $cats = $doc['categories'] ?? ['plan'];
            $catsAttr = implode(',', $cats);
            $ext = $doc['extracted_data'] ?? [];
            $summary = $doc['summary'] ?? '';
            $createdFormatted = date('M j, Y \a\t g:i a', strtotime($doc['created_at']));
            $status = $doc['status'] ?? 'pending';

            // Determine primary document title based on extracted category data
            $docTitle = 'Scanned Document';

            if (isset($ext['bills']) && !empty($ext['bills']['vendor_name'])) {
                $vendor = $ext['bills']['vendor_name'];
                $total = $ext['bills']['grand_total'] ?? null;
                $curr = !empty($ext['bills']['currency']) ? trim($ext['bills']['currency']) . ' ' : '';
                $docTitle = $vendor . ($total ? ' (' . $curr . number_format((float)$total, 2) . ')' : '');
            } elseif (isset($ext['deadline']) && !empty($ext['deadline']['title'])) {
                $docTitle = $ext['deadline']['title'];
            } elseif (isset($ext['prescription']) && !empty($ext['prescription']['medications'])) {
                $medName = $ext['prescription']['medications'][0]['name'] ?? 'Prescription';
                $doctor = $ext['prescription']['doctor_name'] ?? null;
                $docTitle = $medName . ($doctor ? ' (by ' . $doctor . ')' : '');
            } elseif (isset($ext['labreport']) && !empty($ext['labreport']['lab_name'])) {
                $docTitle = $ext['labreport']['lab_name'] . ' Report';
            } elseif (isset($ext['plan']) && !empty($ext['plan']['plan_title'])) {
                $docTitle = $ext['plan']['plan_title'];
            } elseif (!empty($doc['original_filename'])) {
                $docTitle = $doc['original_filename'];
            }

            // Preview image URL via file helper
            $imgUrl = url('/file.php?uuid=' . $doc['uuid']);
            $docJsonAttr = htmlspecialchars(json_encode([
                'id' => $doc['id'],
                'uuid' => $doc['uuid'],
                'filename' => $doc['original_filename'],
                'file_path' => $imgUrl,
                'created_at' => $createdFormatted,
                'status' => $status,
                'summary' => $summary,
                'plan_summary' => $doc['plan_summary'] ?? '',
                'categories' => $cats,
                'suggested_questions' => $doc['suggested_questions'] ?? [],
                'extracted' => $ext,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
          ?>

          <article class="doc-card" data-doc-id="<?= (int)$doc['id'] ?>" data-categories="<?= e($catsAttr) ?>">
            <div class="doc-card__thumbnail">
              <button type="button" class="doc-card__delete-btn btn-delete-doc" data-doc-id="<?= (int)$doc['id'] ?>" title="Delete document" aria-label="Delete document">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
              <img src="<?= e($imgUrl) ?>" alt="<?= e($docTitle) ?>" loading="lazy">
              <div class="doc-card__badges">
                <?php foreach ($cats as $cat): ?>
                  <?php
                    $badgeConfig = match ($cat) {
                        'bills' => ['icon' => '⚡', 'label' => 'Bill', 'class' => 'doc-type--bills'],
                        'deadline' => ['icon' => '⏰', 'label' => 'Deadline', 'class' => 'doc-type--deadline'],
                        'prescription' => ['icon' => '💊', 'label' => 'Rx', 'class' => 'doc-type--prescription'],
                        'labreport' => ['icon' => '🔬', 'label' => 'Lab Report', 'class' => 'doc-type--labreport'],
                        'plan' => ['icon' => '📋', 'label' => 'Plan', 'class' => 'doc-type--plan'],
                        default => ['icon' => '📄', 'label' => 'Doc', 'class' => 'doc-type--plan'],
                    };
                  ?>
                  <span class="doc-card__badge <?= $badgeConfig['class'] ?>">
                    <span class="doc-card__badge-icon"><?= $badgeConfig['icon'] ?></span>
                    <?= e($badgeConfig['label']) ?>
                  </span>
                <?php endforeach; ?>
              </div>
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

              <!-- 10-20 Word AI Summary Callout -->
              <?php if (!empty($summary)): ?>
                <p class="doc-card__summary">“<?= e($summary) ?>”</p>
              <?php endif; ?>

              <!-- Category Snippets -->
              <div class="doc-card__extracted">
                <?php if (isset($ext['bills'])): ?>
                  <?php $b = $ext['bills']; ?>
                  <?php if (!empty($b['grand_total'])): ?>
                    <?php $curr = !empty($b['currency']) ? trim($b['currency']) . ' ' : ''; ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Grand Total:</span>
                      <strong class="doc-field__value"><?= e($curr) ?><?= e(number_format((float)$b['grand_total'], 2)) ?></strong>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (isset($ext['deadline'])): ?>
                  <?php
                    $d = $ext['deadline'];
                    $dueDateStr = !empty($d['due_date']) ? trim($d['due_date']) : '';
                    $dueTimeStr = !empty($d['due_time']) ? trim($d['due_time']) : '';
                    $isPast = false;
                    if (!empty($dueDateStr)) {
                        $dueTs = strtotime($dueDateStr . ($dueTimeStr ? ' ' . $dueTimeStr : ' 23:59:59'));
                        if ($dueTs !== false && $dueTs < time()) {
                            $isPast = true;
                        }
                    }
                  ?>
                  <?php if (!empty($d['due_date'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Due Date:</span>
                      <strong class="doc-field__value <?= $isPast ? '' : 'doc-field__value--urgent' ?>" style="<?= $isPast ? 'color:#dc2626;' : '' ?>">
                        <?= e($d['due_date']) ?><?= !empty($d['due_time']) ? ' (' . e($d['due_time']) . ')' : '' ?>
                        <?= $isPast ? ' <span style="font-size: 0.75rem; font-weight: normal; background: rgba(239,68,68,0.15); padding: 2px 6px; border-radius: 4px; color: #dc2626;">(Passed)</span>' : '' ?>
                      </strong>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (isset($ext['prescription'])): ?>
                  <?php $rx = $ext['prescription']; ?>
                  <?php if (!empty($rx['medications'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Medication:</span>
                      <span class="doc-field__value"><?= e($rx['medications'][0]['name'] ?? 'Prescription') ?></span>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (isset($ext['labreport'])): ?>
                  <?php $lab = $ext['labreport']; ?>
                  <?php if (!empty($lab['test_results'])): ?>
                    <div class="doc-field">
                      <span class="doc-field__label">Tests Count:</span>
                      <span class="doc-field__value"><?= count($lab['test_results']) ?> test items</span>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <div class="doc-card__actions doc-card__actions-grid">
                <button type="button" class="btn btn--secondary btn--sm" data-open-doc-detail='<?= $docJsonAttr ?>'>
                  View Details
                </button>
                <button type="button" class="btn btn--chat btn--sm" data-open-doc-chat='<?= $docJsonAttr ?>'>
                  <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                  </svg>
                  Chat with AI
                </button>
                <?php
                  $d = $ext['deadline'] ?? null;
                  if (!$d && isset($ext['bills']['payment_due_date']) && !empty(trim($ext['bills']['payment_due_date']))) {
                      $d = [
                          'title' => ($ext['bills']['vendor_name'] ?? 'Bill') . ' Payment Due',
                          'due_date' => trim($ext['bills']['payment_due_date']),
                          'due_time' => '',
                          'calendar_event_id' => $ext['bills']['calendar_event_id'] ?? '',
                          'calendar_html_link' => $ext['bills']['calendar_html_link'] ?? '',
                      ];
                  }
                ?>
                <?php if ($d): ?>
                  <?php
                    $dueDateStr = !empty($d['due_date']) ? trim($d['due_date']) : '';
                    $dueTimeStr = !empty($d['due_time']) ? trim($d['due_time']) : '';
                    $isPast = false;
                    if (!empty($dueDateStr)) {
                        $dueTs = strtotime($dueDateStr . ($dueTimeStr ? ' ' . $dueTimeStr : ' 23:59:59'));
                        if ($dueTs !== false && $dueTs < time()) {
                            $isPast = true;
                        }
                    }
                  ?>
                  <?php if ($isPast): ?>
                    <span class="btn btn--sm" style="grid-column: 1 / -1; cursor: default; background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.3); font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                      ⚠️ Due date already passed
                    </span>
                  <?php elseif (!empty($d['calendar_html_link'])): ?>
                    <a href="<?= e($d['calendar_html_link']) ?>" target="_blank" rel="noopener" class="btn btn--calendar btn--sm btn--calendar-synced" style="grid-column: 1 / -1;">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                      In Google Calendar
                    </a>
                  <?php else: ?>
                    <button type="button" class="btn btn--calendar btn--sm btn-add-calendar" data-doc-id="<?= (int)$doc['id'] ?>" style="grid-column: 1 / -1;">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
                      Add to Calendar
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
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
      <h2 id="docDetailTitle">Document Category Details</h2>
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
            <div id="detailBadgesContainer" class="doc-card__badges" style="position:static;"></div>
            <span id="detailDocStatus" class="status-tag">Status</span>
            <time id="detailDocDate" class="doc-card__date"></time>
          </div>

          <h3 id="detailDocHeading" class="doc-detail-heading">Document Title</h3>

          <!-- AI Plan Notes Block (shown only for plan category docs with a saved summary) -->
          <div id="detailPlanNotesBlock" class="doc-detail-summary-banner doc-detail-plan-notes-banner" style="display: none; background: #f0fdf4; border-left-color: #22c55e; color: #14532d; margin-top: var(--space-2); margin-bottom: var(--space-2);">
            <strong style="color: #15803d; font-size: var(--text-xs); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: var(--space-1); display: flex; align-items: center; gap: 4px;">📋 AI Plan Notes</strong>
            <span id="detailPlanNotesText" style="display: block; line-height: 1.5;"></span>
          </div>

          <!-- AI Summary Banner -->
          <div id="detailSummaryBanner" class="doc-detail-summary-banner">
            <strong>AI Summary</strong>
            <span id="detailSummaryText"></span>
          </div>

          <div id="detailFieldsContainer" class="doc-detail-fields">
            <!-- Dynamic key-value fields rendered via JS -->
          </div>

          <div class="doc-detail-actions" style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
            <a id="detailDownloadLink" href="#" target="_blank" download class="btn btn--secondary btn--sm">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              View Full Original Image
            </a>
            <button type="button" id="detailDocChatBtn" class="btn btn--chat btn--sm" data-open-doc-chat="">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
              </svg>
              Chat with AI
            </button>
            <button type="button" id="detailAddToCalendarBtn" class="btn btn--calendar btn--sm btn-add-calendar" style="display:none;" data-doc-id="">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
              Add to Google Calendar
            </button>
            <a id="detailViewCalendarLink" href="#" target="_blank" rel="noopener" class="btn btn--calendar btn--sm btn--calendar-synced" style="display:none;">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
              In Google Calendar
            </a>
            <button type="button" id="detailDeleteDocBtn" class="btn btn--danger-ghost btn--sm btn-delete-doc" data-doc-id="" style="margin-left: auto;">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
              Delete Document
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require VIEWS . '/scan_modal.php'; ?>
<?php require VIEWS . '/chat_modal.php'; ?>

<?php require VIEWS . '/footer.php'; ?>
