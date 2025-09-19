<?php
/**
 * فئة الرسائل لمشروع Silah
 */

class Message {
    private $conn;
    private $table_name = "messages";
    
    public $message_id;
    public $sender_id;
    public $receiver_id;
    public $ad_id;
    public $message_content;
    public $is_read;
    public $sent_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * إرسال رسالة جديدة
     */
    public function send() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET sender_id=:sender_id, receiver_id=:receiver_id, ad_id=:ad_id, 
                      message_content=:message_content, is_read=:is_read";
        
        $stmt = $this->conn->prepare($query);
        
        // تنظيف البيانات
        $this->sender_id = htmlspecialchars(strip_tags($this->sender_id));
        $this->receiver_id = htmlspecialchars(strip_tags($this->receiver_id));
        $this->ad_id = htmlspecialchars(strip_tags($this->ad_id));
        $this->message_content = htmlspecialchars(strip_tags($this->message_content));
        $this->is_read = false; // الرسالة غير مقروءة افتراضياً
        
        // ربط المعاملات
        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":ad_id", $this->ad_id);
        $stmt->bindParam(":message_content", $this->message_content);
        $stmt->bindParam(":is_read", $this->is_read, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            $this->message_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * الحصول على رسائل المستخدم
     */
    public function getUserMessages($user_id) {
        $query = "SELECT m.*, 
                         sender.username as sender_username,
                         receiver.username as receiver_username,
                         a.title as ad_title
                  FROM " . $this->table_name . " m
                  LEFT JOIN users sender ON m.sender_id = sender.user_id
                  LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
                  LEFT JOIN ads a ON m.ad_id = a.ad_id
                  WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
                  ORDER BY m.sent_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على محادثة بين مستخدمين حول إعلان معين
     */
    public function getConversation($user1_id, $user2_id, $ad_id) {
        $query = "SELECT m.*, 
                         sender.username as sender_username,
                         receiver.username as receiver_username,
                         a.title as ad_title
                  FROM " . $this->table_name . " m
                  LEFT JOIN users sender ON m.sender_id = sender.user_id
                  LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
                  LEFT JOIN ads a ON m.ad_id = a.ad_id
                  WHERE m.ad_id = :ad_id 
                  AND ((m.sender_id = :user1_id AND m.receiver_id = :user2_id) 
                       OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id))
                  ORDER BY m.sent_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user1_id", $user1_id);
        $stmt->bindParam(":user2_id", $user2_id);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على رسائل إعلان معين
     */
    public function getAdMessages($ad_id, $user_id) {
        $query = "SELECT m.*, 
                         sender.username as sender_username,
                         receiver.username as receiver_username
                  FROM " . $this->table_name . " m
                  LEFT JOIN users sender ON m.sender_id = sender.user_id
                  LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
                  WHERE m.ad_id = :ad_id 
                  AND (m.sender_id = :user_id OR m.receiver_id = :user_id)
                  ORDER BY m.sent_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديد الرسائل كمقروءة
     */
    public function markAsRead($message_ids, $user_id) {
        if (empty($message_ids)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE message_id IN ($placeholders) 
                  AND receiver_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $params = array_merge($message_ids, [$user_id]);
        
        return $stmt->execute($params);
    }
    
    /**
     * عدد الرسائل غير المقروءة للمستخدم
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as unread_count 
                  FROM " . $this->table_name . " 
                  WHERE receiver_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread_count'];
    }
    
    /**
     * الحصول على قائمة المحادثات للمستخدم
     */
    public function getConversationsList($user_id) {
        $query = "SELECT DISTINCT
                    CASE 
                        WHEN m.sender_id = :user_id THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as other_user_id,
                    CASE 
                        WHEN m.sender_id = :user_id THEN receiver.username 
                        ELSE sender.username 
                    END as other_username,
                    m.ad_id,
                    a.title as ad_title,
                    MAX(m.sent_at) as last_message_time,
                    COUNT(CASE WHEN m.receiver_id = :user_id AND m.is_read = 0 THEN 1 END) as unread_count
                  FROM " . $this->table_name . " m
                  LEFT JOIN users sender ON m.sender_id = sender.user_id
                  LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
                  LEFT JOIN ads a ON m.ad_id = a.ad_id
                  WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
                  GROUP BY other_user_id, m.ad_id
                  ORDER BY last_message_time DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * حذف رسالة
     */
    public function delete($message_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE message_id = :message_id 
                  AND (sender_id = :user_id OR receiver_id = :user_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":message_id", $message_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * التحقق من صحة الرسالة (التأكد من أن المستخدم يمكنه إرسال رسالة لهذا الإعلان)
     */
    public function validateMessage($sender_id, $receiver_id, $ad_id) {
        // التحقق من وجود الإعلان وأنه نشط
        $query = "SELECT user_id FROM ads WHERE ad_id = :ad_id AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // التحقق من أن المستقبل هو صاحب الإعلان أو المرسل هو صاحب الإعلان
        return ($ad['user_id'] == $receiver_id || $ad['user_id'] == $sender_id);
    }
}
?>

