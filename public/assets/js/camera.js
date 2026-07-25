/**
 * OffPaper Web Camera Capture & Pop-over Modal Controller
 */
document.addEventListener('DOMContentLoaded', () => {
  const scanModal = document.getElementById('scanModal');
  const openModalBtns = document.querySelectorAll('[data-open-scan-modal]');
  const closeModalBtns = document.querySelectorAll('[data-close-scan-modal]');

  const cameraContainer = document.getElementById('cameraContainer');
  const video = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  const snapBtn = document.getElementById('snapBtn');
  const flipBtn = document.getElementById('flipBtn');
  const fileInput = document.getElementById('fileInput');
  const dropzone = document.getElementById('uploadDropzone');

  const previewCard = document.getElementById('previewCard');
  const previewImg = document.getElementById('previewImg');
  const retakeBtn = document.getElementById('retakeBtn');
  const uploadBtn = document.getElementById('uploadBtn');
  const uploadStatus = document.getElementById('uploadStatus');

  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
  const uploadUrl = document.body.dataset.uploadUrl || 'upload.php';

  let currentStream = null;
  let videoDevices = [];
  let currentDeviceIndex = 0;
  let capturedBlob = null;
  let capturedFile = null;
  let isCameraActive = false;

  // Open modal handler
  openModalBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openScanModal();
    });
  });

  // Close modal handler
  closeModalBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      closeScanModal();
    });
  });

  // Close modal when clicking backdrop
  if (scanModal) {
    scanModal.addEventListener('click', (e) => {
      if (e.target === scanModal) {
        closeScanModal();
      }
    });
  }

  // Close modal on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && scanModal && scanModal.classList.contains('is-open')) {
      closeScanModal();
    }
  });

  function openScanModal() {
    if (scanModal) {
      scanModal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    }
    resetState();
    initCamera();
  }

  function closeScanModal() {
    if (scanModal) {
      scanModal.classList.remove('is-open');
      document.body.style.overflow = '';
    }
    stopStream();
  }

  function resetState() {
    capturedBlob = null;
    capturedFile = null;
    if (previewCard) previewCard.style.display = 'none';
    if (uploadStatus) uploadStatus.innerHTML = '';
    if (uploadBtn) {
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Confirm & Upload';
    }
    if (fileInput) fileInput.value = '';
    if (cameraContainer) cameraContainer.style.display = 'block';
    if (dropzone) dropzone.style.display = 'none';
  }

  // Initialize camera feed if available
  async function initCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      showFallbackDropzone('Camera access is not supported by your browser.');
      return;
    }

    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      videoDevices = devices.filter(device => device.kind === 'videoinput');

      if (videoDevices.length > 1 && flipBtn) {
        flipBtn.style.display = 'inline-flex';
      }

      await startStream();
    } catch (err) {
      console.warn('Camera initialization error:', err);
      showFallbackDropzone('Camera permission denied or camera unavailable. You can select an image file below.');
    }
  }

  async function startStream() {
    stopStream();

    const constraints = {
      audio: false,
      video: {
        width: { ideal: 1920 },
        height: { ideal: 1080 }
      }
    };

    if (videoDevices.length > 0 && videoDevices[currentDeviceIndex]) {
      constraints.video.deviceId = { exact: videoDevices[currentDeviceIndex].deviceId };
    } else {
      constraints.video.facingMode = { ideal: 'environment' };
    }

    try {
      currentStream = await navigator.mediaDevices.getUserMedia(constraints);
      if (video) {
        video.srcObject = currentStream;
        await video.play();
      }
      isCameraActive = true;
      if (cameraContainer) cameraContainer.style.display = 'block';
      if (dropzone) dropzone.style.display = 'none';
    } catch (err) {
      console.warn('Unable to start video stream:', err);
      showFallbackDropzone('Could not start live camera feed. Please select an image file directly.');
    }
  }

  function stopStream() {
    if (currentStream) {
      currentStream.getTracks().forEach(track => track.stop());
      currentStream = null;
    }
    isCameraActive = false;
  }

  function showFallbackDropzone(reasonMessage) {
    if (cameraContainer) cameraContainer.style.display = 'none';
    if (dropzone) {
      dropzone.style.display = 'block';
      if (reasonMessage) {
        const desc = dropzone.querySelector('.upload-dropzone__desc');
        if (desc) desc.textContent = reasonMessage;
      }
    }
  }

  // Snap photo from video stream
  if (snapBtn) {
    snapBtn.addEventListener('click', () => {
      if (!isCameraActive || !video || !video.videoWidth) return;

      const context = canvas.getContext('2d');
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      const currentTrack = currentStream ? currentStream.getVideoTracks()[0] : null;
      const settings = currentTrack ? currentTrack.getSettings() : {};

      context.save();
      if (settings.facingMode === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
      }

      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      context.restore();

      canvas.toBlob((blob) => {
        if (!blob) {
          showFallbackDropzone('Failed to capture frame from camera.');
          return;
        }
        capturedBlob = blob;
        capturedFile = null;

        const previewUrl = URL.createObjectURL(blob);
        showPreview(previewUrl);
      }, 'image/jpeg', 0.92);
    });
  }

  // Flip camera between front/rear
  if (flipBtn) {
    flipBtn.addEventListener('click', () => {
      if (videoDevices.length <= 1) return;
      currentDeviceIndex = (currentDeviceIndex + 1) % videoDevices.length;
      startStream();
    });
  }

  // File input change handler
  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;
      handleFileSelected(file);
    });
  }

  // Drag and drop handlers
  if (dropzone) {
    dropzone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropzone.classList.add('is-dragover');
    });

    dropzone.addEventListener('dragleave', () => {
      dropzone.classList.remove('is-dragover');
    });

    dropzone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropzone.classList.remove('is-dragover');
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        handleFileSelected(file);
      }
    });

    dropzone.addEventListener('click', (e) => {
      if (e.target.tagName !== 'INPUT' && fileInput) fileInput.click();
    });
  }

  function handleFileSelected(file) {
    capturedFile = file;
    capturedBlob = null;

    const previewUrl = URL.createObjectURL(file);
    showPreview(previewUrl);
  }

  function showPreview(dataUrl) {
    stopStream();
    if (cameraContainer) cameraContainer.style.display = 'none';
    if (dropzone) dropzone.style.display = 'none';

    if (previewImg) previewImg.src = dataUrl;
    if (previewCard) previewCard.style.display = 'block';
  }

  // Retake photo action
  if (retakeBtn) {
    retakeBtn.addEventListener('click', () => {
      resetState();
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        initCamera();
      } else {
        showFallbackDropzone();
      }
    });
  }

  // Submit/Upload photo action
  if (uploadBtn) {
    uploadBtn.addEventListener('click', async () => {
      uploadBtn.disabled = true;
      uploadBtn.textContent = 'Uploading...';
      if (uploadStatus) {
        uploadStatus.innerHTML = '<span class="status-badge">Uploading captured image...</span>';
      }

      try {
        const targetUploadUrl = document.body.dataset.uploadUrl || 'upload.php';
        const currentCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const formData = new FormData();
        formData.append('csrf', currentCsrfToken);

        if (capturedBlob) {
          formData.append('photo', capturedBlob, 'camera_capture_' + Date.now() + '.jpg');
          formData.append('source', 'camera');
        } else if (capturedFile) {
          formData.append('photo', capturedFile);
          formData.append('source', 'file_input');
        } else {
          throw new Error('No image selected to upload.');
        }

        const response = await fetch(targetUploadUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const responseText = await response.text();
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (parseErr) {
          console.error('Server returned non-JSON response:', responseText);
          throw new Error('Server returned invalid response (Status ' + response.status + ').');
        }

        if (response.ok && data.success) {
          if (uploadStatus) {
            uploadStatus.innerHTML = `
              <div class="flash flash--success">
                <strong>Document captured!</strong><br>
                Saved with UUID: <code>${data.upload.uuid}</code><br>
                Classified Categories: <code>${(data.upload.categories || [data.upload.doc_type]).join(', ')}</code>
              </div>
            `;
          }
          uploadBtn.textContent = 'Uploaded Successfully';

          setTimeout(() => {
            closeScanModal();
            window.location.reload();
          }, 1500);
        } else {
          throw new Error(data.message || 'Upload failed');
        }
      } catch (err) {
        console.error('Upload Error:', err);
        if (uploadStatus) {
          uploadStatus.innerHTML = `<div class="flash flash--error">Error: ${err.message}</div>`;
        }
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Confirm & Upload';
      }
    });
  }

  // Standalone scan.php handler
  if (!scanModal && cameraContainer) {
    initCamera();
  }

  // --- DASHBOARD MULTI-CATEGORY TAB FILTERING ---
  const tabBtns = document.querySelectorAll('.dash-tab');
  const docCards = document.querySelectorAll('.doc-card');

  tabBtns.forEach(tab => {
    tab.addEventListener('click', () => {
      tabBtns.forEach(b => {
        b.classList.remove('is-active');
        b.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('is-active');
      tab.setAttribute('aria-selected', 'true');

      const filter = tab.dataset.filter;
      docCards.forEach(card => {
        const rawCategories = card.dataset.categories || '';
        const categoriesList = rawCategories.split(',').map(s => s.trim());

        if (filter === 'all' || categoriesList.includes(filter)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // --- DOCUMENT DETAIL MODAL CONTROLLER ---
  const docDetailModal = document.getElementById('docDetailModal');
  const closeDocDetailBtn = document.getElementById('closeDocDetailBtn');
  const openDetailBtns = document.querySelectorAll('[data-open-doc-detail]');

  const detailDocImage = document.getElementById('detailDocImage');
  const detailBadgesContainer = document.getElementById('detailBadgesContainer');
  const detailDocStatus = document.getElementById('detailDocStatus');
  const detailDocDate = document.getElementById('detailDocDate');
  const detailDocHeading = document.getElementById('detailDocHeading');
  const detailSummaryBanner = document.getElementById('detailSummaryBanner');
  const detailSummaryText = document.getElementById('detailSummaryText');
  const detailFieldsContainer = document.getElementById('detailFieldsContainer');
  const detailDownloadLink = document.getElementById('detailDownloadLink');

  openDetailBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const rawData = btn.getAttribute('data-open-doc-detail');
      if (!rawData) return;

      try {
        const doc = JSON.parse(rawData);
        openDocumentDetail(doc);
      } catch (err) {
        console.error('Failed to parse document detail JSON:', err);
      }
    });
  });

  function openDocumentDetail(doc) {
    if (!docDetailModal) return;

    if (detailDocImage) detailDocImage.src = doc.file_path || '';
    if (detailDocDate) detailDocDate.textContent = doc.created_at || '';
    if (detailDownloadLink) detailDownloadLink.href = doc.file_path || '#';

    // Status tag
    if (detailDocStatus) {
      detailDocStatus.className = 'status-tag';
      if (doc.status === 'processed') {
        detailDocStatus.classList.add('status-tag--success');
        detailDocStatus.textContent = '✓ Processed';
      } else if (doc.status === 'error') {
        detailDocStatus.classList.add('status-tag--error');
        detailDocStatus.textContent = '⚠️ Processing Error';
      } else {
        detailDocStatus.classList.add('status-tag--pending');
        detailDocStatus.textContent = '⏳ Processing...';
      }
    }

    // Category Badges
    const categories = doc.categories || [doc.doc_type || 'plan'];
    if (detailBadgesContainer) {
      detailBadgesContainer.innerHTML = '';
      categories.forEach(cat => {
        const badgeEl = document.createElement('span');
        let icon = '📄';
        let label = cat;
        let typeClass = 'doc-type--plan';

        if (cat === 'bills') {
          icon = '⚡'; label = 'Bill'; typeClass = 'doc-type--bills';
        } else if (cat === 'deadline') {
          icon = '⏰'; label = 'Deadline'; typeClass = 'doc-type--deadline';
        } else if (cat === 'prescription') {
          icon = '💊'; label = 'Prescription'; typeClass = 'doc-type--prescription';
        } else if (cat === 'labreport') {
          icon = '🔬'; label = 'Lab Report'; typeClass = 'doc-type--labreport';
        } else if (cat === 'plan') {
          icon = '📋'; label = 'Plan'; typeClass = 'doc-type--plan';
        }

        badgeEl.className = `doc-card__badge ${typeClass}`;
        badgeEl.innerHTML = `<span class="doc-card__badge-icon">${icon}</span> ${label}`;
        detailBadgesContainer.appendChild(badgeEl);
      });
    }

    // AI Summary Banner
    if (detailSummaryBanner && detailSummaryText) {
      if (doc.summary) {
        detailSummaryBanner.style.display = 'block';
        detailSummaryText.textContent = `“${doc.summary}”`;
      } else {
        detailSummaryBanner.style.display = 'none';
      }
    }

    // Dynamic Title
    const ext = doc.extracted || {};
    let title = doc.filename || 'Document';

    if (ext.bills && ext.bills.vendor_name) {
      title = ext.bills.vendor_name + (ext.bills.grand_total ? ` ($${ext.bills.grand_total})` : '');
    } else if (ext.deadline && ext.deadline.title) {
      title = ext.deadline.title;
    } else if (ext.prescription && ext.prescription.medications && ext.prescription.medications.length) {
      title = ext.prescription.medications[0].name + (ext.prescription.doctor_name ? ` (by ${ext.prescription.doctor_name})` : '');
    } else if (ext.labreport && ext.labreport.lab_name) {
      title = ext.labreport.lab_name + ' Report';
    } else if (ext.plan && ext.plan.plan_title) {
      title = ext.plan.plan_title;
    }

    if (detailDocHeading) detailDocHeading.textContent = title;

    // Render Extracted Fields by Category
    if (detailFieldsContainer) {
      detailFieldsContainer.innerHTML = '';

      if (!ext || Object.keys(ext).length === 0) {
        detailFieldsContainer.innerHTML = '<p class="doc-card__text-preview">AI processing in progress or no extracted data available.</p>';
      } else {
        // Check if extracted has category keys (bills, deadline, prescription, labreport, plan)
        const categoryKeys = ['bills', 'deadline', 'prescription', 'labreport', 'plan'];
        let renderedSections = false;

        categoryKeys.forEach(catKey => {
          if (ext[catKey] && typeof ext[catKey] === 'object') {
            renderedSections = true;
            const secData = ext[catKey];

            const secEl = document.createElement('div');
            secEl.className = 'detail-category-block';

            let secTitle = 'Category Details';
            let secIcon = '📄';
            if (catKey === 'bills') { secTitle = 'Bill & Invoice Items'; secIcon = '⚡'; }
            if (catKey === 'deadline') { secTitle = 'Deadline Notice'; secIcon = '⏰'; }
            if (catKey === 'prescription') { secTitle = 'Medical Prescription'; secIcon = '💊'; }
            if (catKey === 'labreport') { secTitle = 'Lab Test Panel'; secIcon = '🔬'; }
            if (catKey === 'plan') { secTitle = 'Action Plan & Checklist'; secIcon = '📋'; }

            let bodyHtml = `<h4 class="detail-category-block__title"><span>${secIcon}</span> ${secTitle}</h4>`;

            // Bills renderer
            if (catKey === 'bills') {
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Vendor:</span> <strong class="doc-field__value">${secData.vendor_name || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Bill Date:</span> <span class="doc-field__value">${secData.bill_date || 'N/A'}</span></div>
                <div class="doc-field"><span class="doc-field__label">Invoice #:</span> <span class="doc-field__value">${secData.invoice_number || 'N/A'}</span></div>
                <div class="doc-field"><span class="doc-field__label">Grand Total:</span> <strong class="doc-field__value" style="color:#059669;">${secData.currency || '$'} ${secData.grand_total || '0.00'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Payment Due:</span> <strong class="doc-field__value doc-field__value--urgent">${secData.payment_due_date || 'N/A'}</strong></div>
              `;

              if (secData.items && secData.items.length) {
                bodyHtml += `
                  <table class="detail-table">
                    <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                    <tbody>
                `;
                secData.items.forEach(it => {
                  bodyHtml += `
                    <tr>
                      <td>${escapeHtml(it.description || '')}</td>
                      <td>${it.quantity ?? 1}</td>
                      <td>${it.unit_price ? '$' + it.unit_price : '-'}</td>
                      <td><strong>${it.total_price ? '$' + it.total_price : '-'}</strong></td>
                    </tr>
                  `;
                });
                bodyHtml += `</tbody></table>`;
              }
            }
            // Deadline renderer
            else if (catKey === 'deadline') {
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Event / Task:</span> <strong class="doc-field__value">${secData.title || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Due Date:</span> <strong class="doc-field__value doc-field__value--urgent">${secData.due_date || 'N/A'} ${secData.due_time ? '(' + secData.due_time + ')' : ''}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Priority:</span> <span class="detail-flag-badge detail-flag-badge--${secData.priority || 'medium'}">${(secData.priority || 'medium').toUpperCase()}</span></div>
                <div class="doc-field"><span class="doc-field__label">Issuer:</span> <span class="doc-field__value">${secData.issuer_or_organization || 'N/A'}</span></div>
                <div style="margin-top:var(--space-2);"><span class="doc-field__label">Action Required:</span> <p style="font-size:var(--text-xs);margin:0;">${escapeHtml(secData.action_required || 'N/A')}</p></div>
              `;
            }
            // Prescription renderer
            else if (catKey === 'prescription') {
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Doctor:</span> <strong class="doc-field__value">${secData.doctor_name || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Clinic / Hospital:</span> <span class="doc-field__value">${secData.clinic_hospital || 'N/A'}</span></div>
                <div class="doc-field"><span class="doc-field__label">Patient:</span> <span class="doc-field__value">${secData.patient_name || 'N/A'}</span></div>
              `;

              if (secData.medications && secData.medications.length) {
                bodyHtml += `<div style="margin-top:var(--space-2);font-weight:700;font-size:var(--text-xs);">Medications List:</div>`;
                secData.medications.forEach(m => {
                  bodyHtml += `
                    <div style="background:var(--color-surface-muted);padding:var(--space-2);border-radius:var(--radius-sm);margin-top:var(--space-1);font-size:var(--text-xs);">
                      <strong>💊 ${escapeHtml(m.name)}</strong> ${m.dosage ? '(' + escapeHtml(m.dosage) + ')' : ''}<br>
                      <span>Frequency: ${escapeHtml(m.frequency || 'N/A')}</span> | <span>Duration: ${escapeHtml(m.duration || 'N/A')}</span><br>
                      ${m.special_instructions ? '<i style="color:var(--color-text-secondary);">' + escapeHtml(m.special_instructions) + '</i>' : ''}
                    </div>
                  `;
                });
              }
            }
            // Lab report renderer
            else if (catKey === 'labreport') {
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Diagnostic Lab:</span> <strong class="doc-field__value">${secData.lab_name || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Report Date:</span> <span class="doc-field__value">${secData.report_date || 'N/A'}</span></div>
              `;

              if (secData.test_results && secData.test_results.length) {
                bodyHtml += `
                  <table class="detail-table">
                    <thead><tr><th>Test Name</th><th>Result</th><th>Unit</th><th>Reference Range</th><th>Status</th></tr></thead>
                    <tbody>
                `;
                secData.test_results.forEach(tr => {
                  const flag = tr.status_flag || 'normal';
                  bodyHtml += `
                    <tr>
                      <td>${escapeHtml(tr.test_name || '')}</td>
                      <td><strong>${escapeHtml(tr.observed_value || '')}</strong></td>
                      <td>${escapeHtml(tr.unit || '')}</td>
                      <td>${escapeHtml(tr.reference_range || '')}</td>
                      <td><span class="detail-flag-badge detail-flag-badge--${flag}">${flag.toUpperCase()}</span></td>
                    </tr>
                  `;
                });
                bodyHtml += `</tbody></table>`;
              }
            }
            // Plan renderer
            else if (catKey === 'plan') {
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Plan Title:</span> <strong class="doc-field__value">${secData.plan_title || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Date:</span> <span class="doc-field__value">${secData.date || 'N/A'}</span></div>
              `;

              if (secData.action_items && secData.action_items.length) {
                bodyHtml += `<div style="margin-top:var(--space-2);font-weight:700;font-size:var(--text-xs);">Action Items Checklist:</div>`;
                secData.action_items.forEach(ai => {
                  const done = ai.status === 'completed';
                  bodyHtml += `
                    <div style="display:flex;align-items:center;gap:var(--space-2);padding-block:var(--space-1);font-size:var(--text-xs);border-bottom:1px dashed var(--color-border);">
                      <span>${done ? '✅' : '⏳'}</span>
                      <strong style="${done ? 'text-decoration:line-through;color:var(--color-text-muted);' : ''}">${ai.step_number}. ${escapeHtml(ai.task)}</strong>
                      ${ai.assigned_to ? '<span style="margin-left:auto;color:var(--color-text-muted);">[' + escapeHtml(ai.assigned_to) + ']</span>' : ''}
                    </div>
                  `;
                });
              }

              if (secData.notes) {
                bodyHtml += `<div style="margin-top:var(--space-2);font-size:var(--text-xs);"><strong>Notes:</strong> ${escapeHtml(secData.notes)}</div>`;
              }
            }

            secEl.innerHTML = bodyHtml;
            detailFieldsContainer.appendChild(secEl);
          }
        });

        // Fallback for unformatted/flat JSON objects
        if (!renderedSections) {
          for (const [key, val] of Object.entries(ext)) {
            if (val === null || val === undefined || val === '') continue;
            const itemEl = document.createElement('div');
            itemEl.className = 'doc-detail-item';
            const formattedKey = key.replace(/_/g, ' ').toUpperCase();
            const formattedVal = typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val);
            itemEl.innerHTML = `
              <span class="doc-detail-item__label">${formattedKey}</span>
              <span class="doc-detail-item__value">${escapeHtml(formattedVal)}</span>
            `;
            detailFieldsContainer.appendChild(itemEl);
          }
        }
      }
    }

    docDetailModal.style.display = 'flex';
    docDetailModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function closeDocumentDetail() {
    if (!docDetailModal) return;
    docDetailModal.style.display = 'none';
    docDetailModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  if (closeDocDetailBtn) {
    closeDocDetailBtn.addEventListener('click', (e) => {
      e.preventDefault();
      closeDocumentDetail();
    });
  }

  if (docDetailModal) {
    docDetailModal.addEventListener('click', (e) => {
      if (e.target === docDetailModal) {
        closeDocumentDetail();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && docDetailModal && docDetailModal.classList.contains('is-open')) {
      closeDocumentDetail();
    }
  });
});
