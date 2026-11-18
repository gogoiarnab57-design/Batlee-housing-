<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Helpline messages', 'messages');

if (is_post()) {
    require_post();
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'read')   q('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]);
    if ($action === 'unread') q('UPDATE messages SET is_read = 0 WHERE id = ?', [$id]);
    if ($action === 'delete') { q('DELETE FROM messages WHERE id = ?', [$id]); flash('Message deleted.'); }
    if ($action === 'allread') { q('UPDATE messages SET is_read = 1'); flash('All messages marked as read.'); }
    redirect(url('admin/messages.php'));
}

$rows = q_all('SELECT * FROM messages ORDER BY id DESC LIMIT 300');
admin_head();
?>
<form method="post" class="toolbar"><?= csrf_field() ?><input type="hidden" name="action" value="allread"><button class="btn btn-ghost" type="submit">Mark all as read</button></form>
<div class="table-wrap"><table class="table">
  <thead><tr><th>From</th><th>Message</th><th>Received</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $m): ?>
    <tr class="<?= (int) $m['is_read'] ? '' : 'unread' ?>">
      <td><?= e($m['name']) ?><br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $m['phone'])) ?>"><?= e($m['phone']) ?></a></td>
      <td><?= nl2br(e($m['message'])) ?></td>
      <td><?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?></td>
      <td><div class="inline-actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="<?= (int) $m['is_read'] ? 'unread' : 'read' ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= (int) $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
        <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </div></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="muted">No messages yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
