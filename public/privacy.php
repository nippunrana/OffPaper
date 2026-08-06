<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page_title = 'Privacy Policy';
$page_desc = 'How EarlySnap collects, uses, and protects your data, including your Google Account and Google Calendar information.';
$page_nav = '';
$page_css = ['legal.css'];
$body_class = 'page-legal';

require VIEWS . '/header.php';
?>

<section class="legal">
  <div class="container legal__container">
    <h1>Privacy Policy</h1>
    <p class="legal__updated">Last updated: August 6, 2026</p>

    <p>This Privacy Policy explains what information EarlySnap ("we," "us") collects when you use the EarlySnap application, how we use it, and the choices you have — including the information we access through your Google Account.</p>

    <h2>Information we collect</h2>
    <p>When you create an account with an email and password, we store your email address and a hashed password.</p>
    <p>When you sign in with Google, we receive and store your Google account email address, your profile name, and your Google account identifier (the "sub" claim). We do not receive your Google password.</p>
    <p>When you upload a document (a bill, prescription, lab report, or handwritten note), we store the file and process its contents to classify it and extract structured information such as dates, amounts, and deadlines.</p>

    <h2>Google Calendar access</h2>
    <p>If you choose to sync a deadline from a document to Google Calendar, EarlySnap requests permission to create and delete events on the <strong>primary calendar</strong> of your Google account (the <code>calendar.events</code> scope). Specifically:</p>
    <ul>
      <li>We create a calendar event only when you explicitly choose to sync a detected deadline.</li>
      <li>We delete an event we created if you delete the corresponding document in EarlySnap.</li>
      <li>We do not read your existing calendar events, calendar list, calendar settings, or sharing permissions, and we never access any calendar other than your primary calendar.</li>
    </ul>
    <p>To enable this without asking you to sign in again each time, Google issues an access token and a refresh token, which we store so we can create or remove events on your behalf. You can revoke this access at any time — see "Your choices" below.</p>

    <h2>How we use your information</h2>
    <ul>
      <li>To create and authenticate your account.</li>
      <li>To process documents you upload — classifying them and extracting structured data — using the Google Gemini API.</li>
      <li>To power AI chat about your documents, including optional voice input transcribed via the Gemini API.</li>
      <li>To create or remove Google Calendar events when you request a deadline sync.</li>
    </ul>

    <h2>Third-party services</h2>
    <p>We use the following third-party services to operate EarlySnap:</p>
    <ul>
      <li><strong>Google Sign-In and Google Calendar API</strong> — for authentication and calendar sync, as described above.</li>
      <li><strong>Google Gemini API</strong> — the contents of documents you upload, and any chat messages or voice input you send, are transmitted to Google's Gemini API for classification, data extraction, and conversational responses.</li>
    </ul>
    <p>We do not sell your data, and we do not share it with any other third party.</p>

    <h2>Google user data and Limited Use</h2>
    <p>EarlySnap's use and transfer of information received from Google APIs adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Google API Services User Data Policy</a>, including the Limited Use requirements. In particular, we do not use Google user data for advertising, we do not sell Google user data or transfer it to data brokers, and we do not use Google user data to train generalized artificial-intelligence or machine-learning models.</p>

    <h2>Data storage and retention</h2>
    <p>Your account information, uploaded documents, and Google tokens are stored in our database for as long as your account remains active. If you delete your account, your stored data — including any Google access and refresh tokens — is deleted.</p>

    <h2>Your choices</h2>
    <ul>
      <li>You can revoke EarlySnap's access to your Google Account at any time from <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">Google Account permissions</a>.</li>
      <li>You can delete individual documents from your EarlySnap dashboard, which also removes any calendar event we created for that document.</li>
      <li>You can request deletion of your account and all associated data by contacting us at the address below.</li>
    </ul>

    <h2>Security</h2>
    <p>We use industry-standard measures, including encrypted connections (HTTPS) and hashed passwords, to protect your information. No method of transmission or storage is completely secure, but we work to protect your data at every step.</p>

    <h2>Changes to this policy</h2>
    <p>We may update this Privacy Policy from time to time. Material changes will be reflected by updating the "Last updated" date above.</p>

    <h2>Contact us</h2>
    <p>Questions about this policy or requests to access or delete your data can be sent to <a href="mailto:privacy@earlysnap.com">privacy@earlysnap.com</a>.</p>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
