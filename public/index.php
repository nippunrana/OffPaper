<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page_title = 'Get your life off paper';
$page_nav = 'home';
$page_desc = 'Snap a photo of any paper — a bill, a prescription, a handwritten note — and OffPaper turns it into a reminder, a record, or editable text.';
$page_css = ['landing.css'];
$body_class = 'page-landing';

require VIEWS . '/header.php';
?>

<section class="hero">
  <div class="container hero__grid">
    <div class="hero__copy">
      <p class="eyebrow">For every bill, prescription, and sticky note</p>
      <h1>Snap it. OffPaper remembers, so you don't have to.</h1>
      <p class="hero__subhead">
        Photograph any paper — a bill, a lab report, a note scribbled at 11pm —
        and OffPaper reads it, files it, and reminds you before it's late.
      </p>
      <div class="hero__actions">
        <a class="btn btn--primary btn--lg" href="<?= url('/login.php?mode=signup') ?>">Get started free</a>
        <p class="hero__fud">No credit card. About 30 seconds to start.</p>
      </div>
    </div>

    <div class="hero__visual" aria-hidden="true">
      <div class="paper-card">
        <span class="paper-card__line paper-card__line--title"></span>
        <span class="paper-card__line"></span>
        <span class="paper-card__line"></span>
        <span class="paper-card__line paper-card__line--short"></span>
        <span class="paper-card__stamp">PAID?</span>
      </div>
      <div class="digital-card">
        <p class="digital-card__label">Electricity — City Power Co.</p>
        <p class="digital-card__amount tabular-nums">$84.20</p>
        <p class="digital-card__due tabular-nums">Due Nov 12</p>
        <span class="badge badge--due-soon">Reminder set</span>
      </div>
    </div>
  </div>
</section>

<section class="pain">
  <div class="container">
    <h2>The paper pile that runs your life</h2>
    <div class="pain__grid">
      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">⚡</span>
        <h3>The bill under the fruit bowl</h3>
        <p>Due four days ago. You find it when the late fee notice arrives.</p>
      </article>
      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">💊</span>
        <h3>The prescription you can't find</h3>
        <p>Dad needs a refill and the dosage was written on a slip that's gone missing.</p>
      </article>
      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">✎</span>
        <h3>The plan you scribbled last night</h3>
        <p>Three good ideas, now illegible, now forgotten in a notebook.</p>
      </article>
    </div>
    <p class="pain__cost">
      Late fees stack up. Refills run out at the worst time. And the plan you
      wrote by hand never leaves the page it was written on.
    </p>
  </div>
</section>

<section class="how">
  <div class="container">
    <h2>How it works</h2>
    <ol class="how__steps">
      <li class="how__step">
        <span class="how__number tabular-nums">1</span>
        <h3>Snap</h3>
        <p>Photograph any paper with your phone's camera. No scanner, no app to learn.</p>
      </li>
      <li class="how__step">
        <span class="how__number tabular-nums">2</span>
        <h3>Understand</h3>
        <p>OffPaper reads it — the amount, the due date, the diagnosis, the handwriting — and works out what kind of paper it is.</p>
      </li>
      <li class="how__step">
        <span class="how__number tabular-nums">3</span>
        <h3>Act</h3>
        <p>It becomes a reminder on your timeline, a searchable health record, or editable text you can keep working on.</p>
      </li>
    </ol>
  </div>
</section>

<section class="outcomes">
  <div class="container">
    <div class="outcomes__row">
      <div class="outcomes__copy">
        <span class="eyebrow">Bills &amp; deadlines</span>
        <h3>Never pay a late fee for forgetting.</h3>
        <p>OffPaper reads the amount and due date straight off the bill and puts it on your timeline, with a reminder before it's due — so a bill under the fruit bowl never becomes a bill you paid twice.</p>
      </div>
      <div class="outcomes__visual">
        <div class="mini-timeline">
          <div class="mini-timeline__item">
            <span class="badge badge--overdue">Overdue</span>
            <span>Internet — $52.00</span>
          </div>
          <div class="mini-timeline__item">
            <span class="badge badge--due-soon">Due in 3 days</span>
            <span>Electricity — $84.20</span>
          </div>
          <div class="mini-timeline__item">
            <span class="badge badge--done">Paid</span>
            <span>Rent — $1,450.00</span>
          </div>
        </div>
      </div>
    </div>

    <div class="outcomes__row outcomes__row--reverse">
      <div class="outcomes__copy">
        <span class="eyebrow">Health records</span>
        <h3>Every prescription, exactly where you left it.</h3>
        <p>A prescription or lab report becomes a clean digital record you can actually find again — not a folded slip in a drawer — so refilling a medication doesn't depend on anyone's memory.</p>
      </div>
      <div class="outcomes__visual">
        <div class="mini-record">
          <p class="mini-record__type">Prescription</p>
          <p class="mini-record__title">Amoxicillin 500mg</p>
          <p class="mini-record__meta tabular-nums">Refill by Nov 30 · Dr. Patel</p>
        </div>
      </div>
    </div>

    <div class="outcomes__row">
      <div class="outcomes__copy">
        <span class="eyebrow">Handwritten notes</span>
        <h3>The plan you scribbled, now editable.</h3>
        <p>OffPaper converts your handwriting into real text, so the plan you wrote at 11pm keeps growing instead of dying on the page.</p>
      </div>
      <div class="outcomes__visual">
        <div class="mini-note">
          <p class="mini-note__before">— call plumber<br>— book flights???<br>— ask abt refund</p>
          <p class="mini-note__after">Call plumber · Book flights · Ask about refund</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="demo">
  <div class="container demo__inner">
    <span class="demo__number tabular-nums" aria-hidden="true">5</span>
    <h2>Five seconds. That's the whole demo.</h2>
    <p>Hand someone a paper bill. They snap it. Before they've set the phone down, the amount and due date are already on their timeline. That's an hour of life admin, done in five seconds.</p>
  </div>
</section>

<section class="faq">
  <div class="container">
    <h2>Before you start</h2>
    <div class="faq__list">
      <details class="faq__item">
        <summary>What if OffPaper reads something wrong?</summary>
        <p>You'll always see exactly what it extracted and can fix it in one tap before anything is saved.</p>
      </details>
      <details class="faq__item">
        <summary>Is my data private?</summary>
        <p>Your documents are yours. We don't sell your data, and only Google's document AI ever sees the photo you take — never a third-party ad network.</p>
      </details>
      <details class="faq__item">
        <summary>Do I need a scanner or a special app?</summary>
        <p>No — just your phone's camera. If you can take a photo, you can use OffPaper.</p>
      </details>
    </div>
  </div>
</section>

<section class="final-cta">
  <div class="container final-cta__inner">
    <h2>Ready to get your life off paper?</h2>
    <p>No credit card required to get started.</p>
    <a class="btn btn--primary btn--lg" href="<?= url('/login.php?mode=signup') ?>">Get started free</a>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
