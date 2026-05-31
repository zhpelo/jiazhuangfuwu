<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

$searchPhone = trim((string) ($_GET['phone'] ?? ''));
$error = null;

if ($searchPhone !== '') {
    if (!preg_match('/^1\d{10}$/', $searchPhone)) {
        $error = '请输入有效的 11 位手机号。';
    } else {
        $db = db_connect();
        $stmt = $db->prepare(
            'SELECT c.id, c.customer_phone, c.address, w.name AS worker_name
             FROM customers c
             INNER JOIN construction_workers w ON w.id = c.worker_id
             WHERE c.customer_phone = :phone
             ORDER BY c.created_at DESC
             LIMIT 1'
        );
        $stmt->bindValue(':phone', $searchPhone, SQLITE3_TEXT);
        $result = $stmt->execute();
        $customer = $result->fetchArray(SQLITE3_ASSOC) ?: null;
        $result->finalize();

        if ($customer) {
            header('Location: user.php?customer_id=' . (int) $customer['id']);
            exit;
        } else {
            $error = '未找到该手机号对应的施工记录，请联系施工员确认。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?= e(app_name()) ?> - 电子质保卡查询</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wechat: '#07C160',
                        'wechat-dark': '#06AD56',
                        'wechat-tap': '#F2F2F2',
                        'link': '#576B95',
                    }
                }
            }
        }
    </script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif; }
        input:focus { outline: none; }
    </style>
</head>
<body class="bg-[#EDEDED] min-h-screen">
<main class="max-w-lg mx-auto px-4 pt-12 pb-8">
    <!-- Brand Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#07C160] text-white text-2xl font-bold mb-4 shadow-lg shadow-green-200">
            <?= mb_substr(e(app_name()), 0, 1) ?>
        </div>
        <h1 class="text-xl font-bold text-gray-900"><?= e(app_name()) ?></h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">输入手机号，即可查看电子质保卡图片与施工员信息</p>
    </div>

    <!-- Search Card -->
    <div class="bg-white rounded-xl shadow-sm px-5 py-6">
        <form method="get" action="index.php" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="searchPhone">手机号</label>
                <input
                    id="searchPhone" name="phone" type="tel" inputmode="numeric" maxlength="11"
                    placeholder="请输入您的手机号"
                    value="<?= e($searchPhone) ?>"
                    required autofocus
                    class="w-full h-11 px-0 text-base text-gray-900 bg-transparent border-0 border-b-2 border-gray-200 focus:border-[#07C160] transition-colors placeholder:text-gray-300"
                >
            </div>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 text-sm rounded-lg px-4 py-3"><?= e($error) ?></div>
            <?php endif; ?>
            <button
                type="submit"
                class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-base transition-colors"
            >
                查询电子质保卡
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center text-xs text-gray-400 leading-relaxed">
        <p class="font-medium text-gray-500"><?= e(app_name()) ?></p>
        <?php $ft = get_setting('footer_text'); if ($ft !== null && $ft !== ''): ?>
        <p><?= e($ft) ?></p>
        <?php endif; ?>
        <?php if (service_phone()): ?>
        <p>服务电话：<a class="text-[#576B95]" href="tel:<?= e(service_phone()) ?>"><?= e(service_phone()) ?></a></p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>


