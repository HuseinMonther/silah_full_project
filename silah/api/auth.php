<?php
/**
 * API المصادقة لمشروع Silah
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
    case 'POST':
        if ($action == 'register') {
            register($user);
        } elseif ($action == 'login') {
            login($user);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    default:
        jsonResponse(false, 'طريقة طلب غير مدعومة');
        break;
}

/**
 * تسجيل مستخدم جديد
 */
function register($user) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['username', 'email', 'password'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $username = sanitizeInput($data['username']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    
    // التحقق من صحة البريد الإلكتروني
    if (!validateEmail($email)) {
        jsonResponse(false, 'البريد الإلكتروني غير صحيح');
        return;
    }
    
    // التحقق من طول كلمة المرور
    if (strlen($password) < 6) {
        jsonResponse(false, 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        return;
    }
    
    // التحقق من وجود اسم المستخدم
    if ($user->usernameExists($username)) {
        jsonResponse(false, 'اسم المستخدم موجود بالفعل');
        return;
    }
    
    // التحقق من وجود البريد الإلكتروني
    if ($user->emailExists($email)) {
        jsonResponse(false, 'البريد الإلكتروني موجود بالفعل');
        return;
    }
    
    // تعيين بيانات المستخدم
    $user->username = $username;
    $user->email = $email;
    $user->password = $password;
    $user->role = 'user'; // الدور الافتراضي
    
    // محاولة التسجيل
    if ($user->register()) {
        $token = generateToken($user->user_id);
        
        jsonResponse(true, 'تم التسجيل بنجاح', [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'token' => $token
        ]);
    } else {
        jsonResponse(false, 'فشل في التسجيل، حاول مرة أخرى');
    }
}

/**
 * تسجيل دخول المستخدم
 */
function login($user) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['email', 'password'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    
    // التحقق من صحة البريد الإلكتروني
    if (!validateEmail($email)) {
        jsonResponse(false, 'البريد الإلكتروني غير صحيح');
        return;
    }
    
    // محاولة تسجيل الدخول
    if ($user->login($email, $password)) {
        $token = generateToken($user->user_id);
        
        jsonResponse(true, 'تم تسجيل الدخول بنجاح', [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'token' => $token
        ]);
    } else {
        jsonResponse(false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة');
    }
}
?>

