<?php
declare(strict_types=1);

require_once __DIR__ . '/api_common.php';

db_connect();

$workerLoggedIn = is_worker_logged_in();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?= e(app_name()) ?> - 施工员端</title>
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
        .switch { width: 51px; height: 31px; appearance: none; background: #E5E5E5; border-radius: 999px; position: relative; transition: background .2s; cursor: pointer; }
        .switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 27px; height: 27px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        .switch:checked { background: #07C160; }
        .switch:checked::after { transform: translateX(20px); }
        input:focus, textarea:focus, select:focus { outline: none; }
    </style>
</head>
<body class="bg-[#EDEDED] min-h-screen">

<!-- ========== LOGIN VIEW ========== -->
<section class="<?= $workerLoggedIn ? 'hidden' : '' ?>" id="authView">
    <div class="max-w-lg mx-auto px-4 pt-12 pb-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#07C160] text-white text-2xl font-bold mb-4 shadow-lg shadow-green-200">
                <?= mb_substr(e(app_name()), 0, 1) ?>
            </div>
            <h1 class="text-xl font-bold text-gray-900"><?= e(app_name()) ?></h1>
            <p class="text-sm text-gray-500 mt-2">施工员登录</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm px-5 py-6">
            <form class="space-y-4" id="loginForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="loginPhone">手机号</label>
                    <input id="loginPhone" name="phone" type="tel" inputmode="numeric" maxlength="11" placeholder="请输入施工员手机号" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="loginPassword">密码</label>
                    <input id="loginPassword" name="password" type="password" placeholder="请输入登录密码" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <button type="submit" class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-base transition-colors">登录施工员端</button>
            </form>

            <div class="mt-5 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400 leading-relaxed">
                    如果忘记密码请联系管理员帮您处理！建议在首次登录后立即修改密码。
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ========== APP VIEW ========== -->
<main class="max-w-lg mx-auto px-4 pt-6 pb-8 <?= $workerLoggedIn ? '' : 'hidden' ?>" id="appView">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#07C160] text-white text-sm font-bold">
                <?= mb_substr(e(app_name()), 0, 1) ?>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">施工员工作台</h1>
                <p class="text-xs text-gray-400" id="workerGreeting">加载中...</p>
            </div>
        </div>
        <button id="logoutBtn" type="button" class="text-sm text-gray-500 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 active:bg-gray-100 transition-colors">退出</button>
    </div>

    <!-- Tabs -->
    <div class="flex bg-white rounded-full p-1 shadow-sm mb-4">
        <button class="tab-button flex-1 py-2.5 rounded-full text-sm font-medium transition-colors bg-[#07C160] text-white active" type="button" data-tab="customers">我的客户</button>
        <button class="tab-button flex-1 py-2.5 rounded-full text-sm font-medium text-gray-500 transition-colors" type="button" data-tab="settings">个人设置</button>
    </div>

    <!-- Tab: Customers -->
    <section class="tab-panel space-y-3" id="tab-customers">
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">客户列表</h2>
                    <p class="text-xs text-gray-400 mt-0.5">新增客户后自动生成专属二维码</p>
                </div>
                <button id="openCustomerModal" type="button" class="text-sm bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white rounded-lg px-3 py-2 font-medium transition-colors">新增客户</button>
            </div>
            <div class="space-y-2" id="customerList"></div>
        </div>
    </section>

    <!-- Tab: Settings -->
    <section class="tab-panel space-y-3 hidden" id="tab-settings">
        <!-- Profile -->
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900 mb-3">个人资料</h2>
            <p class="text-xs text-gray-400 -mt-2 mb-4">可设置是否向客户公开手机号</p>

            <form class="space-y-4" id="profileForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="profileName">姓名</label>
                    <input id="profileName" name="name" type="text" placeholder="请输入姓名" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="profilePhone">手机号</label>
                    <input id="profilePhone" name="phone" type="tel" maxlength="11" inputmode="numeric" placeholder="请输入手机号" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <label class="flex items-center justify-between py-2 cursor-pointer" for="profileShowPhone">
                    <div>
                        <span class="text-sm font-medium text-gray-700">对客户展示手机号</span>
                        <p class="text-xs text-gray-400 mt-0.5">关闭后用户端仅显示姓名，不显示手机号</p>
                    </div>
                    <input id="profileShowPhone" name="show_phone" type="checkbox" checked class="switch">
                </label>
                <button type="submit" class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-sm transition-colors">保存个人资料</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900 mb-3">修改密码</h2>
            <p class="text-xs text-gray-400 -mt-2 mb-4">新密码至少 6 位</p>

            <form class="space-y-4" id="passwordForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="oldPassword">旧密码</label>
                    <input id="oldPassword" name="old_password" type="password" placeholder="请输入旧密码" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="newPassword">新密码</label>
                    <input id="newPassword" name="new_password" type="password" placeholder="请输入新密码" required
                           class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
                </div>
                <button type="submit" class="w-full h-12 rounded-lg bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 font-medium text-sm transition-colors">更新密码</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-400 leading-relaxed">
        <p class="font-medium text-gray-500"><?= e(app_name()) ?></p>
        <p>施工数据仅对当前登录施工员可见</p>
    </div>
</main>

<!-- ========== MODALS ========== -->

<!-- Add Customer Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" id="customerModal">
    <div class="w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">新增客户</h3>
            <button type="button" data-close-modal="customerModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form class="p-5 space-y-4" id="customerForm">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="customerPhone">客户手机号</label>
                <input id="customerPhone" name="customer_phone" type="tel" maxlength="11" inputmode="numeric" placeholder="请输入客户手机号" required
                       class="w-full h-11 px-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="customerAddress">施工地址</label>
                <textarea id="customerAddress" name="address" placeholder="请输入施工地址" required rows="3"
                          class="w-full px-3 py-3 text-base text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:border-[#07C160] focus:bg-white transition-colors placeholder:text-gray-300 resize-none"></textarea>
            </div>
            <button type="submit" class="w-full h-12 rounded-lg bg-[#07C160] hover:bg-[#06AD56] active:bg-[#059A4C] text-white font-medium text-sm transition-colors">保存客户并生成二维码</button>
        </form>
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
    const state = { worker: null, customers: [] };
    const initialLoggedIn = <?= $workerLoggedIn ? 'true' : 'false' ?>;

    const $ = (id) => document.getElementById(id);
    const authView = $('authView');
    const appView = $('appView');
    const customerList = $('customerList');
    const workerGreeting = $('workerGreeting');
    const loadingMask = $('loadingMask');
    const loadingText = $('loadingText');
    const toast = $('toast');
    const customerModal = $('customerModal');
    const previewModal = $('previewModal');
    const previewImage = $('previewImage');
    const previewTitle = $('previewTitle');
    const loginPhone = $('loginPhone');
    const loginPassword = $('loginPassword');
    const profileName = $('profileName');
    const profilePhone = $('profilePhone');
    const profileShowPhone = $('profileShowPhone');
    const oldPassword = $('oldPassword');
    const newPassword = $('newPassword');
    const customerPhone = $('customerPhone');
    const customerAddress = $('customerAddress');

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

    function showApp() { authView.classList.add('hidden'); appView.classList.remove('hidden'); }
    function showAuth() { appView.classList.add('hidden'); authView.classList.remove('hidden'); }

    function openModal(m) { m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); document.body.style.overflow = ''; }

    function fillWorkerProfile() {
        if (!state.worker) return;
        workerGreeting.textContent = `${state.worker.name}，可在此维护客户资料与施工图片`;
        profileName.value = state.worker.name || '';
        profilePhone.value = state.worker.phone || '';
        profileShowPhone.checked = String(state.worker.show_phone) === '1';
    }

    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderCustomers() {
        if (!state.customers.length) {
            customerList.innerHTML = '<div class="text-center py-8 text-sm text-gray-400">还没有客户，点击右上角"新增客户"开始录入</div>';
            return;
        }
        customerList.innerHTML = state.customers.map(c => {
            const cnt = c.image_count ?? (Array.isArray(c.images) ? c.images.length : 0);
            return `
                <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 active:bg-gray-100 transition-colors">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 truncate">${escapeHtml(c.customer_phone)}</span>
                            <span class="text-xs bg-green-50 text-[#07C160] px-2 py-0.5 rounded-full font-medium shrink-0">${cnt}张</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 truncate">${escapeHtml(c.address)}</p>
                        <p class="text-xs text-gray-400 mt-0.5">${escapeHtml(c.created_at)}</p>
                    </div>
                    <a href="customer_detail.php?customer_id=${c.id}" class="shrink-0 ml-3 text-xs text-[#576B95] border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-100 active:bg-gray-200 transition-colors">查看详情</a>
                </div>
            `;
        }).join('');
    }

    function updateState(data) {
        if (data.worker) state.worker = data.worker;
        if (data.customers) state.customers = data.customers;
        fillWorkerProfile();
        renderCustomers();
    }

    async function bootstrap() {
        setLoading(true, '正在加载数据...');
        try {
            updateState(await api('bootstrap'));
            showApp();
        } catch (e) { showAuth(); showToast(e.message); }
        finally { setLoading(false); }
    }

    // Login
    $('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在登录...');
        try {
            updateState(await api('login', { phone: loginPhone.value.trim(), password: loginPassword.value }));
            e.target.reset();
            showApp();
            showToast('登录成功');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Logout
    $('logoutBtn').addEventListener('click', async () => {
        setLoading(true, '正在退出...');
        try {
            await api('logout');
            showAuth();
            state.worker = null;
            state.customers = [];
            customerList.innerHTML = '';
            showToast('已退出');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Tabs
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(b => { b.classList.remove('bg-[#07C160]', 'text-white'); b.classList.add('text-gray-500'); });
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
            btn.classList.add('bg-[#07C160]', 'text-white');
            btn.classList.remove('text-gray-500');
            $(`tab-${btn.dataset.tab}`).classList.remove('hidden');
        });
    });

    // Open customer modal
    $('openCustomerModal').addEventListener('click', () => openModal(customerModal));

    // Close modals
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal($(btn.dataset.closeModal)));
    });

    [customerModal, previewModal].forEach(m => {
        m.addEventListener('click', (e) => { if (e.target === m) { closeModal(m); if (m === previewModal) previewImage.src = ''; } });
    });

    // Add customer
    $('customerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在保存客户...');
        try {
            updateState(await api('add_customer', { customer_phone: customerPhone.value.trim(), address: customerAddress.value.trim() }));
            closeModal(customerModal);
            e.target.reset();
            showToast('客户已保存');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Update profile
    $('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在保存资料...');
        try {
            updateState(await api('update_profile', { name: profileName.value.trim(), phone: profilePhone.value.trim(), show_phone: profileShowPhone.checked ? '1' : '0' }));
            showToast('资料已保存');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    // Change password
    $('passwordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true, '正在更新密码...');
        try {
            await api('change_password', { old_password: oldPassword.value, new_password: newPassword.value });
            e.target.reset();
            showToast('密码已修改');
        } catch (err) { showToast(err.message); }
        finally { setLoading(false); }
    });

    if (initialLoggedIn) bootstrap();
})();
</script>
</body>
</html>
