<?php
/**
 * فئة المستخدم لمشروع Silah
 */

class User {
    private $conn;
    private $table_name = "users";
    
    public $user_id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * تسجيل مستخدم جديد
     */
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password, role=:role";
        
        $stmt = $this->conn->prepare($query);
        
        // تنظيف البيانات
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // ربط المعاملات
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        
        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    public function login($email, $password) {
        $query = "SELECT user_id, username, email, password, role 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                $this->user_id = $row['user_id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * الحصول على تفاصيل المستخدم
     */
    public function getUserById($user_id) {
        $query = "SELECT user_id, username, email, role, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * تحديث ملف تعريف المستخدم
     */
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, email=:email";
        
        // إضافة كلمة المرور إذا تم توفيرها
        if (!empty($this->password)) {
            $query .= ", password=:password";
        }
        
        $query .= " WHERE user_id=:user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // تنظيف البيانات
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // ربط المعاملات
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if (!empty($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $this->password);
        }
        
        return $stmt->execute();
    }
    
    /**
     * التحقق من وجود اسم المستخدم
     */
    public function usernameExists($username) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * التحقق من وجود البريد الإلكتروني
     */
    public function emailExists($email) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * الحصول على جميع المستخدمين (للمسؤول)
     */
    public function getAllUsers() {
        $query = "SELECT user_id, username, email, role, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * حذف مستخدم (للمسؤول)
     */
    public function deleteUser($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
}
?>

