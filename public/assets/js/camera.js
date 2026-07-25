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
                Stored at: <code>${data.upload.file_path}</code>
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


  // If on standalone scan.php page without modal, init camera directly
  if (!scanModal && cameraContainer) {
    initCamera();
  }

  // --- DASHBOARD CATEGORY TAB FILTERING ---
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
        const cardType = card.dataset.docType;
        if (filter === 'all' || cardType === filter) {
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
  const detailDocBadge = document.getElementById('detailDocBadge');
  const detailDocStatus = document.getElementById('detailDocStatus');
  const detailDocDate = document.getElementById('detailDocDate');
  const detailDocHeading = document.getElementById('detailDocHeading');
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

    // Status badge
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

    // Doc Type Badge & Icon
    if (detailDocBadge) {
      let icon = '📄';
      let label = 'General Document';
      let typeClass = 'doc-type--general';

      if (doc.doc_type === 'bill') {
        icon = '⚡';
        label = 'Bill / Deadline';
        typeClass = 'doc-type--bill';
      } else if (doc.doc_type === 'prescription') {
        icon = '💊';
        label = 'Health Record';
        typeClass = 'doc-type--health';
      } else if (doc.doc_type === 'handwritten_note') {
        icon = '✏️';
        label = 'Handwritten Note';
        typeClass = 'doc-type--note';
      }

      detailDocBadge.className = `doc-card__badge ${typeClass}`;
      detailDocBadge.innerHTML = `<span class="doc-card__badge-icon">${icon}</span> ${label}`;
    }

    // Title & Fields
    const ext = doc.extracted || {};
    let title = doc.filename || 'Document';

    if (doc.doc_type === 'bill') {
      const vendor = ext.biller_name || ext.vendor_name || ext.payee || ext.vendor;
      const amt = ext.amount_due || ext.total_amount || ext.amount;
      if (vendor) title = `${vendor}${amt ? ' (' + amt + ')' : ''}`;
    } else if (doc.doc_type === 'prescription') {
      const med = ext.medication_name || ext.medication || ext.drug;
      const docName = ext.doctor_name || ext.prescriber || ext.provider;
      if (med) title = `${med}${docName ? ' (by ' + docName + ')' : ''}`;
    } else if (doc.doc_type === 'handwritten_note') {
      if (ext.title || ext.heading) title = ext.title || ext.heading;
    } else if (ext.title || ext.document_title) {
      title = ext.title || ext.document_title;
    }

    if (detailDocHeading) detailDocHeading.textContent = title;

    // Render extracted fields
    if (detailFieldsContainer) {
      detailFieldsContainer.innerHTML = '';

      if (Object.keys(ext).length === 0) {
        detailFieldsContainer.innerHTML = '<p class="doc-card__text-preview">No extracted AI data available yet.</p>';
      } else {
        for (const [key, val] of Object.entries(ext)) {
          if (val === null || val === undefined || val === '') continue;

          const itemEl = document.createElement('div');
          itemEl.className = 'doc-detail-item';

          const formattedKey = key.replace(/_/g, ' ').toUpperCase();
          const formattedVal = typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val);

          itemEl.innerHTML = `
            <span class="doc-detail-item__label">${formattedKey}</span>
            <span class="doc-detail-item__value">${formattedVal}</span>
          `;
          detailFieldsContainer.appendChild(itemEl);
        }
      }
    }

    docDetailModal.style.display = 'flex';
    docDetailModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
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

