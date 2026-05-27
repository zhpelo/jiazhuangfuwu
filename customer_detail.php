<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

$workerLoggedIn = is_worker_logged_in();
$customerId = (int) ($_GET['customer_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?= e(app_name()) ?> - 客户详情</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wechat: '#07C160',
                        'wechat-dark': '#06AD56',
                        'wechat-tap': '#F2F2F2',
                        link: '#576B95',
                    }
                }
            }
        }
    </script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif; }
        input:focus, textarea:focus { outline: none; }
    </style>
</head>
<body class="bg-[#EDEDED] min-h-screen">

<!-- Not logged in -->
<section class="<?= $workerLoggedIn ? 'hidden' : '' ?>" id="authView">
    <div class="max-w-lg mx-auto px-4 pt-12 pb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#07C160] text-white text-2xl font-bold mb-4 shadow-lg shadow-green-200">
            <?= mb_substr(e(app_name()), 0, 1) ?>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">请先登录</h1>
        <p class="text-sm text-gray-500 mb-6">您需要登录施工员账号才能查看客户详情</p>
        <a href="worker.php" class="inline-block w-full max-w-xs h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] text-white font-medium text-sm leading-[48px] transition-colors">前往登录</a>
    </div>
</section>

<!-- App View -->
<main class="max-w-lg mx-auto px-4 pt-6 pb-8 <?= $workerLoggedIn ? '' : 'hidden' ?>" id="appView">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#07C160] text-white text-sm font-bold"><?= mb_substr(e(app_name()), 0, 1) ?></div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">客户详情</h1>
                <p class="text-xs text-gray-400" id="detailGreeting">加载中...</p>
            </div>
        </div>
        <a href="worker.php" class="text-sm text-gray-500 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 active:bg-gray-100 transition-colors">← 返回列表</a>
    </div>

    <div class="space-y-3" id="detailContent">
        <div class="bg-white rounded-xl shadow-sm px-5 py-10 text-center">
            <div class="w-6 h-6 border-2 border-[#07C160] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-sm text-gray-400">正在加载客户信息...</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-400 leading-relaxed">
        <p class="font-medium text-gray-500"><?= e(app_name()) ?></p>
        <p>施工数据仅对当前登录施工员可见</p>
    </div>
</main>

<!-- Preview Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4" id="previewModal">
    <div class="relative w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900" id="previewTitle">图片预览</h3>
            <button type="button" data-close-modal="previewModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="flex items-center justify-center bg-gray-900">
            <img id="previewImage" src="" alt="预览" class="w-full">
        </div>
    </div>
</div>

<!-- Loading Mask -->
<div class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/30" id="loadingMask">
    <div class="bg-white rounded-xl shadow-lg px-6 py-5 flex flex-col items-center gap-3">
        <div class="w-6 h-6 border-2 border-[#07C160] border-t-transparent rounded-full animate-spin"></div>
        <span class="text-sm text-gray-500" id="loadingText">处理中...</span>
    </div>
</div>

<!-- Toast -->
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-[70] bg-gray-900/90 text-white text-sm rounded-lg px-5 py-2.5 opacity-0 pointer-events-none transition-opacity duration-300 shadow-lg" id="toast"></div>

<script>
(() => {
    const state = { customer: null };
    const customerId = <?= $customerId ?>;
    const initialLoggedIn = <?= $workerLoggedIn ? 'true' : 'false' ?>;

    const $ = (id) => document.getElementById(id);
    const detailContent = $('detailContent');
    const detailGreeting = $('detailGreeting');
    const previewModal = $('previewModal');
    const previewImage = $('previewImage');
    const previewTitle = $('previewTitle');
    const loadingMask = $('loadingMask');
    const loadingText = $('loadingText');
    const toast = $('toast');

    async function api(action, payload = {}, options = {}) {
        const { formData = null } = options;
        let body;
        if (formData) {
            body = formData;
            body.append('action', action);
        } else {
            body = new URLSearchParams();
            body.set('action', action);
            Object.entries(payload).forEach(([k, v]) => body.set(k, v));
        }
        const res = await fetch('api_worker.php', { method: 'POST', body, credentials: 'same-origin' });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || '请求失败');
        return result.data || {};
    }

    function setLoading(v, text = '处理中...') {
        loadingText.textContent = text;
        v ? (loadingMask.classList.remove('hidden'), loadingMask.classList.add('flex')) : (loadingMask.classList.add('hidden'), loadingMask.classList.remove('flex'));
    }

    let toastTimer = null;
    function showToast(msg) {
        if (!msg) return;
        toast.textContent = msg;
        toast.classList.add('!opacity-100');
        toast.classList.remove('opacity-0');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.classList.add('opacity-0'); toast.classList.remove('!opacity-100'); }, 2400);
    }

    function openModal(m) { m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); document.body.style.overflow = ''; }

    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderDetail() {
        const c = state.customer;
        if (!c) return;

        detailGreeting.textContent = `${escapeHtml(c.customer_phone)}`;
        const images = Array.isArray(c.images) ? c.images : [];

        const qrBlock = c.qr_code
            ? `<div class="mt-3"> <div class="flex flex-col items-center"><img class="w-36 h-auto object-contain rounded-xl border border-gray-200 bg-white cursor-pointer previewable" src="${escapeHtml(c.qr_code)}?${Date.now()}" data-src="${escapeHtml(c.qr_code)}" data-title="客户二维码" alt="客户二维码"></div>
            <div class="text-xs text-gray-500 text-center p-2">长按二维码，分享给客户查看施工进度</div>
            <div class="flex gap-2 mt-2 justify-center"><button class="text-xs text-gray-500 bg-gray-100 rounded-lg px-3 py-1.5 hover:bg-gray-200 transition-colors" type="button" id="generateQrBtn">刷新二维码</button><a class="text-xs text-[#576B95] bg-blue-50 rounded-lg px-3 py-1.5 hover:bg-blue-100 transition-colors" href="${escapeHtml(c.user_url)}" target="_blank" rel="noopener">打开客户页</a></div></div>`
            : `<div class="mt-3 p-3 bg-orange-50 text-orange-600 text-xs rounded-lg">二维码尚未生成<button class="ml-2 text-[#576B95] underline" type="button" id="generateQrBtn">立即生成</button></div>`;

        const imageBlock = images.length
            ? `<div class="grid grid-cols-3 gap-2">${images.map(img => `
                <div class="relative overflow-hidden rounded-lg bg-gray-100 aspect-square group">
                    <img class="w-full h-full object-cover cursor-pointer previewable" src="${escapeHtml(img.thumbnail_path)}" data-src="${escapeHtml(img.image_path)}" data-title="施工图片" alt="施工图片" loading="lazy">
                    <button class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/50 text-white text-xs leading-6 text-center opacity-0 group-hover:opacity-100 active:opacity-100 transition-opacity" type="button" data-action="delete-image" data-image-id="${img.id}">&times;</button>
                </div>`).join('')}</div>`
            : '<div class="text-center py-8 text-sm text-gray-400">暂未上传施工图片</div>';

        detailContent.innerHTML = `
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div><h2 class="text-base font-semibold text-gray-900">客户信息</h2><p class="text-xs text-gray-400 mt-0.5">创建时间：${escapeHtml(c.created_at)}</p></div>
                        <div class="flex items-center gap-2">
                            
                            <button class="text-xs text-[#576B95] bg-blue-50 rounded-lg px-2.5 py-1 hover:bg-blue-100 active:bg-blue-200 transition-colors" type="button" id="editCustomerBtn">编辑</button>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <div class="flex justify-between items-center px-5 py-3.5"><span class="text-sm text-gray-500 shrink-0">客户手机号</span><span class="text-sm text-gray-900 info-display" data-field="phone">${escapeHtml(c.customer_phone)}</span><input class="hidden text-sm text-gray-900 text-right w-full bg-gray-50 rounded-lg px-2 py-1 info-input" id="editPhone" type="tel" maxlength="11" value="${escapeHtml(c.customer_phone)}" placeholder="请输入手机号"></div>
                    <div class="flex justify-between items-center px-5 py-3.5"><span class="text-sm text-gray-500 shrink-0">施工地址</span><span class="text-sm text-gray-900 text-right max-w-[60%] info-display" data-field="address">${escapeHtml(c.address)}</span><input class="hidden text-sm text-gray-900 text-right w-full bg-gray-50 rounded-lg px-2 py-1 info-input" id="editAddress" type="text" value="${escapeHtml(c.address)}" placeholder="请输入施工地址"></div>
                </div>
                <div class="hidden px-5 py-3 border-t border-gray-100 bg-gray-50/50" id="editActions">
                    <div class="flex gap-2">
                        <button class="flex-1 h-9 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white text-sm font-medium transition-colors" type="button" id="saveCustomerBtn">保存</button>
                        <button class="flex-1 h-9 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 active:bg-gray-200 text-gray-600 text-sm transition-colors" type="button" id="cancelEditBtn">取消</button>
                    </div>
                </div>
                <div class="px-5 py-4">${qrBlock}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div><h2 class="text-base font-semibold text-gray-900">施工图片</h2><p class="text-xs text-gray-400 mt-0.5">支持多张上传，单张不超过 5MB</p></div>
                        <button class="text-sm bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white rounded-lg px-3 py-2 font-medium transition-colors" type="button" id="pickUploadBtn">上传图片</button>
                    </div>
                </div>
                <div class="p-3">${imageBlock}</div>
            </div>
            <input class="hidden" id="uploadInput" type="file" accept="image/*" multiple>
        `;

        bindEvents();
    }

    function bindEvents() {
        // Edit customer info
        const editBtn = document.getElementById('editCustomerBtn');
        const saveBtn = document.getElementById('saveCustomerBtn');
        const cancelBtn = document.getElementById('cancelEditBtn');
        const editActions = document.getElementById('editActions');
        const editPhone = document.getElementById('editPhone');
        const editAddress = document.getElementById('editAddress');

        let editing = false;

        function enterEditMode() {
            editing = true;
            if (editBtn) editBtn.classList.add('hidden');
            if (editActions) editActions.classList.remove('hidden');
            detailContent.querySelectorAll('.info-display').forEach(el => el.classList.add('hidden'));
            detailContent.querySelectorAll('.info-input').forEach(el => el.classList.remove('hidden'));
            if (editPhone) editPhone.focus();
        }

        function exitEditMode() {
            editing = false;
            if (editBtn) editBtn.classList.remove('hidden');
            if (editActions) editActions.classList.add('hidden');
            detailContent.querySelectorAll('.info-display').forEach(el => el.classList.remove('hidden'));
            detailContent.querySelectorAll('.info-input').forEach(el => el.classList.add('hidden'));
        }

        if (editBtn) editBtn.addEventListener('click', enterEditMode);
        if (cancelBtn) cancelBtn.addEventListener('click', exitEditMode);

        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                if (!editing) return;
                const phone = (editPhone?.value ?? '').trim();
                const address = (editAddress?.value ?? '').trim();
                if (!phone || !address) { showToast('请填写手机号和施工地址。'); return; }
                if (!/^1\d{10}$/.test(phone)) { showToast('请输入有效的 11 位手机号。'); return; }

                setLoading(true, '正在保存...');
                try {
                    const data = await api('update_customer', { customer_id: customerId, customer_phone: phone, address });
                    if (data.customers) {
                        const updated = data.customers.find(c => String(c.id) === String(customerId));
                        if (updated) { state.customer = updated; renderDetail(); }
                    }
                    showToast('客户信息已更新');
                } catch (err) { showToast(err.message); }
                finally { setLoading(false); }
            });
        }

        // QR generate
        const qrBtn = document.getElementById('generateQrBtn');
        if (qrBtn) {
            qrBtn.addEventListener('click', async () => {
                setLoading(true, '正在生成二维码...');
                try {
                    const data = await api('generate_customer_qr', { customer_id: customerId });
                    if (data.customers) {
                        const updated = data.customers.find(c => String(c.id) === String(customerId));
                        if (updated) { state.customer = updated; renderDetail(); }
                    }
                    showToast('二维码已更新');
                } catch (err) { showToast(err.message); }
                finally { setLoading(false); }
            });
        }

        // Upload
        const pickBtn = document.getElementById('pickUploadBtn');
        const uploadInput = document.getElementById('uploadInput');
        if (pickBtn && uploadInput) {
            pickBtn.addEventListener('click', () => uploadInput.click());
            uploadInput.addEventListener('change', async () => {
                if (!uploadInput.files || !uploadInput.files.length) return;
                const formData = new FormData();
                formData.append('customer_id', String(customerId));
                Array.from(uploadInput.files).forEach(f => formData.append('images[]', f));
                setLoading(true, '正在上传图片...');
                try {
                    const data = await api('upload_images', {}, { formData });
                    if (data.customers) {
                        const updated = data.customers.find(c => String(c.id) === String(customerId));
                        if (updated) { state.customer = updated; renderDetail(); }
                    }
                    showToast('图片上传完成');
                } catch (err) { showToast(err.message); }
                finally { uploadInput.value = ''; setLoading(false); }
            });
        }

        // Preview
        detailContent.querySelectorAll('.previewable').forEach(el => {
            el.addEventListener('click', () => {
                previewTitle.textContent = el.dataset.title || '图片预览';
                previewImage.src = el.dataset.src || el.getAttribute('src') || '';
                openModal(previewModal);
            });
        });

        // Delete image
        detailContent.querySelectorAll('[data-action="delete-image"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('确认删除这张施工图片吗？')) return;
                setLoading(true, '正在删除图片...');
                try {
                    const data = await api('delete_image', { id: btn.dataset.imageId });
                    if (data.customers) {
                        const updated = data.customers.find(c => String(c.id) === String(customerId));
                        if (updated) { state.customer = updated; renderDetail(); }
                    }
                    showToast('图片已删除');
                } catch (err) { showToast(err.message); }
                finally { setLoading(false); }
            });
        });
    }

    async function bootstrap() {
        if (customerId <= 0) {
            detailContent.innerHTML = '<div class="bg-white rounded-xl shadow-sm px-5 py-10 text-center text-sm text-gray-400">无效的客户编号，请从客户列表进入</div>';
            return;
        }
        setLoading(true, '正在加载客户信息...');
        try {
            const data = await api('get_customer_detail', { customer_id: customerId });
            state.customer = data.customer;
            renderDetail();
        } catch (err) {
            detailContent.innerHTML = `<div class="bg-white rounded-xl shadow-sm px-5 py-10 text-center text-sm text-gray-400">${escapeHtml(err.message)}</div>`;
        }
        finally { setLoading(false); }
    }

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal($(btn.dataset.closeModal)));
    });

    previewModal.addEventListener('click', (e) => {
        if (e.target === previewModal) { closeModal(previewModal); previewImage.src = ''; }
    });

    if (initialLoggedIn) bootstrap();
})();
</script>
</body>
</html>
