<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

$adminLoggedIn = is_admin_logged_in();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?= e(APP_NAME) ?> - 管理后台</title>
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
        .switch { width: 51px; height: 31px; appearance: none; background: #E5E5E5; border-radius: 999px; position: relative; transition: background .2s; cursor: pointer; flex-shrink: 0; }
        .switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 27px; height: 27px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        .switch:checked { background: #07C160; }
        .switch:checked::after { transform: translateX(20px); }
        input:focus, textarea:focus, select:focus { outline: none; }
        select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; appearance: none; }
    </style>
</head>
<body class="bg-[#EDEDED] min-h-screen">

<!-- ========== LOGIN VIEW ========== -->
<section class="<?= $adminLoggedIn ? 'hidden' : '' ?>" id="authView">
    <div class="max-w-lg mx-auto px-4 pt-12 pb-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#07C160] text-white text-2xl font-bold mb-4 shadow-lg shadow-green-200">联</div>
            <h1 class="text-xl font-bold text-gray-900"><?= e(APP_NAME) ?></h1>
            <p class="text-sm text-gray-500 mt-2">管理后台登录</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm px-5 py-6">
            <form class="space-y-4" id="loginForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="adminUsername">用户名</label>
                    <input id="adminUsername" name="username" type="text" placeholder="请输入管理员用户名" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="adminPassword">密码</label>
                    <input id="adminPassword" name="password" type="password" placeholder="请输入管理员密码" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <button type="submit" class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-base transition-colors">登录后台</button>
            </form>
            <div class="mt-5 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400 leading-relaxed">默认用户名：admin<br>默认密码：admin123<br>请在正式部署后及时更换默认管理员密码。</p>
            </div>
        </div>
    </div>
</section>

<!-- ========== APP VIEW ========== -->
<main class="max-w-4xl mx-auto px-4 pt-6 pb-8 <?= $adminLoggedIn ? '' : 'hidden' ?>" id="appView">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#07C160] text-white text-sm font-bold">联</div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">管理后台</h1>
                <p class="text-xs text-gray-400" id="adminGreeting">加载中...</p>
            </div>
        </div>
        <button id="logoutBtn" type="button" class="text-sm text-gray-500 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 active:bg-gray-100 transition-colors">退出</button>
    </div>

    <!-- Tabs -->
    <div class="flex bg-white rounded-full p-1 shadow-sm mb-4 overflow-x-auto">
        <button class="tab-button whitespace-nowrap flex-1 py-2.5 rounded-full text-sm font-medium transition-colors bg-[#07C160] text-white" type="button" data-tab="workers">施工员管理</button>
        <button class="tab-button whitespace-nowrap flex-1 py-2.5 rounded-full text-sm font-medium text-gray-500 transition-colors" type="button" data-tab="customers">客户管理</button>
        <button class="tab-button whitespace-nowrap flex-1 py-2.5 rounded-full text-sm font-medium text-gray-500 transition-colors" type="button" data-tab="images">图片管理</button>
        <button class="tab-button whitespace-nowrap flex-1 py-2.5 rounded-full text-sm font-medium text-gray-500 transition-colors" type="button" data-tab="stats">统计概览</button>
    </div>

    <!-- Tab: Workers -->
    <section class="tab-panel" id="tab-workers">
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">施工员管理</h2>
                    <p class="text-xs text-gray-400 mt-0.5">可新增、编辑和删除施工员。删除将级联删除客户与图片文件。</p>
                </div>
                <button id="openWorkerModal" type="button" class="text-sm bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white rounded-lg px-3 py-2 font-medium transition-colors">新增施工员</button>
            </div>
            <div class="space-y-2" id="workerList"></div>
        </div>
    </section>

    <!-- Tab: Customers -->
    <section class="tab-panel hidden" id="tab-customers">
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-900">客户管理</h2>
                <p class="text-xs text-gray-400 mt-0.5">按施工员、手机号或地址筛选客户，并查看该客户全部施工图片。</p>
            </div>
            <div class="flex gap-2 mb-3">
                <select id="customerWorkerFilter" class="flex-1 h-10 text-sm bg-gray-50 border border-gray-200 rounded-lg px-3 text-gray-700 focus:border-[#07C160] transition-colors"></select>
                <input id="customerKeywordFilter" type="text" placeholder="搜索手机号或地址" class="flex-1 h-10 text-sm bg-gray-50 border border-gray-200 rounded-lg px-3 text-gray-700 focus:border-[#07C160] transition-colors placeholder:text-gray-300">
                <button id="applyCustomerFilter" type="button" class="h-10 text-sm bg-blue-50 text-[#576B95] rounded-lg px-3 font-medium hover:bg-blue-100 transition-colors">筛选</button>
                <button id="resetCustomerFilter" type="button" class="h-10 text-sm bg-gray-100 text-gray-500 rounded-lg px-3 hover:bg-gray-200 transition-colors">重置</button>
            </div>
            <div class="space-y-2" id="customerList"></div>
        </div>
    </section>

    <!-- Tab: Images -->
    <section class="tab-panel hidden" id="tab-images">
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-900">图片管理</h2>
                <p class="text-xs text-gray-400 mt-0.5">可按施工员与客户筛选全部施工图片，支持单张删除。</p>
            </div>
            <div class="flex gap-2 mb-3 flex-wrap">
                <select id="imageWorkerFilter" class="flex-1 min-w-[120px] h-10 text-sm bg-gray-50 border border-gray-200 rounded-lg px-3 text-gray-700 focus:border-[#07C160] transition-colors"></select>
                <select id="imageCustomerFilter" class="flex-1 min-w-[120px] h-10 text-sm bg-gray-50 border border-gray-200 rounded-lg px-3 text-gray-700 focus:border-[#07C160] transition-colors"></select>
                <button id="applyImageFilter" type="button" class="h-10 text-sm bg-blue-50 text-[#576B95] rounded-lg px-3 font-medium hover:bg-blue-100 transition-colors">筛选</button>
                <button id="resetImageFilter" type="button" class="h-10 text-sm bg-gray-100 text-gray-500 rounded-lg px-3 hover:bg-gray-200 transition-colors">重置</button>
            </div>
            <div class="space-y-2" id="imageList"></div>
        </div>
    </section>

    <!-- Tab: Stats -->
    <section class="tab-panel hidden" id="tab-stats">
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-900">统计概览</h2>
                <p class="text-xs text-gray-400 mt-0.5">系统当前施工员、客户和图片总量一目了然。</p>
            </div>
            <div class="grid grid-cols-3 gap-3" id="statsGrid"></div>
        </div>
    </section>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-400 leading-relaxed">
        <p class="font-medium text-gray-500"><?= e(APP_NAME) ?></p>
        <p>管理后台</p>
    </div>
</main>

<!-- ========== MODALS ========== -->

<!-- Worker Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" id="workerModal">
    <div class="w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="text-base font-semibold text-gray-900" id="workerModalTitle">新增施工员</h3>
            <button type="button" data-close-modal="workerModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form class="p-5 space-y-4" id="workerForm">
            <input id="workerIdField" type="hidden">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="workerNameField">姓名</label>
                <input id="workerNameField" type="text" placeholder="请输入施工员姓名" required
                       class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="workerPhoneField">手机号</label>
                <input id="workerPhoneField" type="tel" maxlength="11" inputmode="numeric" placeholder="请输入手机号" required
                       class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
            </div>
            <label class="flex items-center justify-between py-2 cursor-pointer" for="workerShowPhoneField">
                <div>
                    <span class="text-sm font-medium text-gray-700">对客户展示手机号</span>
                    <p class="text-xs text-gray-400 mt-0.5">关闭后客户端仅显示施工员姓名。</p>
                </div>
                <input id="workerShowPhoneField" type="checkbox" checked class="switch">
            </label>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="workerPasswordField">登录密码</label>
                <input id="workerPasswordField" type="password" placeholder="新增时必填，编辑时留空表示不修改"
                       class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                <p class="text-xs text-gray-400 mt-1" id="workerPasswordHelp">新增施工员时，密码至少 6 位。</p>
            </div>
            <button type="submit" class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-sm transition-colors">保存施工员</button>
        </form>
    </div>
</div>

<!-- Customer Images Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" id="customerImagesModal">
    <div class="w-full max-w-lg bg-white rounded-2xl overflow-hidden shadow-xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
            <h3 class="text-base font-semibold text-gray-900" id="customerImagesTitle">客户施工图片</h3>
            <button type="button" data-close-modal="customerImagesModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto" id="customerImagesContent"></div>
    </div>
</div>

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
    const state = { session: null, stats: { worker_count:0, customer_count:0, image_count:0 }, workers: [], customers: [], images: [] };
    const initialLoggedIn = <?= $adminLoggedIn ? 'true' : 'false' ?>;

    const $ = (id) => document.getElementById(id);
    const authView = $('authView');
    const appView = $('appView');
    const adminGreeting = $('adminGreeting');
    const workerList = $('workerList');
    const customerList = $('customerList');
    const imageList = $('imageList');
    const statsGrid = $('statsGrid');
    const workerModal = $('workerModal');
    const customerImagesModal = $('customerImagesModal');
    const previewModal = $('previewModal');
    const previewImage = $('previewImage');
    const previewTitle = $('previewTitle');
    const loadingMask = $('loadingMask');
    const loadingText = $('loadingText');
    const toast = $('toast');

    const workerForm = {
        form: $('workerForm'), id: $('workerIdField'), name: $('workerNameField'),
        phone: $('workerPhoneField'), showPhone: $('workerShowPhoneField'),
        password: $('workerPasswordField'), title: $('workerModalTitle'), help: $('workerPasswordHelp')
    };

    const filters = {
        customerWorker: $('customerWorkerFilter'), customerKeyword: $('customerKeywordFilter'),
        imageWorker: $('imageWorkerFilter'), imageCustomer: $('imageCustomerFilter')
    };

    async function api(action, payload = {}) {
        const body = new URLSearchParams();
        body.set('action', action);
        Object.entries(payload).forEach(([k, v]) => body.set(k, v));
        const res = await fetch('api_admin.php', { method: 'POST', body, credentials: 'same-origin' });
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
        toast.classList.add('!opacity-100'); toast.classList.remove('opacity-0');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.classList.add('opacity-0'); toast.classList.remove('!opacity-100'); }, 2400);
    }

    function openModal(m) { m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); document.body.style.overflow = ''; if (m === previewModal) previewImage.src = ''; }
    function showApp() { authView.classList.add('hidden'); appView.classList.remove('hidden'); }
    function showAuth() { appView.classList.add('hidden'); authView.classList.remove('hidden'); }

    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function workerName(wid) {
        const w = state.workers.find(i => Number(i.id) === Number(wid));
        return w ? w.name : '未知施工员';
    }

    function updateState(data) {
        if (data.session) state.session = data.session;
        if (data.stats) state.stats = data.stats;
        if (data.workers) state.workers = data.workers;
        if (data.customers) state.customers = data.customers;
        if (data.images) state.images = data.images;
        adminGreeting.textContent = `${state.session?.username || '管理员'}，可统一管理施工员、客户与施工图片。`;
        renderStats(); renderWorkers(); renderCustomerFilterOptions(); renderImageFilterOptions(); renderCustomers(); renderImages();
    }

    function renderStats() {
        statsGrid.innerHTML = [
            { label: '施工员数量', value: state.stats.worker_count },
            { label: '客户数量', value: state.stats.customer_count },
            { label: '图片总数', value: state.stats.image_count }
        ].map(item => `
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">${item.label}</p>
                <p class="text-2xl font-bold text-gray-900">${item.value}</p>
            </div>
        `).join('');
    }

    function renderWorkers() {
        if (!state.workers.length) { workerList.innerHTML = '<div class="text-center py-8 text-sm text-gray-400">暂无施工员数据。</div>'; return; }
        workerList.innerHTML = state.workers.map(w => `
            <div class="bg-gray-50 rounded-xl px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-900">${escapeHtml(w.name)}</span>
                        <span class="text-xs ml-2 px-2 py-0.5 rounded-full font-medium ${String(w.show_phone)==='1'?'bg-green-50 text-[#07C160]':'bg-orange-50 text-orange-600'}">${String(w.show_phone)==='1'?'客户可见手机号':'客户不可见手机号'}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">手机号：${escapeHtml(w.phone)} · 创建：${escapeHtml(w.created_at)}</p>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="bg-white rounded-lg p-2 text-center"><p class="text-xs text-gray-400">客户数量</p><p class="text-lg font-bold text-gray-900">${w.customer_count}</p></div>
                    <div class="bg-white rounded-lg p-2 text-center"><p class="text-xs text-gray-400">图片数量</p><p class="text-lg font-bold text-gray-900">${w.image_count}</p></div>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <button class="text-xs text-[#576B95] bg-blue-50 rounded-lg px-3 py-1.5 hover:bg-blue-100 transition-colors" type="button" data-action="edit-worker" data-id="${w.id}">编辑</button>
                    <button class="text-xs text-red-600 bg-red-50 rounded-lg px-3 py-1.5 hover:bg-red-100 transition-colors" type="button" data-action="delete-worker" data-id="${w.id}">删除</button>
                </div>
            </div>
        `).join('');
    }

    function renderCustomerFilterOptions() {
        const sel = filters.customerWorker.value || '';
        filters.customerWorker.innerHTML = '<option value="">全部施工员</option>' + state.workers.map(w => `<option value="${w.id}">${escapeHtml(w.name)}</option>`).join('');
        filters.customerWorker.value = sel;
    }

    function renderImageFilterOptions() {
        const sw = filters.imageWorker.value || '';
        const sc = filters.imageCustomer.value || '';
        filters.imageWorker.innerHTML = '<option value="">全部施工员</option>' + state.workers.map(w => `<option value="${w.id}">${escapeHtml(w.name)}</option>`).join('');
        filters.imageWorker.value = sw;
        const opts = state.customers.filter(c => !sw || Number(c.worker_id) === Number(sw));
        filters.imageCustomer.innerHTML = '<option value="">全部客户</option>' + opts.map(c => `<option value="${c.id}">${escapeHtml(c.customer_phone)} / ${escapeHtml(c.address)}</option>`).join('');
        filters.imageCustomer.value = opts.some(c => Number(c.id) === Number(sc)) ? sc : '';
    }

    function filteredCustomers() {
        const wid = filters.customerWorker.value;
        const kw = filters.customerKeyword.value.trim().toLowerCase();
        return state.customers.filter(c => (!wid || Number(c.worker_id)===Number(wid)) && (!kw || `${c.customer_phone} ${c.address}`.toLowerCase().includes(kw)));
    }

    function filteredImages() {
        const wid = filters.imageWorker.value;
        const cid = filters.imageCustomer.value;
        return state.images.filter(i => (!wid || Number(i.worker_id)===Number(wid)) && (!cid || Number(i.customer_id)===Number(cid)));
    }

    function renderCustomers() {
        const list = filteredCustomers();
        if (!list.length) { customerList.innerHTML = '<div class="text-center py-8 text-sm text-gray-400">没有符合条件的客户。</div>'; return; }
        customerList.innerHTML = list.map(c => `
            <div class="bg-gray-50 rounded-xl px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 truncate">${escapeHtml(c.customer_phone)}</span>
                            <span class="text-xs bg-green-50 text-[#07C160] px-2 py-0.5 rounded-full font-medium shrink-0">${c.image_count}张</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 truncate">${escapeHtml(c.address)}</p>
                        <p class="text-xs text-gray-400 mt-0.5">施工员：${escapeHtml(c.worker_name)}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <button class="text-xs text-[#576B95] bg-blue-50 rounded-lg px-3 py-1.5 hover:bg-blue-100 transition-colors" type="button" data-action="view-customer-images" data-id="${c.id}">查看图片</button>
                    <button class="text-xs text-red-600 bg-red-50 rounded-lg px-3 py-1.5 hover:bg-red-100 transition-colors" type="button" data-action="delete-customer" data-id="${c.id}">删除客户</button>
                </div>
            </div>
        `).join('');
    }

    function renderImages() {
        const list = filteredImages();
        if (!list.length) { imageList.innerHTML = '<div class="text-center py-8 text-sm text-gray-400">没有符合条件的图片。</div>'; return; }
        imageList.innerHTML = list.map(img => `
            <div class="bg-gray-50 rounded-xl px-4 py-3">
                <div class="flex items-start gap-3">
                    <img class="w-20 h-20 object-cover rounded-lg cursor-pointer previewable shrink-0" src="${escapeHtml(img.thumbnail_path)}" data-src="${escapeHtml(img.image_path)}" data-title="施工图片" alt="施工图片" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(img.customer_phone)}</p>
                        <p class="text-xs text-gray-500 mt-0.5">${escapeHtml(img.address)}</p>
                        <p class="text-xs text-gray-400 mt-0.5">施工员：${escapeHtml(img.worker_name)}</p>
                        <p class="text-xs text-gray-400">上传：${escapeHtml(img.uploaded_at)}</p>
                    </div>
                    <button class="text-xs text-red-600 bg-red-50 rounded-lg px-3 py-1.5 hover:bg-red-100 transition-colors shrink-0" type="button" data-action="delete-image" data-id="${img.id}">删除</button>
                </div>
            </div>
        `).join('');
    }

    async function bootstrap() {
        setLoading(true, '正在加载后台数据...');
        try { updateState(await api('bootstrap')); showApp(); }
        catch (e) { showAuth(); showToast(e.message); }
        finally { setLoading(false); }
    }

    function resetWorkerForm(worker = null) {
        workerForm.form.reset();
        workerForm.id.value = worker ? worker.id : '';
        workerForm.name.value = worker ? worker.name : '';
        workerForm.phone.value = worker ? worker.phone : '';
        workerForm.showPhone.checked = worker ? String(worker.show_phone)==='1' : true;
        workerForm.password.value = '';
        workerForm.title.textContent = worker ? '编辑施工员' : '新增施工员';
        workerForm.help.textContent = worker ? '留空表示不修改登录密码，如需修改请填写至少 6 位新密码。' : '新增施工员时，密码至少 6 位。';
    }

    function showCustomerImages(cid) {
        const c = state.customers.find(i => Number(i.id)===Number(cid));
        if (!c) { showToast('客户不存在'); return; }
        $('customerImagesTitle').textContent = `客户施工图片 - ${c.customer_phone}`;
        const content = $('customerImagesContent');
        if (!c.images || !c.images.length) {
            content.innerHTML = '<div class="text-center py-8 text-sm text-gray-400">该客户暂未上传施工图片。</div>';
        } else {
            content.innerHTML = `
                <div class="space-y-3">
                    <div class="bg-orange-50 text-orange-700 text-xs rounded-lg px-3 py-2">客户地址：${escapeHtml(c.address)}</div>
                    <div class="grid grid-cols-3 gap-2">
                        ${c.images.map(img => `
                            <div class="relative overflow-hidden rounded-lg bg-gray-100 aspect-square cursor-pointer previewable" data-src="${escapeHtml(img.image_path)}" data-title="施工图片">
                                <img class="w-full h-full object-cover" src="${escapeHtml(img.thumbnail_path)}" alt="施工图片" loading="lazy">
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        openModal(customerImagesModal);
    }

    // Login
    $('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在登录...');
        try { await api('login', { username: $('adminUsername').value.trim(), password: $('adminPassword').value }); showToast('登录成功'); bootstrap(); }
        catch (err) { setLoading(false); showToast(err.message); }
    });

    // Logout
    $('logoutBtn').addEventListener('click', async () => {
        setLoading(true, '正在退出...');
        try { await api('logout'); showAuth(); showToast('已退出'); }
        catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Tabs
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(b => { b.classList.remove('bg-[#07C160]','text-white'); b.classList.add('text-gray-500'); });
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
            btn.classList.add('bg-[#07C160]','text-white'); btn.classList.remove('text-gray-500');
            $(`tab-${btn.dataset.tab}`).classList.remove('hidden');
        });
    });

    // Worker modal
    $('openWorkerModal').addEventListener('click', () => { resetWorkerForm(); openModal(workerModal); });

    // Close modals
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal($(btn.dataset.closeModal)));
    });

    [workerModal, customerImagesModal, previewModal].forEach(m => {
        m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
    });

    // Save worker
    workerForm.form.addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在保存施工员...');
        try {
            await api('save_worker', { id: workerForm.id.value, name: workerForm.name.value.trim(), phone: workerForm.phone.value.trim(), show_phone: workerForm.showPhone.checked?'1':'0', login_password: workerForm.password.value });
            closeModal(workerModal);
            await bootstrap();
            showToast('施工员信息已保存');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Worker list actions
    workerList.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        if (btn.dataset.action === 'edit-worker') {
            const w = state.workers.find(i => Number(i.id)===Number(btn.dataset.id));
            resetWorkerForm(w || null);
            openModal(workerModal);
        }
        if (btn.dataset.action === 'delete-worker') {
            if (!confirm('删除施工员后，其名下客户和图片将被一并删除，确认继续吗？')) return;
            setLoading(true, '正在删除施工员...');
            try { await api('delete_worker', { id: btn.dataset.id }); await bootstrap(); showToast('施工员已删除'); }
            catch (err) { showToast(err.message); }
            finally { setLoading(false); }
        }
    });

    // Customer filters
    $('applyCustomerFilter').addEventListener('click', renderCustomers);
    $('resetCustomerFilter').addEventListener('click', () => { filters.customerWorker.value=''; filters.customerKeyword.value=''; renderCustomers(); });
    $('applyImageFilter').addEventListener('click', renderImages);
    $('resetImageFilter').addEventListener('click', () => { filters.imageWorker.value=''; renderImageFilterOptions(); filters.imageCustomer.value=''; renderImages(); });
    filters.imageWorker.addEventListener('change', () => { renderImageFilterOptions(); renderImages(); });

    // Customer list actions
    customerList.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        if (btn.dataset.action === 'view-customer-images') { showCustomerImages(btn.dataset.id); }
        if (btn.dataset.action === 'delete-customer') {
            if (!confirm('删除客户后，该客户二维码和全部施工图片都会删除，确认继续吗？')) return;
            setLoading(true, '正在删除客户...');
            try { await api('delete_customer', { id: btn.dataset.id }); await bootstrap(); showToast('客户已删除'); }
            catch (err) { showToast(err.message); }
            finally { setLoading(false); }
        }
    });

    // Image list actions
    imageList.addEventListener('click', async (e) => {
        const prev = e.target.closest('.previewable');
        if (prev) { previewTitle.textContent = prev.dataset.title||'图片预览'; previewImage.src = prev.dataset.src||prev.getAttribute('src')||''; openModal(previewModal); return; }
        const btn = e.target.closest('[data-action]');
        if (!btn || btn.dataset.action !== 'delete-image') return;
        if (!confirm('确认删除这张图片吗？')) return;
        setLoading(true, '正在删除图片...');
        try { await api('delete_image', { id: btn.dataset.id }); await bootstrap(); showToast('图片已删除'); }
        catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Preview from customer images modal
    customerImagesModal.addEventListener('click', (e) => {
        const prev = e.target.closest('.previewable');
        if (!prev) return;
        previewTitle.textContent = prev.dataset.title||'图片预览';
        previewImage.src = prev.dataset.src||prev.getAttribute('src')||'';
        openModal(previewModal);
    });

    if (initialLoggedIn) bootstrap();
})();
</script>
</body>
</html>
