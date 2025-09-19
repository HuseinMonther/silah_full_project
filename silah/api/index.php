<?php
/**
 * نقطة دخول API لمشروع Silah
 */

// تضمين الملفات المطلوبة
require_once 'includes/functions.php';

// تعيين رؤوس CORS
setCorsHeaders();

// معلومات API
$api_info = [
    'name' => 'Silah API',
    'version' => '1.0.0',
    'description' => 'واجهة برمجة التطبيقات لمنصة سلاح للإعلانات المبوبة',
    'endpoints' => [
        'auth' => [
            'register' => 'POST /api/auth.php?action=register',
            'login' => 'POST /api/auth.php?action=login'
        ],
        'users' => [
            'get' => 'GET /api/users.php?action=get&user_id={id}',
            'getAll' => 'GET /api/users.php?action=getAll',
            'update' => 'POST /api/users.php?action=update',
            'delete' => 'POST /api/users.php?action=delete'
        ],
        'ads' => [
            'getAll' => 'GET /api/ads.php?action=getAll',
            'get' => 'GET /api/ads.php?action=get&ad_id={id}',
            'getUserAds' => 'GET /api/ads.php?action=getUserAds&user_id={id}',
            'getCategories' => 'GET /api/ads.php?action=getCategories',
            'create' => 'POST /api/ads.php?action=create',
            'update' => 'POST /api/ads.php?action=update',
            'delete' => 'POST /api/ads.php?action=delete',
            'changeStatus' => 'POST /api/ads.php?action=changeStatus'
        ],
        'messages' => [
            'get' => 'GET /api/messages.php?action=get&user_id={id}',
            'getConversation' => 'GET /api/messages.php?action=getConversation&user1_id={id1}&user2_id={id2}&ad_id={ad_id}',
            'getAdMessages' => 'GET /api/messages.php?action=getAdMessages&ad_id={ad_id}&user_id={user_id}',
            'getConversationsList' => 'GET /api/messages.php?action=getConversationsList&user_id={id}',
            'getUnreadCount' => 'GET /api/messages.php?action=getUnreadCount&user_id={id}',
            'send' => 'POST /api/messages.php?action=send',
            'markAsRead' => 'POST /api/messages.php?action=markAsRead',
            'delete' => 'POST /api/messages.php?action=delete'
        ]
    ],
    'status' => 'active',
    'timestamp' => date('Y-m-d H:i:s')
];

// إرجاع معلومات API
jsonResponse(true, 'مرحباً بك في API منصة سلاح', $api_info);
?>

