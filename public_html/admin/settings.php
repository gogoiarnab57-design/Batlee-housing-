<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Site & contact settings', 'settings');

// key => [label, type, max length, help]
$fields = [
    'Shop'            => [
        'site_name'  => ['Shop name', 'text', 80, ''],
        'tagline'    => ['Tagline (footer & search engines)', 'text', 200, ''],
        'hero_title' => ['Home page headline', 'text', 100, ''],
        'hero_text'  => ['Home page intro text', 'textarea', 300, ''],
    ],
    'Contact details & helpline' => [
        'phone'         => ['Helpline phone number', 'text', 25, 'Shown in the footer and the Helpline box.'],
        'whatsapp'      => ['WhatsApp number (with country code)', 'text', 20, 'Example: 919876543210. Leave empty to hide the WhatsApp link.'],
        'email'         => ['Contact email', 'text', 120, ''],
        'address'       => ['Shop address', 'textarea', 300, ''],
        'hours'         => ['Working hours', 'text', 120, ''],
        'helpline_note' => ['Message shown at the top of the Helpline box', 'textarea', 300, ''],
    ],
    'Delivery' => [
        'shipping_charge'     => ['Delivery charge (₹)', 'number', 10, 'Use 0 for free delivery on every order.'],
        'free_shipping_above' => ['Free delivery above (₹)', 'number', 10, 'Use 0 to switch this off.'],
    ],
    'Payment options' => [
        'upi_id' => ['Your UPI ID (shown to customers who pick UPI)', 'text', 60, ''],
    ],
];
$toggles = ['pay_cod' => 'Cash on Delivery', 'pay_upi' => 'UPI', 'pay_card' => 'Credit / Debit Card', 'pay_netbanking' => 'Net Banking'];

if (is_post()) {
    require_post();
    $errors = [];
    foreach ($fields as $group) {
        foreach ($group as $key => [$label, $type, $max]) {
            $val = mb_substr(post($key), 0, $max);
            if ($type === 'number') {
                if ($val === '') { $val = '0'; }
                if (!is_numeric($val) || (float) $val < 0) { $errors[] = $label . ' must be a number (0 or more).'; continue; }
            }
            if ($key === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid contact email.'; continue; }
            if ($key === 'whatsapp') { $val = preg_replace('/[^0-9]/', '', $val); }
            if ($key === 'site_name' && $val === '') { $errors[] = 'Shop name cannot be empty.'; continue; }
            setting_save($key, (string) $val);
        }
    }
    foreach ($toggles as $key => $_) {
        setting_save($key, post($key) === '1' ? '1' : '0');
    }
    if ($errors) {
        foreach ($errors as $er) flash($er, 'err');
    } else {
        flash('Settings saved.');
    }
    redirect(url('admin/settings.php'));
}

admin_head();
?>
<form method="post" class="form-card" style="max-width:720px">
  <?= csrf_field() ?>
  <?php foreach ($fields as $group => $items): ?>
    <h2><?= e($group) ?></h2>
    <?php foreach ($items as $key => [$label, $type, $max, $help]): ?>
      <label><?= e($label) ?>
        <?php if ($type === 'textarea'): ?>
          <textarea name="<?= e($key) ?>" rows="2" maxlength="<?= (int) $max ?>"><?= e(setting($key)) ?></textarea>
        <?php else: ?>
          <input type="<?= $type === 'number' ? 'number' : 'text' ?>" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>" maxlength="<?= (int) $max ?>"<?= $type === 'number' ? ' min="0" step="0.01"' : '' ?>>
        <?php endif; ?>
        <?php if ($help): ?><span class="muted small"><?= e($help) ?></span><?php endif; ?>
      </label>
    <?php endforeach; ?>
    <?php if ($group === 'Payment options'): ?>
      <p><strong>Show these options on the payment page</strong></p>
      <?php foreach ($toggles as $key => $label): ?>
        <label class="checkline"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= setting($key, '1') === '1' ? 'checked' : '' ?>> <?= e($label) ?></label>
      <?php endforeach; ?>
    <?php endif; ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
  <?php endforeach; ?>
  <button class="btn btn-gold" type="submit">Save settings</button>
</form>
<?php admin_foot(); ?>
