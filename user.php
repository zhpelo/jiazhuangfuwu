<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

$customerId = (int) ($_GET['customer_id'] ?? 0);
$customer = $customerId > 0 ? get_customer_by_id($customerId) : null;
$images = $customer ? customer_images($customerId) : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?= e(app_name()) ?><?= $customer ? ' - 施工进度' : '' ?></title>
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
    </style>
</head>
<body class="bg-[#EDEDED] min-h-screen">

<main class="max-w-lg mx-auto px-4 pt-6 pb-8">
    <!-- Brand Header -->
    <div class="flex items-center gap-3 mb-5">
        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#07C160] text-white text-sm font-bold">
            <?= mb_substr(e(app_name()), 0, 1) ?>
        </div>
        <div>
            <h1 class="text-lg font-bold text-gray-900">施工进度</h1>
            <p class="text-xs text-gray-400">扫码即可查看施工图片与施工员信息</p>
        </div>
    </div>

    <?php if (!$customer): ?>
        <div class="bg-white rounded-xl shadow-sm px-5 py-10 text-center">
            <div class="text-4xl mb-3">😕</div>
            <p class="text-gray-500 text-sm">无效二维码或客户不存在</p>
            <p class="text-gray-400 text-xs mt-1">请联系施工员重新获取</p>
        </div>
    <?php else: ?>
        <!-- Customer Info Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-3">
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">客户信息</h2>
                    <span class="text-xs bg-green-50 text-[#07C160] px-2.5 py-1 rounded-full font-medium">#<?= (int) $customer['id'] ?></span>
                </div>
            </div>
            <div class="divide-y divide-gray-50">
                <div class="flex justify-between items-center px-5 py-3.5">
                    <span class="text-sm text-gray-500">施工地址</span>
                    <span class="text-sm text-gray-900 text-right max-w-[60%]"><?= e((string) $customer['address']) ?></span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5">
                    <span class="text-sm text-gray-500">客户手机号</span>
                    <span class="text-sm text-gray-900"><?= e((string) $customer['customer_phone']) ?></span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5">
                    <span class="text-sm text-gray-500">施工员</span>
                    <span class="text-sm text-gray-900"><?= e((string) $customer['worker_name']) ?></span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5">
                    <span class="text-sm text-gray-500">施工员电话</span>
                    <span class="text-sm <?= (int) $customer['show_phone'] === 1 ? 'text-[#576B95]' : 'text-gray-400' ?>">
                        <?= (int) $customer['show_phone'] === 1 ? e((string) $customer['worker_phone']) : '暂未公开' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Images Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-3">
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">施工图片</h2>
                        <p class="text-xs text-gray-400 mt-0.5">点击图片可查看大图</p>
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full"><?= count($images) ?> 张</span>
                </div>
            </div>
            <div class="p-3">
                <?php if ($images === []): ?>
                    <div class="text-center py-10 text-sm text-gray-400">施工员暂未上传图片，请稍后再查看</div>
                <?php else: ?>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ($images as $image): ?>
                            <button
                                type="button"
                                class="preview-trigger relative overflow-hidden rounded-lg bg-gray-100 aspect-square"
                                data-full="<?= e((string) $image['image_path']) ?>"
                            >
                                <img class="w-full h-full object-cover" src="<?= e((string) $image['thumbnail_path']) ?>" alt="施工图片" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-3">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">联系施工员</h2>
                <p class="text-xs text-gray-400 mt-0.5">如需确认现场进度，可直接联系对应施工员</p>
            </div>
            <div class="p-4">
                <?php if ((int) $customer['show_phone'] === 1): ?>
                    <a
                        class="flex items-center justify-center gap-2 w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-sm transition-colors"
                        href="tel:<?= e((string) $customer['worker_phone']) ?>"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        拨打施工员电话
                    </a>
                <?php else: ?>
                    <button class="w-full h-12 rounded-lg bg-gray-100 text-gray-400 font-medium text-sm" type="button" disabled>施工员未公开联系方式</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-400 leading-relaxed">
        <p class="font-medium text-gray-500"><?= e(app_name()) ?></p>
        <p>品质保障</p>
        <p>客服电话：<a class="text-[#576B95]" href="tel:<?= e(service_phone()) ?>"><?= e(service_phone()) ?></a></p>
    </div>
</main>

<!-- Preview Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4" id="previewModal">
    <div class="relative w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">图片预览</h3>
            <button class="text-gray-400 hover:text-gray-600 text-xl leading-none" type="button" id="closePreview">&times;</button>
        </div>
        <div class="flex items-center justify-center bg-gray-900">
            <img id="previewImage" src="" alt="大图预览" class="w-full">
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('previewModal');
    const previewImage = document.getElementById('previewImage');
    const closePreview = document.getElementById('closePreview');

    const show = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; };
    const hide = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); previewImage.src = ''; document.body.style.overflow = ''; };

    document.querySelectorAll('.preview-trigger').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.dataset.full) { previewImage.src = btn.dataset.full; show(); }
        });
    });

    closePreview.addEventListener('click', hide);
    modal.addEventListener('click', (e) => { if (e.target === modal) hide(); });
})();
</script>
</body>
</html>

