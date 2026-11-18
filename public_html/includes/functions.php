<?php
declare(strict_types=1);

/* ============================================================
 * Paths, session, security headers
 * ============================================================ */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function base_path(): string
{
    if (BASE_URL_OVERRIDE !== '') {
        return rtrim(BASE_URL_OVERRIDE, '/');
    }
    $root = realpath(ROOT_DIR);
    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($root && $doc && strpos($root, $doc) === 0) {
        return rtrim(str_replace('\\', '/', substr($root, strlen($doc))), '/');
    }
    return '';
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_DIR . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    header_remove('X-Powered-By');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('BATLEESESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

send_security_headers();
start_session();

/* ============================================================
 * Output helpers
 * ============================================================ */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Indian digit grouping, e.g. 1234567.5 => 12,34,567.50 (decimals only when needed). */
function money($amount): string
{
    $amount = round((float) $amount, 2);
    $neg = $amount < 0;
    $amount = abs($amount);
    $whole = (string) (int) floor($amount);
    $dec = round($amount - floor($amount), 2);
    if (strlen($whole) > 3) {
        $last3 = substr($whole, -3);
        $rest  = substr($whole, 0, -3);
        $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $whole = $rest . ',' . $last3;
    }
    $out = $whole;
    if ($dec > 0) {
        $out .= '.' . substr(number_format($dec, 2), 2);
    }
    return ($neg ? '-' : '') . '₹' . $out;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Only allow redirects to local paths of this site. */
function safe_next(?string $next, string $fallback): string
{
    if ($next && $next[0] === '/' && strpos($next, '//') !== 0 && strpos($next, '\\') === false && !preg_match('/[\r\n]/', $next)) {
        return $next;
    }
    return $fallback;
}

function flash(?string $message = null, string $type = 'ok')
{
    if ($message !== null) {
        $_SESSION['flash'][] = ['m' => $message, 't' => $type];
        return null;
    }
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

function render_flash(): string
{
    $out = '';
    foreach (flash() ?? [] as $f) {
        $cls = $f['t'] === 'err' ? 'alert alert-err' : 'alert alert-ok';
        $out .= '<div class="' . $cls . '" role="status">' . e($f['m']) . '</div>';
    }
    return $out;
}

/* ============================================================
 * CSRF
 * ============================================================ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/** Call at the top of every POST handler. */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/* ============================================================
 * Settings (editable by admin)
 * ============================================================ */

function settings_all(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q_all('SELECT skey, svalue FROM settings') as $r) {
            $cache[$r['skey']] = $r['svalue'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $s = settings_all();
    return isset($s[$key]) && $s[$key] !== null ? (string) $s[$key] : $default;
}

function setting_save(string $key, string $value): void
{
    $exists = q_val('SELECT COUNT(*) FROM settings WHERE skey = ?', [$key]);
    if ($exists) {
        q('UPDATE settings SET svalue = ? WHERE skey = ?', [$value, $key]);
    } else {
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
    }
}

/* ============================================================
 * Authentication
 * ============================================================ */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q_one('SELECT id, username, name, email, phone, role FROM users WHERE id = ?', [(int) $_SESSION['uid']]);
            if ($user && $user['role'] === 'admin') {
                $last = $_SESSION['last_active'] ?? time();
                if (time() - $last > ADMIN_IDLE_MINUTES * 60) {
                    logout_user();
                    $user = null;
                    flash('You were logged out after a period of inactivity.', 'err');
                }
            }
            if ($user) {
                $_SESSION['last_active'] = time();
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    $_SESSION['last_active'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $keep = $_SESSION['cart'] ?? [];
    $_SESSION = [];
    session_regenerate_id(true);
    // Guests keep nothing; a logged-out customer's cart is cleared for privacy on shared devices.
    unset($keep);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('Please log in to continue.', 'err');
        redirect(url('login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/')));
    }
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        redirect(url('login.php?next=' . rawurlencode('/admin/')));
    }
    return $u;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** True when this IP/username has too many recent failed logins. */
function login_locked(string $ident): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    q('DELETE FROM login_attempts WHERE created_at < ?', [$since]);
    $byIp   = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ip = ?', [client_ip()]);
    $byUser = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ident = ?', [strtolower($ident)]);
    return $byIp >= LOGIN_MAX_ATTEMPTS * 2 || $byUser >= LOGIN_MAX_ATTEMPTS;
}

function login_failed(string $ident): void
{
    q('INSERT INTO login_attempts (ident, ip, created_at) VALUES (?, ?, ?)', [strtolower($ident), client_ip(), date('Y-m-d H:i:s')]);
}

function login_clear(string $ident): void
{
    q('DELETE FROM login_attempts WHERE ident = ?', [strtolower($ident)]);
}

/* ============================================================
 * Validation
 * ============================================================ */

function valid_phone(string $p): bool
{
    return (bool) preg_match('/^\+?[0-9][0-9 \-]{6,18}$/', $p);
}

function valid_password(string $p): ?string
{
    if (strlen($p) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (strlen($p) > 200) {
        return 'Password is too long.';
    }
    return null;
}

/* ============================================================
 * Images (uploads are re-encoded with GD, which strips any hidden code)
 * ============================================================ */

function upload_dir(): string
{
    $dir = ROOT_DIR . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function product_image_url(?string $file): string
{
    return $file ? url('uploads/' . rawurlencode($file)) : '';
}

/**
 * Validate and store one uploaded image. Returns [filename|null, error|null].
 */
function save_uploaded_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (error code ' . (int) $file['error'] . ').'];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return [null, 'Image is larger than ' . (MAX_UPLOAD_BYTES / 1048576) . ' MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    if ($info[0] > 8000 || $info[1] > 8000) {
        return [null, 'Image dimensions are too large.'];
    }
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        return [null, 'Could not read that image.'];
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $max = 1200;
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten transparency on white
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $name = bin2hex(random_bytes(12)) . '.jpg';
    $ok = imagejpeg($dst, upload_dir() . '/' . $name, 85);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        return [null, 'Could not save the image.'];
    }
    return [$name, null];
}

function delete_image_file(string $name): void
{
    if (preg_match('/^[a-f0-9]{24}\.jpg$/', $name)) {
        $p = upload_dir() . '/' . $name;
        if (is_file($p)) {
            @unlink($p);
        }
    }
}

/** Re-arrange the $_FILES array of a multi-file input into a list of single files. */
function files_list(string $field): array
{
    $out = [];
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $out;
    }
    foreach ($_FILES[$field]['name'] as $i => $n) {
        $out[] = [
            'name'     => $n,
            'type'     => $_FILES[$field]['type'][$i],
            'tmp_name' => $_FILES[$field]['tmp_name'][$i],
            'error'    => $_FILES[$field]['error'][$i],
            'size'     => $_FILES[$field]['size'][$i],
        ];
    }
    return $out;
}

/* ============================================================
 * Cart - stored in the session as [product_id => qty].
 * Prices are ALWAYS re-read from the database, never trusted from the browser.
 * ============================================================ */

const CART_MAX_QTY = 20;

function cart_ids(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart_ids());
}

function cart_set(int $productId, int $qty): void
{
    $qty = max(0, min(CART_MAX_QTY, $qty));
    if ($qty === 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

/**
 * Cart lines with live prices and the calculated totals.
 * Returns ['lines' => [...], 'subtotal' => x, 'shipping' => x, 'total' => x, 'problems' => [...]]
 */
function cart_summary(): array
{
    $lines = [];
    $problems = [];
    $subtotal = 0.0;
    $cart = cart_ids();
    if ($cart) {
        $ids = array_map('intval', array_keys($cart));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $rows = q_all(
            "SELECT p.id, p.name, p.price, p.mrp, p.stock, p.is_active,
                    (SELECT filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image
             FROM products p WHERE p.id IN ($in)",
            $ids
        );
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
        }
        foreach ($cart as $pid => $qty) {
            $pid = (int) $pid;
            $p = $byId[$pid] ?? null;
            if (!$p || !(int) $p['is_active']) {
                unset($_SESSION['cart'][$pid]);
                $problems[] = 'An item in your cart is no longer available and was removed.';
                continue;
            }
            $stock = (int) $p['stock'];
            if ($stock <= 0) {
                unset($_SESSION['cart'][$pid]);
                $problems[] = '"' . $p['name'] . '" is out of stock and was removed from your cart.';
                continue;
            }
            if ($qty > $stock) {
                $qty = $stock;
                $_SESSION['cart'][$pid] = $qty;
                $problems[] = 'Only ' . $stock . ' of "' . $p['name'] . '" available - quantity adjusted.';
            }
            $price = round((float) $p['price'], 2);
            $line  = round($price * $qty, 2);
            $subtotal += $line;
            $lines[] = [
                'id' => $pid, 'name' => $p['name'], 'price' => $price, 'mrp' => $p['mrp'],
                'qty' => $qty, 'line' => $line, 'stock' => $stock, 'image' => $p['image'],
            ];
        }
    }
    $subtotal = round($subtotal, 2);
    $shipping = 0.0;
    if ($lines) {
        $flat = (float) setting('shipping_charge', '0');
        $free = (float) setting('free_shipping_above', '0');
        $shipping = ($free > 0 && $subtotal >= $free) ? 0.0 : $flat;
    }
    return [
        'lines' => $lines, 'subtotal' => $subtotal, 'shipping' => round($shipping, 2),
        'total' => round($subtotal + $shipping, 2), 'problems' => $problems,
    ];
}

/* ============================================================
 * Misc
 * ============================================================ */

function stars(float $rating): string
{
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

function order_status_label(string $s): string
{
    return [
        'new' => 'New', 'confirmed' => 'Confirmed', 'packed' => 'Packed',
        'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
    ][$s] ?? $s;
}

function payment_method_label(string $m): string
{
    return [
        'cod' => 'Cash on Delivery', 'upi' => 'UPI', 'card' => 'Credit / Debit Card', 'netbanking' => 'Net Banking',
    ][$m] ?? $m;
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s === '' ? 'item' : $s;
}

function maps_link($lat, $lng): string
{
    return 'https://www.google.com/maps?q=' . rawurlencode((string) $lat) . ',' . rawurlencode((string) $lng);
}
