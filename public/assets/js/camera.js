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
  const detailDocChatBtn = document.getElementById('detailDocChatBtn');
  const detailAddToCalendarBtn = document.getElementById('detailAddToCalendarBtn');
  const detailViewCalendarLink = document.getElementById('detailViewCalendarLink');

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

  function checkDeadlinePassed(dueDateStr, dueTimeStr) {
    if (!dueDateStr) return false;
    let dateTimeStr = String(dueDateStr).trim();
    if (!dateTimeStr) return false;

    if (dueTimeStr && String(dueTimeStr).trim()) {
      dateTimeStr += ' ' + String(dueTimeStr).trim();
    } else {
      dateTimeStr += ' 23:59:59';
    }

    const dueTime = new Date(dateTimeStr).getTime();
    if (isNaN(dueTime)) {
      const parts = String(dueDateStr).trim().split('-');
      if (parts.length === 3) {
        const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), 23, 59, 59);
        return d.getTime() < Date.now();
      }
      return false;
    }
    return dueTime < Date.now();
  }

  function openDocumentDetail(doc) {
    if (!docDetailModal) return;

    if (detailDocImage) detailDocImage.src = doc.file_path || '';
    if (detailDocDate) detailDocDate.textContent = doc.created_at || '';
    if (detailDownloadLink) detailDownloadLink.href = doc.file_path || '#';
    if (detailDocChatBtn) detailDocChatBtn.setAttribute('data-open-doc-chat', JSON.stringify(doc));
    const detailDeleteBtn = document.getElementById('detailDeleteDocBtn');
    if (detailDeleteBtn) detailDeleteBtn.setAttribute('data-doc-id', doc.id);

    const categories = doc.categories || [doc.doc_type || 'plan'];
    const isPlanDoc = categories.includes('plan');

    // AI Plan Notes block handling (shown only for plan category docs with a saved summary)
    const detailPlanNotesBlock = document.getElementById('detailPlanNotesBlock');
    const detailPlanNotesText = document.getElementById('detailPlanNotesText');
    if (detailPlanNotesBlock && detailPlanNotesText) {
      if (isPlanDoc && doc.plan_summary) {
        detailPlanNotesText.textContent = doc.plan_summary;
        detailPlanNotesBlock.style.display = 'block';
      } else {
        detailPlanNotesBlock.style.display = 'none';
      }
    }

    // Calendar sync button in detail modal footer
    const hasDeadline = doc.extracted && doc.extracted.deadline;
    const deadlineData = hasDeadline ? doc.extracted.deadline : null;
    const isDeadlinePast = deadlineData ? checkDeadlinePassed(deadlineData.due_date, deadlineData.due_time) : false;
    const calendarLink = hasDeadline ? deadlineData.calendar_html_link : null;

    let pastNotice = document.getElementById('detailPastDueNotice');
    if (pastNotice) pastNotice.remove();

    if (detailAddToCalendarBtn && detailViewCalendarLink) {
      if (hasDeadline) {
        if (isDeadlinePast) {
          detailAddToCalendarBtn.style.display = 'none';
          detailViewCalendarLink.style.display = 'none';

          const noticeEl = document.createElement('span');
          noticeEl.id = 'detailPastDueNotice';
          noticeEl.className = 'btn btn--sm';
          noticeEl.style.cssText = 'cursor: default; background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.3); font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;';
          noticeEl.innerHTML = '⚠️ Due date already passed';
          detailAddToCalendarBtn.parentNode.appendChild(noticeEl);
        } else if (calendarLink) {
          detailAddToCalendarBtn.style.display = 'none';
          detailViewCalendarLink.style.display = 'inline-flex';
          detailViewCalendarLink.href = calendarLink;
        } else {
          detailAddToCalendarBtn.style.display = 'inline-flex';
          detailAddToCalendarBtn.setAttribute('data-doc-id', doc.id);
          detailAddToCalendarBtn.disabled = false;
          detailAddToCalendarBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
            Add to Google Calendar
          `;
          detailViewCalendarLink.style.display = 'none';
        }
      } else {
        detailAddToCalendarBtn.style.display = 'none';
        detailViewCalendarLink.style.display = 'none';
      }
    }

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
      const currStr = ext.bills.currency ? ext.bills.currency.trim() + ' ' : '';
      title = ext.bills.vendor_name + (ext.bills.grand_total ? ` (${currStr}${ext.bills.grand_total})` : '');
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
              const currStr = secData.currency ? escapeHtml(secData.currency.trim()) + ' ' : '';
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Vendor:</span> <strong class="doc-field__value">${secData.vendor_name || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Bill Date:</span> <span class="doc-field__value">${secData.bill_date || 'N/A'}</span></div>
                <div class="doc-field"><span class="doc-field__label">Invoice #:</span> <span class="doc-field__value">${secData.invoice_number || 'N/A'}</span></div>
                <div class="doc-field"><span class="doc-field__label">Grand Total:</span> <strong class="doc-field__value" style="color:#059669;">${currStr}${secData.grand_total || '0.00'}</strong></div>
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
                      <td>${it.unit_price ? currStr + it.unit_price : '-'}</td>
                      <td><strong>${it.total_price ? currStr + it.total_price : '-'}</strong></td>
                    </tr>
                  `;
                });
                bodyHtml += `</tbody></table>`;
              }
            }
            // Deadline renderer
            else if (catKey === 'deadline') {
              const isPast = checkDeadlinePassed(secData.due_date, secData.due_time);
              bodyHtml += `
                <div class="doc-field"><span class="doc-field__label">Event / Task:</span> <strong class="doc-field__value">${secData.title || 'N/A'}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Due Date:</span> <strong class="doc-field__value ${isPast ? '' : 'doc-field__value--urgent'}" style="${isPast ? 'color:#dc2626;' : ''}">${secData.due_date || 'N/A'} ${secData.due_time ? '(' + secData.due_time + ')' : ''} ${isPast ? '<span style="font-size:0.75rem;font-weight:normal;background:rgba(239,68,68,0.15);padding:2px 6px;border-radius:4px;color:#dc2626;">(Passed)</span>' : ''}</strong></div>
                <div class="doc-field"><span class="doc-field__label">Priority:</span> <span class="detail-flag-badge detail-flag-badge--${secData.priority || 'medium'}">${(secData.priority || 'medium').toUpperCase()}</span></div>
                <div class="doc-field"><span class="doc-field__label">Issuer:</span> <span class="doc-field__value">${secData.issuer_or_organization || 'N/A'}</span></div>
                <div style="margin-top:var(--space-2);"><span class="doc-field__label">Action Required:</span> <p style="font-size:var(--text-xs);margin:0;">${escapeHtml(secData.action_required || 'N/A')}</p></div>
              `;

              if (isPast) {
                bodyHtml += `
                  <div style="margin-top:var(--space-3); background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 8px 12px; border-radius: var(--radius-sm); font-size: var(--text-xs); font-weight: 600; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(239, 68, 68, 0.2);">
                    ⚠️ Due date already passed
                  </div>
                `;
              } else if (secData.calendar_html_link) {
                bodyHtml += `
                  <div style="margin-top:var(--space-3);">
                    <a href="${escapeHtml(secData.calendar_html_link)}" target="_blank" rel="noopener" class="btn btn--calendar btn--sm btn--calendar-synced">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                      In Google Calendar
                    </a>
                  </div>
                `;
              } else {
                bodyHtml += `
                  <div style="margin-top:var(--space-3);">
                    <button type="button" class="btn btn--calendar btn--sm btn-add-calendar" data-doc-id="${doc.id}">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
                      Add to Google Calendar
                    </button>
                  </div>
                `;
              }
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

  // --- ADD TO GOOGLE CALENDAR ACTION HANDLER ---
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-add-calendar');
    if (!btn) return;
    e.preventDefault();

    const docId = btn.getAttribute('data-doc-id');
    if (!docId) return;

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `⏳ Adding to Calendar...`;

    const targetCalendarUrl = document.body.dataset.calendarUrl || 'api/add_to_calendar.php';

    try {
      const resp = await fetch(targetCalendarUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ document_id: docId })
      });

      const responseText = await resp.text();
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseErr) {
        console.error('Failed to parse calendar response as JSON:', responseText);
        throw new Error(`Server returned HTTP ${resp.status}`);
      }

      if (data.ok) {
        // Replace all matching buttons for this docId on the page with synced link
        const allMatchingBtns = document.querySelectorAll(`.btn-add-calendar[data-doc-id="${docId}"]`);
        allMatchingBtns.forEach(b => {
          const link = document.createElement('a');
          link.href = data.html_link || '#';
          link.target = '_blank';
          link.rel = 'noopener';
          link.className = 'btn btn--calendar btn--sm btn--calendar-synced';
          if (b.style.gridColumn) link.style.gridColumn = b.style.gridColumn;
          link.innerHTML = `
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
            In Google Calendar
          `;
          b.replaceWith(link);
        });

        if (detailAddToCalendarBtn && detailAddToCalendarBtn.getAttribute('data-doc-id') === String(docId)) {
          detailAddToCalendarBtn.style.display = 'none';
          if (detailViewCalendarLink) {
            detailViewCalendarLink.style.display = 'inline-flex';
            detailViewCalendarLink.href = data.html_link || '#';
          }
        }
      } else {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        if (data.auth_url) {
          if (confirm(`${data.error}\n\nWould you like to connect your Google account now?`)) {
            window.location.href = data.auth_url;
          }
        } else {
          alert(data.error || 'Failed to add event to Google Calendar.');
        }
      }
    } catch (err) {
      console.error('Calendar sync error:', err);
      btn.disabled = false;
      btn.innerHTML = originalContent;
      alert('An unexpected error occurred while adding to Google Calendar.');
    }
  });

  // --- DELETE DOCUMENT ACTION HANDLER ---
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-delete-doc');
    if (!btn) return;
    e.preventDefault();

    const docId = btn.getAttribute('data-doc-id');
    if (!docId) return;

    if (!confirm('Are you sure you want to delete this document?\n\nThis will permanently remove the uploaded image, extracted AI records, conversation history, and calendar integration.')) {
      return;
    }

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `⏳ Deleting...`;

    const targetDeleteUrl = document.body.dataset.deleteUrl || 'api/delete_document.php';

    try {
      const resp = await fetch(targetDeleteUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ document_id: docId, csrf: csrfToken })
      });

      const responseText = await resp.text();
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseErr) {
        console.error('Failed to parse delete response as JSON:', responseText);
        throw new Error(`Server returned HTTP ${resp.status}`);
      }

      if (data.ok) {
        // Close document detail modal if open
        closeDocumentDetail();

        // Animate and remove matching card elements from DOM
        const matchingCards = document.querySelectorAll(`.doc-card[data-doc-id="${docId}"]`);
        matchingCards.forEach(card => {
          card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
          card.style.opacity = '0';
          card.style.transform = 'scale(0.95)';
          setTimeout(() => {
            card.remove();
            updateDashboardStatsAfterDeletion();
          }, 250);
        });

        if (matchingCards.length === 0) {
          updateDashboardStatsAfterDeletion();
        }
      } else {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        alert(data.error || 'Failed to delete document.');
      }
    } catch (err) {
      console.error('Document deletion error:', err);
      btn.disabled = false;
      btn.innerHTML = originalContent;
      alert('An unexpected error occurred while deleting the document.');
    }
  });

  function updateDashboardStatsAfterDeletion() {
    const allCards = document.querySelectorAll('.doc-card');
    const totalCount = allCards.length;

    if (totalCount === 0) {
      window.location.reload();
      return;
    }

    const counts = { total: totalCount, bills: 0, deadline: 0, prescription: 0, labreport: 0, plan: 0 };
    allCards.forEach(card => {
      const catsAttr = card.getAttribute('data-categories') || '';
      const cats = catsAttr.split(',').map(s => s.trim()).filter(Boolean);
      cats.forEach(cat => {
        if (counts[cat] !== undefined) counts[cat]++;
      });
    });

    document.querySelectorAll('.dash-tab').forEach(tab => {
      const filter = tab.getAttribute('data-filter');
      const countSpan = tab.querySelector('.dash-tab__count');
      if (filter === 'all') {
        if (countSpan) countSpan.textContent = counts.total;
      } else if (counts[filter] !== undefined) {
        if (countSpan) countSpan.textContent = counts[filter];
        if (counts[filter] === 0) {
          const li = tab.closest('li');
          if (li) li.remove();
        }
      }
    });

    document.querySelectorAll('.dash-stat-card').forEach(statCard => {
      const labelEl = statCard.querySelector('.dash-stat-card__label');
      const valEl = statCard.querySelector('.dash-stat-card__value');
      if (labelEl && valEl) {
        const txt = labelEl.textContent.trim().toLowerCase();
        if (txt === 'total') valEl.textContent = counts.total;
        else if (txt === 'bills') valEl.textContent = counts.bills;
        else if (txt === 'deadlines') valEl.textContent = counts.deadline;
        else if (txt === 'prescriptions') valEl.textContent = counts.prescription;
        else if (txt === 'lab reports') valEl.textContent = counts.labreport;
        else if (txt === 'plans') valEl.textContent = counts.plan;
      }
    });
  }
});
