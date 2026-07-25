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
});
