<?php
/**
 * API الإعلانات لمشروع Silah
 */

// تضمين الملفات المطلوبة
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../classes/Ad.php';
require_once '../includes/functions.php';

// تعيين رؤوس CORS
setCorsHeaders();

// الحصول على طريقة الطلب
$request_method = $_SERVER["REQUEST_METHOD"];

// إنشاء اتصال قاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// إنشاء كائن الإعلان
$ad = new Ad($db);

// تحديد الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($request_method) {
    case 'GET':
        if ($action == 'getAll') {
            getAllAds($ad);
        } elseif ($action == 'get') {
            getAd($ad);
        } elseif ($action == 'getUserAds') {
            getUserAds($ad);
        } elseif ($action == 'getCategories') {
            getCategories($ad);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    case 'POST':
        if ($action == 'create') {
            createAd($ad);
        } elseif ($action == 'update') {
            updateAd($ad);
        } elseif ($action == 'delete') {
            deleteAd($ad);
        } elseif ($action == 'changeStatus') {
            changeAdStatus($ad);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    default:
        jsonResponse(false, 'طريقة طلب غير مدعومة');
        break;
}

/**
 * الحصول على جميع الإعلانات مع إمكانية التصفية
 */
function getAllAds($ad) {
    // الحصول على المرشحات من معاملات الاستعلام
    $filters = [];
    
    if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
        $filters['category_id'] = (int)$_GET['category_id'];
    }
    
    if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
        $filters['search_query'] = sanitizeInput($_GET['search_query']);
    }
    
    if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
        $filters['min_price'] = (float)$_GET['min_price'];
    }
    
    if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
        $filters['max_price'] = (float)$_GET['max_price'];
    }
    
    if (isset($_GET['location']) && !empty($_GET['location'])) {
        $filters['location'] = sanitizeInput($_GET['location']);
    }
    
    if (isset($_GET['limit']) && !empty($_GET['limit'])) {
        $filters['limit'] = (int)$_GET['limit'];
    }
    
    $ads_data = $ad->getAll($filters);
    
    if ($ads_data) {
        jsonResponse(true, 'تم الحصول على الإعلانات بنجاح', $ads_data);
    } else {
        jsonResponse(true, 'لا توجد إعلانات', []);
    }
}

/**
 * الحصول على إعلان واحد بالتفصيل
 */
function getAd($ad) {
    $ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : null;
    
    if (!$ad_id) {
        jsonResponse(false, 'معرف الإعلان مطلوب');
        return;
    }
    
    $ad_data = $ad->getById($ad_id);
    
    if ($ad_data) {
        jsonResponse(true, 'تم الحصول على تفاصيل الإعلان بنجاح', $ad_data);
    } else {
        jsonResponse(false, 'الإعلان غير موجود');
    }
}

/**
 * الحصول على إعلانات المستخدم
 */
function getUserAds($ad) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$user_id) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $ads_data = $ad->getUserAds($user_id);
    
    if ($ads_data) {
        jsonResponse(true, 'تم الحصول على إعلانات المستخدم بنجاح', $ads_data);
    } else {
        jsonResponse(true, 'لا توجد إعلانات للمستخدم', []);
    }
}

/**
 * الحصول على الفئات
 */
function getCategories($ad) {
    $categories_data = $ad->getCategories();
    
    if ($categories_data) {
        jsonResponse(true, 'تم الحصول على الفئات بنجاح', $categories_data);
    } else {
        jsonResponse(true, 'لا توجد فئات', []);
    }
}

/**
 * إنشاء إعلان جديد
 */
function createAd($ad) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['user_id', 'category_id', 'title', 'description', 'price', 'location'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $user_id = (int)$data['user_id'];
    $category_id = (int)$data['category_id'];
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $price = (float)$data['price'];
    $location = sanitizeInput($data['location']);
    $image_url = isset($data['image_url']) ? sanitizeInput($data['image_url']) : '';
    
    // التحقق من صحة البيانات
    if (strlen($title) < 3) {
        jsonResponse(false, 'عنوان الإعلان يجب أن يكون 3 أحرف على الأقل');
        return;
    }
    
    if (strlen($description) < 10) {
        jsonResponse(false, 'وصف الإعلان يجب أن يكون 10 أحرف على الأقل');
        return;
    }
    
    if ($price <= 0) {
        jsonResponse(false, 'السعر يجب أن يكون أكبر من صفر');
        return;
    }
    
    // تعيين بيانات الإعلان
    $ad->user_id = $user_id;
    $ad->category_id = $category_id;
    $ad->title = $title;
    $ad->description = $description;
    $ad->price = $price;
    $ad->location = $location;
    $ad->image_url = $image_url;
    $ad->status = 'active';
    
    // محاولة الإنشاء
    if ($ad->create()) {
        jsonResponse(true, 'تم إنشاء الإعلان بنجاح', ['ad_id' => $ad->ad_id]);
    } else {
        jsonResponse(false, 'فشل في إنشاء الإعلان');
    }
}

/**
 * تحديث إعلان
 */
function updateAd($ad) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['ad_id', 'user_id', 'category_id', 'title', 'description', 'price', 'location'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $ad_id = (int)$data['ad_id'];
    $user_id = (int)$data['user_id'];
    $category_id = (int)$data['category_id'];
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $price = (float)$data['price'];
    $location = sanitizeInput($data['location']);
    $image_url = isset($data['image_url']) ? sanitizeInput($data['image_url']) : '';
    
    // التحقق من ملكية الإعلان
    if (!$ad->isOwner($ad_id, $user_id)) {
        jsonResponse(false, 'ليس لديك صلاحية لتعديل هذا الإعلان');
        return;
    }
    
    // التحقق من صحة البيانات
    if (strlen($title) < 3) {
        jsonResponse(false, 'عنوان الإعلان يجب أن يكون 3 أحرف على الأقل');
        return;
    }
    
    if (strlen($description) < 10) {
        jsonResponse(false, 'وصف الإعلان يجب أن يكون 10 أحرف على الأقل');
        return;
    }
    
    if ($price <= 0) {
        jsonResponse(false, 'السعر يجب أن يكون أكبر من صفر');
        return;
    }
    
    // تعيين بيانات الإعلان
    $ad->ad_id = $ad_id;
    $ad->user_id = $user_id;
    $ad->category_id = $category_id;
    $ad->title = $title;
    $ad->description = $description;
    $ad->price = $price;
    $ad->location = $location;
    $ad->image_url = $image_url;
    
    // محاولة التحديث
    if ($ad->update()) {
        jsonResponse(true, 'تم تحديث الإعلان بنجاح');
    } else {
        jsonResponse(false, 'فشل في تحديث الإعلان');
    }
}

/**
 * حذف إعلان
 */
function deleteAd($ad) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['ad_id', 'user_id'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    $ad_id = (int)$data['ad_id'];
    $user_id = (int)$data['user_id'];
    
    // التحقق من ملكية الإعلان
    if (!$ad->isOwner($ad_id, $user_id)) {
        jsonResponse(false, 'ليس لديك صلاحية لحذف هذا الإعلان');
        return;
    }
    
    // محاولة الحذف
    if ($ad->delete($ad_id, $user_id)) {
        jsonResponse(true, 'تم حذف الإعلان بنجاح');
    } else {
        jsonResponse(false, 'فشل في حذف الإعلان');
    }
}

/**
 * تغيير حالة الإعلان (للمسؤول)
 */
function changeAdStatus($ad) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['ad_id', 'status'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    $ad_id = (int)$data['ad_id'];
    $status = sanitizeInput($data['status']);
    
    // التحقق من صحة الحالة
    $valid_statuses = ['active', 'inactive', 'deleted'];
    if (!in_array($status, $valid_statuses)) {
        jsonResponse(false, 'حالة الإعلان غير صحيحة');
        return;
    }
    
    // هنا يجب التحقق من صلاحيات المسؤول
    // في التطبيق الحقيقي، يجب التحقق من الرمز المميز (Token)
    
    // محاولة تغيير الحالة
    if ($ad->changeStatus($ad_id, $status)) {
        jsonResponse(true, 'تم تغيير حالة الإعلان بنجاح');
    } else {
        jsonResponse(false, 'فشل في تغيير حالة الإعلان');
    }
}
?>

