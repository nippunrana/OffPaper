<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page_title = 'Terms of Service';
$page_desc = 'The terms that govern your use of EarlySnap.';
$page_nav = '';
$page_css = ['legal.css'];
$body_class = 'page-legal';

require VIEWS . '/header.php';
?>

<section class="legal">
  <div class="container legal__container">
    <h1>Terms of Service</h1>
    <p class="legal__updated">Last updated: August 6, 2026</p>

    <p>These Terms of Service ("Terms") govern your use of EarlySnap (the "Service"). By creating an account or using the Service, you agree to these Terms.</p>

    <h2>The service</h2>
    <p>EarlySnap lets you photograph or upload physical documents — bills, prescriptions, lab reports, handwritten notes — and uses AI to classify them, extract structured information, and, at your request, sync detected deadlines to your Google Calendar.</p>

    <h2>Your account</h2>
    <p>You must provide accurate information when creating an account and are responsible for keeping your credentials secure. You are responsible for all activity that occurs under your account.</p>

    <h2>Acceptable use</h2>
    <p>You agree not to use EarlySnap to upload content you do not have the right to upload, to attempt to disrupt or gain unauthorized access to the Service, or to use the Service for any unlawful purpose.</p>

    <h2>Your content</h2>
    <p>You retain ownership of the documents you upload. By uploading a document, you grant EarlySnap a limited license to store and process it — including sending it to third-party AI services — solely to provide the Service to you, as described in our <a href="<?= url('/privacy.php') ?>">Privacy Policy</a>.</p>

    <h2>Google Account and Calendar integration</h2>
    <p>If you sign in with Google or sync a deadline to Google Calendar, that integration is subject to your agreement with Google as well as this Service's <a href="<?= url('/privacy.php') ?>">Privacy Policy</a>, which describes exactly what Google data we access and how.</p>

    <h2>AI-generated content</h2>
    <p>EarlySnap uses AI to classify documents, extract data, and answer questions about them. AI output can be incomplete or inaccurate. EarlySnap does not provide medical, legal, or financial advice, and you should independently verify any extracted date, amount, or other detail — especially before relying on it for a deadline or payment.</p>

    <h2>Termination</h2>
    <p>You may stop using the Service and delete your account at any time. We may suspend or terminate accounts that violate these Terms.</p>

    <h2>Disclaimer of warranties</h2>
    <p>The Service is provided "as is," without warranties of any kind, whether express or implied.</p>

    <h2>Limitation of liability</h2>
    <p>To the maximum extent permitted by law, EarlySnap is not liable for any indirect, incidental, or consequential damages arising from your use of the Service.</p>

    <h2>Changes to these terms</h2>
    <p>We may update these Terms from time to time. Continued use of the Service after a change constitutes acceptance of the updated Terms.</p>

    <h2>Contact us</h2>
    <p>Questions about these Terms can be sent to <a href="mailto:privacy@earlysnap.com">privacy@earlysnap.com</a>.</p>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
