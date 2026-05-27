<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

try {
    $request = request_data();
    $action = trim((string) ($request['action'] ?? ''));

    switch ($action) {
        case 'login':
            admin_login($request);
            break;

        case 'logout':
            admin_logout();
            break;

        case 'bootstrap':
            require_admin();
            json_success('获取成功。', [
                'stats' => statistics_overview(),
                'workers' => worker_list(),
                'customers' => admin_customer_list(),
                'images' => all_images(),
                'session' => [
                    'username' => (string) ($_SESSION['admin_username'] ?? 'admin'),
                ],
            ]);
            break;

        case 'stats':
            require_admin();
            json_success('获取成功。', ['stats' => statistics_overview()]);
            break;

        case 'list_workers':
            require_admin();
            json_success('获取成功。', ['workers' => worker_list()]);
            break;

        case 'save_worker':
            require_admin();
            save_worker($request);
            break;

        case 'delete_worker':
            require_admin();
            delete_worker($request);
            break;

        case 'list_customers':
            require_admin();
            list_customers($request);
            break;

        case 'delete_customer':
            require_admin();
            delete_customer($request);
            break;

        case 'get_customer_images':
            require_admin();
            get_customer_images($request);
            break;

        case 'list_images':
            require_admin();
            list_images($request);
            break;

        case 'delete_image':
            require_admin();
            delete_image($request);
            break;

        case 'get_settings':
            require_admin();
            json_success('获取成功。', ['settings' => get_all_settings()]);
            break;

        case 'save_settings':
            require_admin();
            save_settings($request);
            break;

        default:
            json_error('未知操作。');
    }
} catch (Throwable $exception) {
    json_error($exception->getMessage(), 500);
}

function admin_login(array $request): void
{
    $username = trim((string) ($request['username'] ?? ''));
    $password = (string) ($request['password'] ?? '');

    if ($username === '' || $password === '') {
        json_error('请输入管理员账号和密码。');
    }

    $db = db_connect();
    $stmt = $db->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $admin = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    $result->finalize();

    if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
        json_error('账号或密码错误。', 401);
    }

    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $admin['username'];

    json_success('登录成功。', [
        'session' => ['username' => $admin['username']],
    ]);
}

function admin_logout(): void
{
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_username']);
    json_success('已退出登录。');
}

function save_worker(array $request): void
{
    $db = db_connect();
    $workerId = (int) ($request['id'] ?? 0);
    $name = trim((string) ($request['name'] ?? ''));
    $phone = trim((string) ($request['phone'] ?? ''));
    $showPhone = normalize_flag($request['show_phone'] ?? '1');
    $password = (string) ($request['login_password'] ?? '');

    if ($name === '' || $phone === '') {
        json_error('请完整填写施工员姓名和手机号。');
    }

    if (!preg_match('/^1\d{10}$/', $phone)) {
        json_error('请输入有效的 11 位手机号。');
    }

    $checkSql = 'SELECT id FROM construction_workers WHERE phone = :phone';
    if ($workerId > 0) {
        $checkSql .= ' AND id != :id';
    }
    $checkSql .= ' LIMIT 1';

    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    if ($workerId > 0) {
        $checkStmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
    }
    $checkResult = $checkStmt->execute();
    $exists = $checkResult->fetchArray(SQLITE3_ASSOC);
    $checkResult->finalize();

    if ($exists) {
        json_error('该手机号已被其他施工员使用。');
    }

    if ($workerId > 0) {
        $worker = get_worker_by_id($workerId);
        if (!$worker) {
            json_error('施工员不存在。', 404);
        }

        $fields = [
            'name = :name',
            'phone = :phone',
            'show_phone = :show_phone',
        ];

        if ($password !== '') {
            $fields[] = 'login_password = :login_password';
        }

        $stmt = $db->prepare('UPDATE construction_workers SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':show_phone', $showPhone, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $workerId, SQLITE3_INTEGER);
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                json_error('新密码至少 6 位。');
            }
            $stmt->bindValue(':login_password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        }
        $stmt->execute();

        json_success('施工员信息已更新。', ['workers' => worker_list()]);
    }

    if (mb_strlen($password) < 6) {
        json_error('请设置至少 6 位登录密码。');
    }

    $stmt = $db->prepare(
        'INSERT INTO construction_workers (name, phone, show_phone, login_password)
         VALUES (:name, :phone, :show_phone, :login_password)'
    );
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':show_phone', $showPhone, SQLITE3_INTEGER);
    $stmt->bindValue(':login_password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->execute();

    json_success('施工员已新增。', ['workers' => worker_list()]);
}

function delete_worker(array $request): void
{
    $workerId = (int) ($request['id'] ?? 0);
    if ($workerId <= 0) {
        json_error('缺少施工员编号。');
    }

    if (!delete_worker_cascade($workerId)) {
        json_error('施工员不存在或已删除。', 404);
    }

    json_success('施工员及其客户资料已删除。', [
        'stats' => statistics_overview(),
        'workers' => worker_list(),
    ]);
}

function list_customers(array $request): void
{
    $workerId = (int) ($request['worker_id'] ?? 0);
    $keyword = trim((string) ($request['keyword'] ?? ''));

    json_success('获取成功。', [
        'customers' => admin_customer_list($workerId > 0 ? $workerId : null, $keyword),
    ]);
}

function delete_customer(array $request): void
{
    $customerId = (int) ($request['id'] ?? 0);
    if ($customerId <= 0) {
        json_error('缺少客户编号。');
    }

    if (!delete_customer_cascade($customerId)) {
        json_error('客户不存在或已删除。', 404);
    }

    json_success('客户及施工图片已删除。', [
        'stats' => statistics_overview(),
    ]);
}

function get_customer_images(array $request): void
{
    $customerId = (int) ($request['customer_id'] ?? 0);
    if ($customerId <= 0) {
        json_error('缺少客户编号。');
    }

    $customer = get_customer_by_id($customerId);
    if (!$customer) {
        json_error('客户不存在。', 404);
    }

    json_success('获取成功。', [
        'customer' => $customer,
        'images' => customer_images($customerId),
    ]);
}

function list_images(array $request): void
{
    $workerId = (int) ($request['worker_id'] ?? 0);
    $customerId = (int) ($request['customer_id'] ?? 0);

    json_success('获取成功。', [
        'images' => all_images($workerId > 0 ? $workerId : null, $customerId > 0 ? $customerId : null),
    ]);
}

function delete_image(array $request): void
{
    $imageId = (int) ($request['id'] ?? 0);
    if ($imageId <= 0) {
        json_error('缺少图片编号。');
    }

    if (!delete_image_record($imageId)) {
        json_error('图片不存在或已删除。', 404);
    }

    json_success('图片已删除。', [
        'stats' => statistics_overview(),
    ]);
}

function save_settings(array $request): void
{
    $appName = trim((string) ($request['app_name'] ?? ''));
    $servicePhone = trim((string) ($request['service_phone'] ?? ''));

    if ($appName !== '') {
        save_setting('app_name', $appName);
    }
    if ($servicePhone !== '') {
        save_setting('service_phone', $servicePhone);
    }

    json_success('系统设置已保存。', [
        'settings' => get_all_settings(),
    ]);
}

