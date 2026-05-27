<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const DB_FILE = __DIR__ . '/db.sqlite';
const UPLOAD_ROOT = __DIR__ . '/uploads';
const QRCODE_ROOT = UPLOAD_ROOT . '/qrcodes';
const THUMB_ROOT = UPLOAD_ROOT . '/thumbnails';
const MAX_IMAGE_SIZE = 1024 * 1024 * 10; // 10MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function app_name(): string
{
    return get_setting('app_name') ?: '联塑家装管';
}

function service_phone(): string
{
    return get_setting('service_phone') ?: '400-123-4567';
}

function get_setting(string $key): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $db = db_connect();
        $result = $db->query('SELECT key, value FROM settings');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cache[(string) $row['key']] = (string) $row['value'];
        }
        $result->finalize();
    }
    return $cache[$key] ?? null;
}

function save_setting(string $key, string $value): void
{
    $db = db_connect();
    $stmt = $db->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)');
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
    $stmt->execute();
}

function get_all_settings(): array
{
    $db = db_connect();
    $result = $db->query('SELECT key, value FROM settings ORDER BY key');
    $settings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[(string) $row['key']] = (string) $row['value'];
    }
    $result->finalize();
    return $settings;
}

function ensure_runtime_dirs(): void
{
    $directories = [UPLOAD_ROOT, QRCODE_ROOT, THUMB_ROOT];
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
}

function db_connect(): SQLite3
{
    static $db = null;
    if ($db instanceof SQLite3) {
        return $db;
    }

    ensure_runtime_dirs();

    $db = new SQLite3(DB_FILE);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');

    initialize_schema($db);

    return $db;
}

function initialize_schema(SQLite3 $db): void
{
    $db->exec(
        <<<SQL
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS construction_workers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            show_phone INTEGER DEFAULT 1,
            login_password TEXT NOT NULL,
            qr_code TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            worker_id INTEGER NOT NULL,
            customer_phone TEXT NOT NULL,
            address TEXT NOT NULL,
            qr_code TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(worker_id) REFERENCES construction_workers(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS construction_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
        );

        CREATE UNIQUE INDEX IF NOT EXISTS idx_workers_phone_unique
        ON construction_workers(phone);

        CREATE INDEX IF NOT EXISTS idx_customers_worker_id
        ON customers(worker_id);

        CREATE INDEX IF NOT EXISTS idx_customers_phone
        ON customers(customer_phone);

        CREATE INDEX IF NOT EXISTS idx_images_customer_id
        ON construction_images(customer_id);

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
        SQL
    );

    seed_default_admin($db);
    seed_default_worker($db);
}

function seed_default_admin(SQLite3 $db): void
{
    $stmt = $db->prepare('SELECT id FROM admins WHERE username = :username LIMIT 1');
    $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
    $result = $stmt->execute();
    $exists = $result->fetchArray(SQLITE3_ASSOC);
    $result->finalize();

    if ($exists) {
        return;
    }

    $insert = $db->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
    $insert->bindValue(':username', 'admin', SQLITE3_TEXT);
    $insert->bindValue(':password_hash', password_hash('admin123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $insert->execute();
}

function seed_default_worker(SQLite3 $db): void
{
    $stmt = $db->prepare('SELECT id FROM construction_workers WHERE phone = :phone LIMIT 1');
    $stmt->bindValue(':phone', '13800000000', SQLITE3_TEXT);
    $result = $stmt->execute();
    $exists = $result->fetchArray(SQLITE3_ASSOC);
    $result->finalize();

    if ($exists) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO construction_workers (name, phone, show_phone, login_password) VALUES (:name, :phone, :show_phone, :login_password)'
    );
    $insert->bindValue(':name', '联塑测试施工员', SQLITE3_TEXT);
    $insert->bindValue(':phone', '13800000000', SQLITE3_TEXT);
    $insert->bindValue(':show_phone', 1, SQLITE3_INTEGER);
    $insert->bindValue(':login_password', password_hash('123456', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $insert->execute();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_data(): array
{
    $data = $_POST;
    $rawInput = file_get_contents('php://input');
    if (
        empty($data)
        && !empty($rawInput)
        && str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')
    ) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    if (isset($_GET['action']) && !isset($data['action'])) {
        $data['action'] = $_GET['action'];
    }

    return $data;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success(string $message = '', array $data = []): never
{
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}

function json_error(string $message, int $status = 400, array $data = []): never
{
    json_response([
        'success' => false,
        'message' => $message,
        'data' => $data,
    ], $status);
}

function fetch_all_rows(SQLite3Result $result): array
{
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    $result->finalize();
    return $rows;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        json_error('请先登录管理员账号。', 401);
    }
}

function is_worker_logged_in(): bool
{
    return !empty($_SESSION['worker_logged_in']) && !empty($_SESSION['worker_id']);
}

function require_worker(): int
{
    if (!is_worker_logged_in()) {
        json_error('请先登录施工员账号。', 401);
    }

    return (int) $_SESSION['worker_id'];
}

function current_worker(): ?array
{
    if (!is_worker_logged_in()) {
        return null;
    }
    return sanitize_worker(get_worker_by_id((int) $_SESSION['worker_id']));
}

function base_url(): string
{
    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

    return $scheme . '://' . $host . $scriptDir;
}

function customer_user_url(int $customerId): string
{
    return base_url() . '/user.php?customer_id=' . $customerId;
}

function public_path(string $relativePath): string
{
    return __DIR__ . '/' . ltrim($relativePath, '/');
}

function customer_upload_dir(int $customerId): string
{
    return UPLOAD_ROOT . '/customer_' . $customerId;
}

function customer_thumbnail_dir(int $customerId): string
{
    return THUMB_ROOT . '/customer_' . $customerId;
}

function relative_customer_upload_dir(int $customerId): string
{
    return 'uploads/customer_' . $customerId;
}

function relative_customer_thumbnail_dir(int $customerId): string
{
    return 'uploads/thumbnails/customer_' . $customerId;
}

function normalize_flag(mixed $value): int
{
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
}

function get_worker_by_id(int $workerId): ?array
{
    $db = db_connect();
    $stmt = $db->prepare('SELECT * FROM construction_workers WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $worker = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    $result->finalize();
    return $worker;
}

function sanitize_worker(?array $worker): ?array
{
    if (!$worker) {
        return null;
    }

    unset($worker['login_password']);
    return $worker;
}

function get_customer_by_id(int $customerId): ?array
{
    $db = db_connect();
    $stmt = $db->prepare(
        'SELECT c.*, w.name AS worker_name, w.phone AS worker_phone, w.show_phone
         FROM customers c
         INNER JOIN construction_workers w ON w.id = c.worker_id
         WHERE c.id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $customer = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    $result->finalize();
    return $customer;
}

function get_image_by_id(int $imageId): ?array
{
    $db = db_connect();
    $stmt = $db->prepare(
        'SELECT ci.*, c.worker_id, c.customer_phone, c.address
         FROM construction_images ci
         INNER JOIN customers c ON c.id = ci.customer_id
         WHERE ci.id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $image = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    $result->finalize();
    return $image;
}

function worker_owns_customer(int $workerId, int $customerId): bool
{
    $db = db_connect();
    $stmt = $db->prepare('SELECT id FROM customers WHERE id = :id AND worker_id = :worker_id LIMIT 1');
    $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
    $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $owned = (bool) $result->fetchArray(SQLITE3_ASSOC);
    $result->finalize();
    return $owned;
}

function worker_owns_image(int $workerId, int $imageId): bool
{
    $db = db_connect();
    $stmt = $db->prepare(
        'SELECT ci.id
         FROM construction_images ci
         INNER JOIN customers c ON c.id = ci.customer_id
         WHERE ci.id = :id AND c.worker_id = :worker_id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $owned = (bool) $result->fetchArray(SQLITE3_ASSOC);
    $result->finalize();
    return $owned;
}

function normalize_uploaded_files(array $fileField): array
{
    if (!isset($fileField['name'])) {
        return [];
    }

    if (!is_array($fileField['name'])) {
        return [$fileField];
    }

    $files = [];
    foreach ($fileField['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $fileField['type'][$index] ?? '',
            'tmp_name' => $fileField['tmp_name'][$index] ?? '',
            'error' => $fileField['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileField['size'][$index] ?? 0,
        ];
    }

    return $files;
}

function upload_customer_image(int $customerId, array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('图片上传失败，请重试。');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_IMAGE_SIZE) {
        throw new RuntimeException('图片大小不能超过 5MB。');
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
        throw new RuntimeException('仅支持 JPG、PNG、GIF、WEBP 格式。');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, (string) $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array((string) $mimeType, ALLOWED_MIME_TYPES, true)) {
        throw new RuntimeException('上传文件不是有效图片。');
    }

    $imageInfo = @getimagesize((string) $file['tmp_name']);
    if (!$imageInfo || !in_array((int) ($imageInfo[2] ?? 0), [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
        throw new RuntimeException('图片文件已损坏或格式不受支持。');
    }

    $uploadDir = customer_upload_dir($customerId);
    $thumbDir = customer_thumbnail_dir($customerId);

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0775, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    $relativePath = relative_customer_upload_dir($customerId) . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('服务器保存图片失败。');
    }

    $thumbnailPath = $thumbDir . '/' . $filename;
    create_thumbnail($targetPath, $thumbnailPath, 200);

    return $relativePath;
}

function create_thumbnail(string $sourcePath, string $thumbnailPath, int $maxWidth = 200): void
{
    $info = @getimagesize($sourcePath);
    if (!$info) {
        copy($sourcePath, $thumbnailPath);
        return;
    }

    [$width, $height, $imageType] = $info;
    if ($width <= 0 || $height <= 0) {
        copy($sourcePath, $thumbnailPath);
        return;
    }

    $targetWidth = min($maxWidth, $width);
    $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));

    $sourceImage = match ($imageType) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
        IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
        IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
        default => false,
    };

    if (!$sourceImage) {
        copy($sourcePath, $thumbnailPath);
        return;
    }

    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

    if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
        imagefilledrectangle($thumbnail, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $saved = match ($imageType) {
        IMAGETYPE_JPEG => imagejpeg($thumbnail, $thumbnailPath, 85),
        IMAGETYPE_PNG => imagepng($thumbnail, $thumbnailPath, 6),
        IMAGETYPE_GIF => imagegif($thumbnail, $thumbnailPath),
        IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($thumbnail, $thumbnailPath, 85) : false,
        default => false,
    };

    if (!$saved) {
        copy($sourcePath, $thumbnailPath);
    }

    imagedestroy($thumbnail);
    imagedestroy($sourceImage);
}

function thumbnail_relative_path(string $imagePath): string
{
    $normalized = ltrim(str_replace('\\', '/', $imagePath), '/');
    if (preg_match('#^uploads/customer_(\d+)/([^/]+)$#', $normalized, $matches)) {
        return 'uploads/thumbnails/customer_' . $matches[1] . '/' . $matches[2];
    }
    return $normalized;
}

function delete_image_files(string $imagePath): void
{
    $paths = [
        public_path($imagePath),
        public_path(thumbnail_relative_path($imagePath)),
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function remove_qr_file(?string $qrCodePath): void
{
    if (!$qrCodePath) {
        return;
    }

    $absolutePath = public_path($qrCodePath);
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function cleanup_customer_dirs(int $customerId): void
{
    $directories = [customer_upload_dir($customerId), customer_thumbnail_dir($customerId)];
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }
        $files = array_diff(scandir($directory) ?: [], ['.', '..']);
        if ($files === []) {
            @rmdir($directory);
        }
    }
}

function delete_customer_cascade(int $customerId): bool
{
    $db = db_connect();
    $customer = get_customer_by_id($customerId);
    if (!$customer) {
        return false;
    }

    $stmt = $db->prepare('SELECT image_path FROM construction_images WHERE customer_id = :customer_id');
    $stmt->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
    $images = fetch_all_rows($stmt->execute());

    foreach ($images as $image) {
        delete_image_files((string) $image['image_path']);
    }

    remove_qr_file($customer['qr_code'] ?? null);

    $deleteImages = $db->prepare('DELETE FROM construction_images WHERE customer_id = :customer_id');
    $deleteImages->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
    $deleteImages->execute();

    $deleteCustomer = $db->prepare('DELETE FROM customers WHERE id = :customer_id');
    $deleteCustomer->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
    $deleteCustomer->execute();

    cleanup_customer_dirs($customerId);

    return true;
}

function delete_worker_cascade(int $workerId): bool
{
    $db = db_connect();
    $worker = get_worker_by_id($workerId);
    if (!$worker) {
        return false;
    }

    $stmt = $db->prepare('SELECT id FROM customers WHERE worker_id = :worker_id');
    $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $customers = fetch_all_rows($stmt->execute());

    foreach ($customers as $customer) {
        delete_customer_cascade((int) $customer['id']);
    }

    remove_qr_file($worker['qr_code'] ?? null);

    $deleteWorker = $db->prepare('DELETE FROM construction_workers WHERE id = :worker_id');
    $deleteWorker->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $deleteWorker->execute();

    return true;
}

function delete_image_record(int $imageId): bool
{
    $db = db_connect();
    $image = get_image_by_id($imageId);
    if (!$image) {
        return false;
    }

    delete_image_files((string) $image['image_path']);

    $stmt = $db->prepare('DELETE FROM construction_images WHERE id = :id');
    $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $stmt->execute();

    cleanup_customer_dirs((int) $image['customer_id']);

    return true;
}

function http_get_binary(string $url): string|false
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'LiansuProgress/1.0',
        ]);
        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body !== false && $statusCode >= 200 && $statusCode < 300) {
            return $body;
        }
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "User-Agent: LiansuProgress/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    return @file_get_contents($url, false, $context);
}

function find_chinese_font(): ?string
{
    $candidates = [
        // macOS
        '/System/Library/Fonts/PingFang.ttc',
        '/System/Library/Fonts/STHeiti Medium.ttc',
        '/System/Library/Fonts/STHeiti Light.ttc',
        '/System/Library/Fonts/Hiragino Sans GB.ttc',
        // Linux
        '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
        '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
        '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
        '/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf',
        // Windows (via WAMP/XAMPP)
        'C:\Windows\Fonts\msyh.ttc',
        'C:\Windows\Fonts\simhei.ttf',
    ];

    foreach ($candidates as $path) {
        if (file_exists($path) && is_readable($path)) {
            return $path;
        }
    }

    return null;
}

function generate_qr_code(string $data, string $savePath): bool
{
    $providers = [
        'https://quickchart.io/qr?size=320&margin=1&text=' . rawurlencode($data),
        'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode($data),
    ];

    foreach ($providers as $provider) {
        $binary = http_get_binary($provider);
        if ($binary === false || strlen($binary) < 100) {
            continue;
        }

        if (@getimagesizefromstring($binary) === false) {
            continue;
        }

        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (file_put_contents($savePath, $binary) !== false) {
            return true;
        }
    }

    return false;
}

function composite_qr_with_background(string $qrPath, string $outputPath): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $qrImage = @imagecreatefrompng($qrPath);
    if (!$qrImage) {
        return false;
    }

    $qrWidth = imagesx($qrImage);
    $qrHeight = imagesy($qrImage);

    // 3:4 ratio canvas
    $bgWidth = 600;
    $bgHeight = 800;

    $bg = imagecreatetruecolor($bgWidth, $bgHeight);
    imageantialias($bg, true);

    // Green background #07C160
    $green = imagecolorallocate($bg, 7, 193, 96);
    imagefill($bg, 0, 0, $green);

    // Place QR code centered, slightly above middle to leave room for text
    $qrX = (int) (($bgWidth - $qrWidth) / 2);
    $qrY = (int) (($bgHeight - $qrHeight) / 2) - 50;

    // White padding behind QR code for contrast
    $padding = 16;
    $white = imagecolorallocate($bg, 255, 255, 255);
    imagefilledrectangle(
        $bg,
        $qrX - $padding, $qrY - $padding,
        $qrX + $qrWidth + $padding, $qrY + $qrHeight + $padding,
        $white
    );

    imagecopy($bg, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight);

    // Text below QR code
    $text = '长按识别二维码，查看施工进度';
    $fontPath = find_chinese_font();

    if ($fontPath) {
        $fontSize = 22;
        $textColor = imagecolorallocate($bg, 255, 255, 255);

        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textX = (int) (($bgWidth - $textWidth) / 2);
        $textY = $qrY + $qrHeight + $padding + 100;

        imagettftext($bg, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $text);
    }

    $directory = dirname($outputPath);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $result = imagepng($bg, $outputPath);

    imagedestroy($qrImage);
    imagedestroy($bg);

    return $result;
}

function generate_customer_qr(int $customerId): ?string
{
    $customer = get_customer_by_id($customerId);
    if (!$customer) {
        return null;
    }

    $relativePath = 'uploads/qrcodes/customer_' . $customerId . '.png';
    $absolutePath = public_path($relativePath);
    $targetUrl = customer_user_url($customerId);

    // Step 1: generate raw QR code to temp file
    $rawPath = $absolutePath . '.raw.png';
    if (!generate_qr_code($targetUrl, $rawPath)) {
        return null;
    }

    // Step 2: composite into 3:4 green background with text
    if (!composite_qr_with_background($rawPath, $absolutePath)) {
        // Fallback: use raw QR code
        rename($rawPath, $absolutePath);
    } else {
        @unlink($rawPath);
    }

    $db = db_connect();
    $stmt = $db->prepare('UPDATE customers SET qr_code = :qr_code WHERE id = :id');
    $stmt->bindValue(':qr_code', $relativePath, SQLITE3_TEXT);
    $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
    $stmt->execute();

    return $relativePath;
}

function customer_images(int $customerId): array
{
    $db = db_connect();
    $stmt = $db->prepare(
        'SELECT id, customer_id, image_path, uploaded_at
         FROM construction_images
         WHERE customer_id = :customer_id
         ORDER BY uploaded_at DESC, id DESC'
    );
    $stmt->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rows = fetch_all_rows($result);

    return array_map(static function (array $row): array {
        $row['thumbnail_path'] = thumbnail_relative_path((string) $row['image_path']);
        return $row;
    }, $rows);
}

function worker_customer_list(int $workerId): array
{
    $db = db_connect();
    $stmt = $db->prepare(
        'SELECT c.id, c.worker_id, c.customer_phone, c.address, c.qr_code, c.created_at,
                COUNT(ci.id) AS image_count
         FROM customers c
         LEFT JOIN construction_images ci ON ci.customer_id = c.id
         WHERE c.worker_id = :worker_id
         GROUP BY c.id
         ORDER BY c.created_at DESC, c.id DESC'
    );
    $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $customers = fetch_all_rows($stmt->execute());

    foreach ($customers as &$customer) {
        $customer['images'] = customer_images((int) $customer['id']);
        $customer['qr_url'] = $customer['qr_code'] ?: '';
        $customer['user_url'] = customer_user_url((int) $customer['id']);
    }
    unset($customer);

    return $customers;
}

function admin_customer_list(?int $workerId = null, string $keyword = ''): array
{
    $db = db_connect();
    $sql = 'SELECT c.id, c.customer_phone, c.address, c.qr_code, c.created_at,
                   w.id AS worker_id, w.name AS worker_name, w.phone AS worker_phone,
                   COUNT(ci.id) AS image_count
            FROM customers c
            INNER JOIN construction_workers w ON w.id = c.worker_id
            LEFT JOIN construction_images ci ON ci.customer_id = c.id
            WHERE 1 = 1';

    if ($workerId) {
        $sql .= ' AND c.worker_id = :worker_id';
    }
    if ($keyword !== '') {
        $sql .= ' AND (c.customer_phone LIKE :keyword OR c.address LIKE :keyword)';
    }
    $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC, c.id DESC';

    $stmt = $db->prepare($sql);
    if ($workerId) {
        $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    }
    if ($keyword !== '') {
        $stmt->bindValue(':keyword', '%' . $keyword . '%', SQLITE3_TEXT);
    }

    $customers = fetch_all_rows($stmt->execute());
    foreach ($customers as &$customer) {
        $customer['images'] = customer_images((int) $customer['id']);
        $customer['qr_url'] = $customer['qr_code'] ?: '';
    }
    unset($customer);

    return $customers;
}

function worker_list(): array
{
    $db = db_connect();
    $result = $db->query(
        'SELECT w.id, w.name, w.phone, w.show_phone, w.qr_code, w.created_at,
                (SELECT COUNT(*) FROM customers c WHERE c.worker_id = w.id) AS customer_count,
                (SELECT COUNT(*) FROM construction_images ci INNER JOIN customers c2 ON c2.id = ci.customer_id WHERE c2.worker_id = w.id) AS image_count
         FROM construction_workers w
         ORDER BY w.created_at DESC, w.id DESC'
    );

    return fetch_all_rows($result);
}

function all_images(?int $workerId = null, ?int $customerId = null): array
{
    $db = db_connect();
    $sql = 'SELECT ci.id, ci.customer_id, ci.image_path, ci.uploaded_at,
                   c.customer_phone, c.address, c.worker_id,
                   w.name AS worker_name
            FROM construction_images ci
            INNER JOIN customers c ON c.id = ci.customer_id
            INNER JOIN construction_workers w ON w.id = c.worker_id
            WHERE 1 = 1';

    if ($workerId) {
        $sql .= ' AND c.worker_id = :worker_id';
    }
    if ($customerId) {
        $sql .= ' AND c.id = :customer_id';
    }

    $sql .= ' ORDER BY ci.uploaded_at DESC, ci.id DESC';

    $stmt = $db->prepare($sql);
    if ($workerId) {
        $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    }
    if ($customerId) {
        $stmt->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
    }

    $images = fetch_all_rows($stmt->execute());
    return array_map(static function (array $row): array {
        $row['thumbnail_path'] = thumbnail_relative_path((string) $row['image_path']);
        return $row;
    }, $images);
}

function statistics_overview(): array
{
    $db = db_connect();
    return [
        'worker_count' => (int) $db->querySingle('SELECT COUNT(*) FROM construction_workers'),
        'customer_count' => (int) $db->querySingle('SELECT COUNT(*) FROM customers'),
        'image_count' => (int) $db->querySingle('SELECT COUNT(*) FROM construction_images'),
    ];
}
