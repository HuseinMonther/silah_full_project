<?php
/**
 * API المستخدمين لمشروع Silah
 */

// تضمين الملفات المطلوبة
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/functions.php';

// تعيين رؤوس CORS
setCorsHeaders();

// الحصول على طريقة الطلب
$request_method = $_SERVER["REQUEST_METHOD"];

// إنشاء اتصال قاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// إنشاء كائن المستخدم
$user = new User($db);

// تحديد الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($request_method) {
    case 'GET':
        if ($action == 'get') {
            getUser($user);
        } elseif ($action == 'getAll') {
            getAllUsers($user);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    case 'POST':
        if ($action == 'update') {
            updateUser($user);
        } elseif ($action == 'delete') {
            deleteUser($user);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    default:
        jsonResponse(false, 'طريقة طلب غير مدعومة');
        break;
}

/**
 * الحصول على تفاصيل المستخدم
 */
function getUser($user) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$user_id) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $user_data = $user->getUserById($user_id);
    
    if ($user_data) {
        jsonResponse(true, 'تم الحصول على بيانات المستخدم بنجاح', $user_data);
    } else {
        jsonResponse(false, 'المستخدم غير موجود');
    }
}

/**
 * الحصول على جميع المستخدمين (للمسؤول فقط)
 */
function getAllUsers($user) {
    // هنا يجب التحقق من صلاحيات المسؤول
    // في التطبيق الحقيقي، يجب التحقق من الرمز المميز (Token)
    
    $users_data = $user->getAllUsers();
    
    if ($users_data) {
        jsonResponse(true, 'تم الحصول على قائمة المستخدمين بنجاح', $users_data);
    } else {
        jsonResponse(false, 'لا توجد بيانات مستخدمين');
    }
}

/**
 * تحديث ملف تعريف المستخدم
 */
function updateUser($user) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['user_id', 'username', 'email'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $user_id = (int)$data['user_id'];
    $username = sanitizeInput($data['username']);
    $email = sanitizeInput($data['email']);
    $password = isset($data['password']) ? $data['password'] : '';
    
    // التحقق من صحة البريد الإلكتروني
    if (!validateEmail($email)) {
        jsonResponse(false, 'البريد الإلكتروني غير صحيح');
        return;
    }
    
    // التحقق من طول كلمة المرور إذا تم توفيرها
    if (!empty($password) && strlen($password) < 6) {
        jsonResponse(false, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        return;
    }
    
    // التحقق من وجود المستخدم
    $existing_user = $user->getUserById($user_id);
    if (!$existing_user) {
        jsonResponse(false, 'المستخدم غير موجود');
        return;
    }
    
    // التحقق من عدم وجود اسم المستخدم لدى مستخدم آخر
    if ($existing_user['username'] !== $username && $user->usernameExists($username)) {
        jsonResponse(false, 'اسم المستخدم موجود بالفعل');
        return;
    }
    
    // التحقق من عدم وجود البريد الإلكتروني لدى مستخدم آخر
    if ($existing_user['email'] !== $email && $user->emailExists($email)) {
        jsonResponse(false, 'البريد الإلكتروني موجود بالفعل');
        return;
    }
    
    // تعيين بيانات المستخدم
    $user->user_id = $user_id;
    $user->username = $username;
    $user->email = $email;
    $user->password = $password;
    
    // محاولة التحديث
    if ($user->updateProfile()) {
        jsonResponse(true, 'تم تحديث الملف الشخصي بنجاح');
    } else {
        jsonResponse(false, 'فشل في تحديث الملف الشخصي');
    }
}

/**
 * حذف مستخدم (للمسؤول فقط)
 */
function deleteUser($user) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود معرف المستخدم
    if (!isset($data['user_id']) || empty($data['user_id'])) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $user_id = (int)$data['user_id'];
    
    // هنا يجب التحقق من صلاحيات المسؤول
    // في التطبيق الحقيقي، يجب التحقق من الرمز المميز (Token)
    
    // محاولة الحذف
    if ($user->deleteUser($user_id)) {
        jsonResponse(true, 'تم حذف المستخدم بنجاح');
    } else {
        jsonResponse(false, 'فشل في حذف المستخدم');
    }
}
?>

