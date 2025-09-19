// ملف JavaScript الرئيسي لمشروع Silah

// إعدادات API
const API_BASE_URL = '../api';

// متغيرات عامة
let currentUser = null;
let currentAds = [];
let currentCategories = [];

// تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// تهيئة التطبيق
function initializeApp() {
    // التحقق من وجود مستخدم مسجل دخول
    checkAuthStatus();
    
    // تحميل الفئات
    loadCategories();
    
    // تحميل الإعلانات
    loadAds();
    
    // ربط الأحداث
    bindEvents();
}

// التحقق من حالة المصادقة
function checkAuthStatus() {
    const userData = localStorage.getItem('silah_user');
    if (userData) {
        currentUser = JSON.parse(userData);
        updateUIForLoggedInUser();
    } else {
        updateUIForGuestUser();
    }
}

// تحديث واجهة المستخدم للمستخدم المسجل دخول
function updateUIForLoggedInUser() {
    const authButtons = document.querySelector('.auth-buttons');
    if (authButtons) {
        authButtons.innerHTML = `
            <span>مرحباً، ${currentUser.username}</span>
            <a href="dashboard.html" class="btn btn-primary">لوحة التحكم</a>
            <button onclick="logout()" class="btn btn-secondary">تسجيل الخروج</button>
        `;
    }
}

// تحديث واجهة المستخدم للمستخدم الضيف
function updateUIForGuestUser() {
    const authButtons = document.querySelector('.auth-buttons');
    if (authButtons) {
        authButtons.innerHTML = `
            <a href="login.html" class="btn btn-secondary">تسجيل الدخول</a>
            <a href="register.html" class="btn btn-primary">إنشاء حساب</a>
        `;
    }
}

// تسجيل الخروج
function logout() {
    localStorage.removeItem('silah_user');
    currentUser = null;
    updateUIForGuestUser();
    showAlert('تم تسجيل الخروج بنجاح', 'success');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// ربط الأحداث
function bindEvents() {
    // نموذج البحث
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearch);
    }
    
    // نموذج التسجيل
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // نموذج تسجيل الدخول
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // نموذج إضافة إعلان
    const adForm = document.getElementById('adForm');
    if (adForm) {
        adForm.addEventListener('submit', handleAddAd);
    }
    
    // نموذج الرسائل
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', handleSendMessage);
    }
}

// معالجة البحث
function handleSearch(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const searchQuery = formData.get('search_query');
    const categoryId = formData.get('category_id');
    const minPrice = formData.get('min_price');
    const maxPrice = formData.get('max_price');
    const location = formData.get('location');
    
    const filters = {};
    if (searchQuery) filters.search_query = searchQuery;
    if (categoryId) filters.category_id = categoryId;
    if (minPrice) filters.min_price = minPrice;
    if (maxPrice) filters.max_price = maxPrice;
    if (location) filters.location = location;
    
    loadAds(filters);
}

// معالجة التسجيل
async function handleRegister(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const userData = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    // التحقق من تطابق كلمات المرور
    const confirmPassword = formData.get('confirm_password');
    if (userData.password !== confirmPassword) {
        showAlert('كلمات المرور غير متطابقة', 'danger');
        return;
    }
    
    try {
        showLoading();
        const response = await apiCall('auth.php?action=register', 'POST', userData);
        hideLoading();
        
        if (response.success) {
            showAlert('تم التسجيل بنجاح', 'success');
            localStorage.setItem('silah_user', JSON.stringify(response.data));
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            showAlert(response.message, 'danger');
        }
    } catch (error) {
        hideLoading();
        showAlert('حدث خطأ أثناء التسجيل', 'danger');
        console.error('Registration error:', error);
    }
}

// معالجة تسجيل الدخول
async function handleLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const userData = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    try {
        showLoading();
        const response = await apiCall('auth.php?action=login', 'POST', userData);
        hideLoading();
        
        if (response.success) {
            showAlert('تم تسجيل الدخول بنجاح', 'success');
            localStorage.setItem('silah_user', JSON.stringify(response.data));
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            showAlert(response.message, 'danger');
        }
    } catch (error) {
        hideLoading();
        showAlert('حدث خطأ أثناء تسجيل الدخول', 'danger');
        console.error('Login error:', error);
    }
}

// معالجة إضافة إعلان
async function handleAddAd(e) {
    e.preventDefault();
    
    if (!currentUser) {
        showAlert('يجب تسجيل الدخول أولاً', 'danger');
        return;
    }
    
    const formData = new FormData(e.target);
    const adData = {
        user_id: currentUser.user_id,
        category_id: formData.get('category_id'),
        title: formData.get('title'),
        description: formData.get('description'),
        price: formData.get('price'),
        location: formData.get('location'),
        image_url: formData.get('image_url') || ''
    };
    
    try {
        showLoading();
        const response = await apiCall('ads.php?action=create', 'POST', adData);
        hideLoading();
        
        if (response.success) {
            showAlert('تم إضافة الإعلان بنجاح', 'success');
            e.target.reset();
            loadAds(); // إعادة تحميل الإعلانات
        } else {
            showAlert(response.message, 'danger');
        }
    } catch (error) {
        hideLoading();
        showAlert('حدث خطأ أثناء إضافة الإعلان', 'danger');
        console.error('Add ad error:', error);
    }
}

// معالجة إرسال رسالة
async function handleSendMessage(e) {
    e.preventDefault();
    
    if (!currentUser) {
        showAlert('يجب تسجيل الدخول أولاً', 'danger');
        return;
    }
    
    const formData = new FormData(e.target);
    const messageData = {
        sender_id: currentUser.user_id,
        receiver_id: formData.get('receiver_id'),
        ad_id: formData.get('ad_id'),
        message_content: formData.get('message_content')
    };
    
    try {
        showLoading();
        const response = await apiCall('messages.php?action=send', 'POST', messageData);
        hideLoading();
        
        if (response.success) {
            showAlert('تم إرسال الرسالة بنجاح', 'success');
            e.target.reset();
            // إعادة تحميل الرسائل إذا كانت موجودة
            if (typeof loadMessages === 'function') {
                loadMessages();
            }
        } else {
            showAlert(response.message, 'danger');
        }
    } catch (error) {
        hideLoading();
        showAlert('حدث خطأ أثناء إرسال الرسالة', 'danger');
        console.error('Send message error:', error);
    }
}

// تحميل الفئات
async function loadCategories() {
    try {
        const response = await apiCall('ads.php?action=getCategories', 'GET');
        if (response.success) {
            currentCategories = response.data;
            updateCategoriesUI();
        }
    } catch (error) {
        console.error('Load categories error:', error);
    }
}

// تحديث واجهة الفئات
function updateCategoriesUI() {
    const categorySelects = document.querySelectorAll('.category-select');
    categorySelects.forEach(select => {
        select.innerHTML = '<option value="">جميع الفئات</option>';
        currentCategories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.category_id;
            option.textContent = category.name;
            select.appendChild(option);
        });
    });
}

// تحميل الإعلانات
async function loadAds(filters = {}) {
    try {
        showLoading();
        const queryParams = new URLSearchParams(filters).toString();
        const url = `ads.php?action=getAll${queryParams ? '&' + queryParams : ''}`;
        const response = await apiCall(url, 'GET');
        hideLoading();
        
        if (response.success) {
            currentAds = response.data;
            updateAdsUI();
        } else {
            showAlert('فشل في تحميل الإعلانات', 'danger');
        }
    } catch (error) {
        hideLoading();
        showAlert('حدث خطأ أثناء تحميل الإعلانات', 'danger');
        console.error('Load ads error:', error);
    }
}

// تحديث واجهة الإعلانات
function updateAdsUI() {
    const adsContainer = document.getElementById('adsContainer');
    if (!adsContainer) return;
    
    if (currentAds.length === 0) {
        adsContainer.innerHTML = '<div class="text-center"><p>لا توجد إعلانات</p></div>';
        return;
    }
    
    adsContainer.innerHTML = currentAds.map(ad => `
        <div class="col-md-4">
            <div class="ad-card">
                <div class="ad-image">
                    ${ad.image_url ? 
                        `<img src="${ad.image_url}" alt="${ad.title}" style="width: 100%; height: 200px; object-fit: cover;">` : 
                        '<span>لا توجد صورة</span>'
                    }
                </div>
                <div class="ad-content">
                    <h3 class="ad-title">${ad.title}</h3>
                    <div class="ad-price">${ad.price} ₪</div>
                    <div class="ad-location">📍 ${ad.location}</div>
                    <p class="ad-description">${ad.description}</p>
                    <div class="ad-meta">
                        <span>بواسطة: ${ad.username}</span>
                        <span>${formatDate(ad.created_at)}</span>
                    </div>
                    <div class="mt-3">
                        <a href="ad_details.html?id=${ad.ad_id}" class="btn btn-primary">عرض التفاصيل</a>
                        ${currentUser && currentUser.user_id !== ad.user_id ? 
                            `<button onclick="showMessageModal(${ad.ad_id}, ${ad.user_id})" class="btn btn-success">إرسال رسالة</button>` : 
                            ''
                        }
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// عرض نافذة الرسالة
function showMessageModal(adId, receiverId) {
    if (!currentUser) {
        showAlert('يجب تسجيل الدخول أولاً', 'danger');
        return;
    }
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>إرسال رسالة</h3>
                <button onclick="closeModal()" class="btn btn-secondary">×</button>
            </div>
            <div class="modal-body">
                <form id="quickMessageForm">
                    <input type="hidden" name="receiver_id" value="${receiverId}">
                    <input type="hidden" name="ad_id" value="${adId}">
                    <div class="form-group">
                        <label class="form-label">الرسالة</label>
                        <textarea name="message_content" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">إرسال</button>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // ربط حدث النموذج
    document.getElementById('quickMessageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleSendMessage(e);
        closeModal();
    });
}

// إغلاق النافذة المنبثقة
function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.remove();
    }
}

// استدعاء API
async function apiCall(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE_URL}/${endpoint}`;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    return await response.json();
}

// عرض التنبيه
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    alertContainer.appendChild(alert);
    
    // إزالة التنبيه بعد 5 ثوان
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// إنشاء حاوي التنبيهات
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.maxWidth = '400px';
    document.body.appendChild(container);
    return container;
}

// عرض التحميل
function showLoading() {
    const loading = document.getElementById('loadingIndicator') || createLoadingIndicator();
    loading.style.display = 'flex';
}

// إخفاء التحميل
function hideLoading() {
    const loading = document.getElementById('loadingIndicator');
    if (loading) {
        loading.style.display = 'none';
    }
}

// إنشاء مؤشر التحميل
function createLoadingIndicator() {
    const loading = document.createElement('div');
    loading.id = 'loadingIndicator';
    loading.className = 'loading';
    loading.innerHTML = '<div class="spinner"></div>';
    loading.style.position = 'fixed';
    loading.style.top = '0';
    loading.style.left = '0';
    loading.style.width = '100%';
    loading.style.height = '100%';
    loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
    loading.style.zIndex = '9999';
    loading.style.display = 'none';
    document.body.appendChild(loading);
    return loading;
}

// تنسيق التاريخ
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// وظائف مساعدة إضافية
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

// إضافة أنماط CSS للنوافذ المنبثقة
const modalStyles = `
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    }
    
    .modal-content {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }
`;

// إضافة الأنماط إلى الصفحة
const styleSheet = document.createElement('style');
styleSheet.textContent = modalStyles;
document.head.appendChild(styleSheet);

