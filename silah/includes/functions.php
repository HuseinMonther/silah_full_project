<?php
/**
 * وظائف مساعدة لمشروع Silah
 */

/**
 * تعيين رؤوس CORS للسماح بالطلبات من الفرونت إند
 */
function setCorsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    // التعامل مع طلبات OPTIONS
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * إرجاع استجابة JSON
 */
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * إنشاء رمز مميز (Token) بسيط
 */
function generateToken($user_id) {
    return base64_encode($user_id . ':' . time() . ':' . bin2hex(random_bytes(16)));
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * تنظيف البيانات المدخلة
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * التحقق من وجود المعاملات المطلوبة
 */
function validateRequiredFields($data, $required_fields) {
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    return $missing_fields;
}

/**
 * رفع الصور
 */
function uploadImage($file, $upload_dir = '../frontend/images/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'معاملات الملف غير صحيحة'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'لم يتم اختيار ملف'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
        default:
            return ['success' => false, 'message' => 'خطأ غير معروف في رفع الملف'];
    }
    
    // التحقق من نوع الملف
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم'];
    }
    
    // التحقق من حجم الملف (5MB كحد أقصى)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)'];
    }
    
    // إنشاء اسم ملف فريد
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'فشل في رفع الملف'];
    }
}

/**
 * تسجيل الأخطاء
 */
function logError($message, $file = 'error.log') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($file, $log_message, FILE_APPEND | LOCK_EX);
}
?>

