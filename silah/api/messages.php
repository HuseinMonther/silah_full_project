<?php
/**
 * API الرسائل لمشروع Silah
 */

// تضمين الملفات المطلوبة
require_once '../config/database.php';
require_once '../classes/Message.php';
require_once '../includes/functions.php';

// تعيين رؤوس CORS
setCorsHeaders();

// الحصول على طريقة الطلب
$request_method = $_SERVER["REQUEST_METHOD"];

// إنشاء اتصال قاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// إنشاء كائن الرسالة
$message = new Message($db);

// تحديد الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($request_method) {
    case 'GET':
        if ($action == 'get') {
            getUserMessages($message);
        } elseif ($action == 'getConversation') {
            getConversation($message);
        } elseif ($action == 'getAdMessages') {
            getAdMessages($message);
        } elseif ($action == 'getConversationsList') {
            getConversationsList($message);
        } elseif ($action == 'getUnreadCount') {
            getUnreadCount($message);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    case 'POST':
        if ($action == 'send') {
            sendMessage($message);
        } elseif ($action == 'markAsRead') {
            markAsRead($message);
        } elseif ($action == 'delete') {
            deleteMessage($message);
        } else {
            jsonResponse(false, 'إجراء غير صحيح');
        }
        break;
    
    default:
        jsonResponse(false, 'طريقة طلب غير مدعومة');
        break;
}

/**
 * الحصول على رسائل المستخدم
 */
function getUserMessages($message) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$user_id) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $messages_data = $message->getUserMessages($user_id);
    
    if ($messages_data) {
        jsonResponse(true, 'تم الحصول على الرسائل بنجاح', $messages_data);
    } else {
        jsonResponse(true, 'لا توجد رسائل', []);
    }
}

/**
 * الحصول على محادثة بين مستخدمين حول إعلان معين
 */
function getConversation($message) {
    $user1_id = isset($_GET['user1_id']) ? (int)$_GET['user1_id'] : null;
    $user2_id = isset($_GET['user2_id']) ? (int)$_GET['user2_id'] : null;
    $ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : null;
    
    if (!$user1_id || !$user2_id || !$ad_id) {
        jsonResponse(false, 'معرفات المستخدمين والإعلان مطلوبة');
        return;
    }
    
    $conversation_data = $message->getConversation($user1_id, $user2_id, $ad_id);
    
    if ($conversation_data) {
        jsonResponse(true, 'تم الحصول على المحادثة بنجاح', $conversation_data);
    } else {
        jsonResponse(true, 'لا توجد رسائل في هذه المحادثة', []);
    }
}

/**
 * الحصول على رسائل إعلان معين
 */
function getAdMessages($message) {
    $ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : null;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$ad_id || !$user_id) {
        jsonResponse(false, 'معرف الإعلان والمستخدم مطلوبان');
        return;
    }
    
    $messages_data = $message->getAdMessages($ad_id, $user_id);
    
    if ($messages_data) {
        jsonResponse(true, 'تم الحصول على رسائل الإعلان بنجاح', $messages_data);
    } else {
        jsonResponse(true, 'لا توجد رسائل لهذا الإعلان', []);
    }
}

/**
 * الحصول على قائمة المحادثات للمستخدم
 */
function getConversationsList($message) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$user_id) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $conversations_data = $message->getConversationsList($user_id);
    
    if ($conversations_data) {
        jsonResponse(true, 'تم الحصول على قائمة المحادثات بنجاح', $conversations_data);
    } else {
        jsonResponse(true, 'لا توجد محادثات', []);
    }
}

/**
 * الحصول على عدد الرسائل غير المقروءة
 */
function getUnreadCount($message) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$user_id) {
        jsonResponse(false, 'معرف المستخدم مطلوب');
        return;
    }
    
    $unread_count = $message->getUnreadCount($user_id);
    
    jsonResponse(true, 'تم الحصول على عدد الرسائل غير المقروءة بنجاح', ['unread_count' => $unread_count]);
}

/**
 * إرسال رسالة جديدة
 */
function sendMessage($message) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['sender_id', 'receiver_id', 'ad_id', 'message_content'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    // تنظيف البيانات
    $sender_id = (int)$data['sender_id'];
    $receiver_id = (int)$data['receiver_id'];
    $ad_id = (int)$data['ad_id'];
    $message_content = sanitizeInput($data['message_content']);
    
    // التحقق من صحة البيانات
    if (strlen($message_content) < 1) {
        jsonResponse(false, 'محتوى الرسالة لا يمكن أن يكون فارغاً');
        return;
    }
    
    if ($sender_id == $receiver_id) {
        jsonResponse(false, 'لا يمكن إرسال رسالة لنفسك');
        return;
    }
    
    // التحقق من صحة الرسالة
    if (!$message->validateMessage($sender_id, $receiver_id, $ad_id)) {
        jsonResponse(false, 'لا يمكن إرسال رسالة لهذا الإعلان');
        return;
    }
    
    // تعيين بيانات الرسالة
    $message->sender_id = $sender_id;
    $message->receiver_id = $receiver_id;
    $message->ad_id = $ad_id;
    $message->message_content = $message_content;
    
    // محاولة الإرسال
    if ($message->send()) {
        jsonResponse(true, 'تم إرسال الرسالة بنجاح', ['message_id' => $message->message_id]);
    } else {
        jsonResponse(false, 'فشل في إرسال الرسالة');
    }
}

/**
 * تحديد الرسائل كمقروءة
 */
function markAsRead($message) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['message_ids', 'user_id'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    $message_ids = $data['message_ids'];
    $user_id = (int)$data['user_id'];
    
    // التحقق من أن message_ids هو مصفوفة
    if (!is_array($message_ids)) {
        jsonResponse(false, 'معرفات الرسائل يجب أن تكون مصفوفة');
        return;
    }
    
    // تحويل معرفات الرسائل إلى أرقام صحيحة
    $message_ids = array_map('intval', $message_ids);
    
    // محاولة تحديد الرسائل كمقروءة
    if ($message->markAsRead($message_ids, $user_id)) {
        jsonResponse(true, 'تم تحديد الرسائل كمقروءة بنجاح');
    } else {
        jsonResponse(false, 'فشل في تحديد الرسائل كمقروءة');
    }
}

/**
 * حذف رسالة
 */
function deleteMessage($message) {
    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['message_id', 'user_id'];
    $missing_fields = validateRequiredFields($data, $required_fields);
    
    if (!empty($missing_fields)) {
        jsonResponse(false, 'الحقول التالية مطلوبة: ' . implode(', ', $missing_fields));
        return;
    }
    
    $message_id = (int)$data['message_id'];
    $user_id = (int)$data['user_id'];
    
    // محاولة الحذف
    if ($message->delete($message_id, $user_id)) {
        jsonResponse(true, 'تم حذف الرسالة بنجاح');
    } else {
        jsonResponse(false, 'فشل في حذف الرسالة');
    }
}
?>

