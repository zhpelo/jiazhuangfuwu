<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

try {
    $request = request_data();
    $action = trim((string) ($request['action'] ?? ''));

    switch ($action) {
        case 'login':
            worker_login($request);
            break;

        case 'logout':
            worker_logout();
            break;

        case 'bootstrap':
            $workerId = require_worker();
            $worker = get_worker_by_id($workerId);
            if (!$worker) {
                worker_logout();
            }
            json_success('获取成功。', [
                'worker' => sanitize_worker($worker),
                'customers' => worker_customer_list($workerId),
            ]);
            break;

        case 'list_customers':
            $workerId = require_worker();
            json_success('获取成功。', ['customers' => worker_customer_list($workerId)]);
            break;

        case 'add_customer':
            $workerId = require_worker();
            add_customer($workerId, $request);
            break;

        case 'generate_customer_qr':
            $workerId = require_worker();
            regenerate_customer_qr($workerId, $request);
            break;

        case 'upload_images':
            $workerId = require_worker();
            upload_images($workerId, $request);
            break;

        case 'delete_image':
            $workerId = require_worker();
            delete_image($workerId, $request);
            break;

        case 'update_profile':
            $workerId = require_worker();
            update_profile($workerId, $request);
            break;

        case 'get_customer_detail':
            $workerId = require_worker();
            get_customer_detail($workerId, $request);
            break;

        case 'change_password':
            $workerId = require_worker();
            change_password($workerId, $request);
            break;

        default:
            json_error('未知操作。');
    }
} catch (Throwable $exception) {
    json_error($exception->getMessage(), 500);
}

function worker_login(array $request): void
{
    $phone = trim((string) ($request['phone'] ?? ''));
    $password = (string) ($request['password'] ?? '');

    if ($phone === '' || $password === '') {
        json_error('请输入手机号和密码。');
    }

    $db = db_connect();
    $stmt = $db->prepare('SELECT * FROM construction_workers WHERE phone = :phone LIMIT 1');
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $result = $stmt->execute();
    $worker = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    $result->finalize();

    if (!$worker || !password_verify($password, (string) $worker['login_password'])) {
        json_error('手机号或密码错误。', 401);
    }

    session_regenerate_id(true);
    $_SESSION['worker_logged_in'] = true;
    $_SESSION['worker_id'] = (int) $worker['id'];

    json_success('登录成功。', [
        'worker' => sanitize_worker(get_worker_by_id((int) $worker['id'])),
        'customers' => worker_customer_list((int) $worker['id']),
    ]);
}

function worker_logout(): void
{
    unset($_SESSION['worker_logged_in'], $_SESSION['worker_id']);
    json_success('已退出登录。');
}

function add_customer(int $workerId, array $request): void
{
    $phone = trim((string) ($request['customer_phone'] ?? ''));
    $address = trim((string) ($request['address'] ?? ''));

    if ($phone === '' || $address === '') {
        json_error('请填写客户手机号和施工地址。');
    }

    if (!preg_match('/^1\d{10}$/', $phone)) {
        json_error('请输入有效的客户手机号。');
    }

    $db = db_connect();
    $stmt = $db->prepare(
        'INSERT INTO customers (worker_id, customer_phone, address)
         VALUES (:worker_id, :customer_phone, :address)'
    );
    $stmt->bindValue(':worker_id', $workerId, SQLITE3_INTEGER);
    $stmt->bindValue(':customer_phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->execute();

    $customerId = $db->lastInsertRowID();
    $qrCode = generate_customer_qr((int) $customerId);

    $message = $qrCode ? '客户已新增并生成二维码。' : '客户已新增，但二维码生成失败，可稍后重试。';
    json_success($message, [
        'customers' => worker_customer_list($workerId),
    ]);
}

function regenerate_customer_qr(int $workerId, array $request): void
{
    $customerId = (int) ($request['customer_id'] ?? 0);
    if ($customerId <= 0) {
        json_error('缺少客户编号。');
    }

    if (!worker_owns_customer($workerId, $customerId)) {
        json_error('无权操作该客户。', 403);
    }

    $qrCode = generate_customer_qr($customerId);
    if (!$qrCode) {
        json_error('二维码生成失败，请稍后重试。', 500);
    }

    json_success('二维码已生成。', [
        'customers' => worker_customer_list($workerId),
    ]);
}

function upload_images(int $workerId, array $request): void
{
    $customerId = (int) ($request['customer_id'] ?? 0);
    if ($customerId <= 0) {
        json_error('缺少客户编号。');
    }

    if (!worker_owns_customer($workerId, $customerId)) {
        json_error('无权操作该客户。', 403);
    }

    $files = normalize_uploaded_files($_FILES['images'] ?? []);
    if ($files === []) {
        json_error('请选择要上传的图片。');
    }

    $db = db_connect();
    $uploadedCount = 0;
    $errors = [];

    foreach ($files as $file) {
        try {
            $path = upload_customer_image($customerId, $file);
            $stmt = $db->prepare(
                'INSERT INTO construction_images (customer_id, image_path)
                 VALUES (:customer_id, :image_path)'
            );
            $stmt->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
            $stmt->bindValue(':image_path', $path, SQLITE3_TEXT);
            $stmt->execute();
            $uploadedCount++;
        } catch (Throwable $exception) {
            $errors[] = ((string) ($file['name'] ?? '图片')) . '：' . $exception->getMessage();
        }
    }

    if ($uploadedCount === 0) {
        json_error($errors[0] ?? '图片上传失败。');
    }

    $message = '成功上传 ' . $uploadedCount . ' 张图片。';
    if ($errors !== []) {
        $message .= ' 部分文件失败：' . implode('；', $errors);
    }

    json_success($message, [
        'customers' => worker_customer_list($workerId),
    ]);
}

function delete_image(int $workerId, array $request): void
{
    $imageId = (int) ($request['id'] ?? 0);
    if ($imageId <= 0) {
        json_error('缺少图片编号。');
    }

    if (!worker_owns_image($workerId, $imageId)) {
        json_error('无权删除该图片。', 403);
    }

    if (!delete_image_record($imageId)) {
        json_error('图片不存在或已删除。', 404);
    }

    json_success('图片已删除。', [
        'customers' => worker_customer_list($workerId),
    ]);
}

function update_profile(int $workerId, array $request): void
{
    $name = trim((string) ($request['name'] ?? ''));
    $phone = trim((string) ($request['phone'] ?? ''));
    $showPhone = normalize_flag($request['show_phone'] ?? '1');

    if ($name === '' || $phone === '') {
        json_error('请完整填写姓名和手机号。');
    }

    if (!preg_match('/^1\d{10}$/', $phone)) {
        json_error('请输入有效的 11 位手机号。');
    }

    $db = db_connect();
    $checkStmt = $db->prepare(
        'SELECT id FROM construction_workers WHERE phone = :phone AND id != :id LIMIT 1'
    );
    $checkStmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $checkStmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
    $checkResult = $checkStmt->execute();
    $exists = $checkResult->fetchArray(SQLITE3_ASSOC);
    $checkResult->finalize();

    if ($exists) {
        json_error('该手机号已被其他施工员使用。');
    }

    $stmt = $db->prepare(
        'UPDATE construction_workers
         SET name = :name, phone = :phone, show_phone = :show_phone
         WHERE id = :id'
    );
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':show_phone', $showPhone, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
    $stmt->execute();

    json_success('个人资料已保存。', [
        'worker' => sanitize_worker(get_worker_by_id($workerId)),
    ]);
}

function get_customer_detail(int $workerId, array $request): void
{
    $customerId = (int) ($request['customer_id'] ?? 0);
    if ($customerId <= 0) {
        json_error('缺少客户编号。');
    }

    if (!worker_owns_customer($workerId, $customerId)) {
        json_error('无权查看该客户。', 403);
    }

    $customer = get_customer_by_id($customerId);
    if (!$customer) {
        json_error('客户不存在。', 404);
    }

    $images = customer_images($customerId);
    $customer['images'] = $images;
    $customer['image_count'] = count($images);
    $customer['user_url'] = customer_user_url($customerId);

    json_success('获取成功。', ['customer' => $customer]);
}

function change_password(int $workerId, array $request): void
{
    $oldPassword = (string) ($request['old_password'] ?? '');
    $newPassword = (string) ($request['new_password'] ?? '');

    if ($oldPassword === '' || $newPassword === '') {
        json_error('请填写旧密码和新密码。');
    }

    if (mb_strlen($newPassword) < 6) {
        json_error('新密码至少 6 位。');
    }

    $worker = get_worker_by_id($workerId);
    if (!$worker) {
        json_error('施工员不存在。', 404);
    }

    if (!password_verify($oldPassword, (string) $worker['login_password'])) {
        json_error('旧密码错误。');
    }

    $db = db_connect();
    $stmt = $db->prepare('UPDATE construction_workers SET login_password = :login_password WHERE id = :id');
    $stmt->bindValue(':login_password', password_hash($newPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
    $stmt->execute();

    json_success('密码已修改。');
}
