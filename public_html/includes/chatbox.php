<?php
$phone    = setting('phone');
$telHref  = preg_replace('/[^0-9+]/', '', $phone);
$wa       = preg_replace('/[^0-9]/', '', setting('whatsapp'));
$email    = setting('email');
?>
<div class="helpline" id="helpline">
  <button class="helpline-fab" id="helpline-open" type="button" aria-expanded="false" aria-controls="helpline-panel">
    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M4 3h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 1-2Z"/></svg>
    <span>Helpline</span>
  </button>
  <section class="helpline-panel" id="helpline-panel" hidden aria-label="Helpline chat">
    <header>
      <strong>How can we help?</strong>
      <button type="button" class="icon-btn" id="helpline-close" aria-label="Close helpline">&times;</button>
    </header>
    <div class="helpline-body">
      <p class="muted"><?= e(setting('helpline_note')) ?></p>
      <ul class="plain helpline-contacts">
        <?php if ($phone !== ''): ?><li>📞 <a href="tel:<?= e($telHref) ?>"><?= e($phone) ?></a></li><?php endif; ?>
        <?php if ($wa !== ''): ?><li>💬 <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a></li><?php endif; ?>
        <?php if ($email !== ''): ?><li>✉️ <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
        <?php if (setting('hours') !== ''): ?><li>🕘 <?= e(setting('hours')) ?></li><?php endif; ?>
      </ul>
      <form id="helpline-form" action="<?= e(url('chat_send.php')) ?>" method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <label>Your name <input type="text" name="name" required maxlength="100"></label>
        <label>Phone number <input type="tel" name="phone" required maxlength="20" inputmode="tel"></label>
        <label>Message <textarea name="message" rows="3" required maxlength="1000"></textarea></label>
        <button class="btn btn-gold btn-block" type="submit">Send message</button>
        <p class="helpline-status" id="helpline-status" role="status"></p>
      </form>
    </div>
  </section>
</div>
