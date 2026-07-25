<?php
require_once __DIR__ . '/../src/bootstrap.php';

require_login();
$user = current_user();

$page_title = 'Scan Document';
$page_nav = 'scan';
$page_css = ['scan.css'];
$page_js = ['camera.js'];
$body_class = 'page-scan';

require VIEWS . '/header.php';
?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">

<section class="scan-section">
  <div class="container">
    <header class="dash-header">
      <h1>Scan a Document</h1>
      <p>Hold up your bill, prescription, or handwritten note to capture it cleanly.</p>
    </header>

    <div class="scan-layout">
      <!-- Live Web Camera Feed Container -->
      <div id="cameraContainer" class="scan-card">
        <div class="camera-viewport">
          <video id="cameraVideo" class="camera-video" autoplay playsinline muted></video>
          <div class="camera-overlay">
            <div class="camera-guide-frame">
              <span class="camera-guide-hint">Align paper within frame</span>
            </div>
          </div>
        </div>

        <canvas id="cameraCanvas" class="camera-canvas"></canvas>

        <div class="camera-controls">
          <button type="button" id="flipBtn" class="btn btn--secondary btn--sm" style="display: none;" title="Flip Camera">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 10c0-4.4-3.6-8-8-8s-8 3.6-8 8h-3l4 5 4-5h-3c0-3.3 2.7-6 6-6s6 2.7 6 6h-2l3.5 4.5L22 10h-2z"/>
              <path d="M4 14c0 4.4 3.6 8 8 8s8-3.6 8-8h3l-4-5-4 5h3c0 3.3-2.7 6-6 6s-6-2.7-6-6h2l-3.5-4.5L1 14h3z"/>
            </svg>
            Flip
          </button>

          <button type="button" id="snapBtn" class="camera-btn-snap" title="Capture Photo" aria-label="Capture Photo">
            <svg class="camera-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span>Capture Photo</span>
          </button>


          <label for="fileInput" class="btn btn--secondary btn--sm" style="cursor: pointer;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Choose File
          </label>
        </div>
      </div>

      <!-- File Picker & Drag-and-Drop Fallback -->
      <div id="uploadDropzone" class="upload-dropzone" style="display: none;">
        <svg class="upload-dropzone__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/>
          <circle cx="12" cy="13" r="3.5"/>
        </svg>
        <h3 class="upload-dropzone__title">Upload or Take Photo</h3>
        <p class="upload-dropzone__desc">Click to browse or drop an image file here</p>
        <input type="file" id="fileInput" accept="image/*" capture="environment" style="display: none;">
      </div>

      <!-- Captured Image Preview & Confirmation Card -->
      <div id="previewCard" class="preview-panel" style="display: none;">
        <h3>Review Captured Document</h3>
        <div class="preview-image-container">
          <img id="previewImg" class="preview-image" src="" alt="Captured Document Preview">
        </div>

        <div id="uploadStatus"></div>

        <div class="preview-actions">
          <button type="button" id="retakeBtn" class="btn btn--secondary">Retake Photo</button>
          <button type="button" id="uploadBtn" class="btn btn--primary">Confirm &amp; Upload</button>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
