<?php
/**
 * فئة الإعلان لمشروع Silah
 */

class Ad {
    private $conn;
    private $table_name = "ads";
    
    public $ad_id;
    public $user_id;
    public $category_id;
    public $title;
    public $description;
    public $price;
    public $location;
    public $image_url;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * إنشاء إعلان جديد
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, category_id=:category_id, title=:title, 
                      description=:description, price=:price, location=:location, 
                      image_url=:image_url, status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        // تنظيف البيانات
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // ربط المعاملات
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":status", $this->status);
        
        if ($stmt->execute()) {
            $this->ad_id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * الحصول على جميع الإعلانات مع إمكانية التصفية
     */
    public function getAll($filters = []) {
        $query = "SELECT a.*, u.username, c.name as category_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  WHERE a.status = 'active'";
        
        $params = [];
        
        // تطبيق المرشحات
        if (!empty($filters['category_id'])) {
            $query .= " AND a.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search_query'])) {
            $query .= " AND (a.title LIKE :search_query OR a.description LIKE :search_query)";
            $params[':search_query'] = '%' . $filters['search_query'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $query .= " AND a.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $query .= " AND a.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['location'])) {
            $query .= " AND a.location LIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        // إضافة الحد الأقصى للنتائج
        if (!empty($filters['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        // ربط المعاملات
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على إعلان واحد بالتفصيل
     */
    public function getById($ad_id) {
        $query = "SELECT a.*, u.username, u.email, c.name as category_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  WHERE a.ad_id = :ad_id AND a.status = 'active'
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * الحصول على إعلانات المستخدم
     */
    public function getUserAds($user_id) {
        $query = "SELECT a.*, c.name as category_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  WHERE a.user_id = :user_id AND a.status != 'deleted'
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديث إعلان
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id=:category_id, title=:title, description=:description, 
                      price=:price, location=:location, image_url=:image_url
                  WHERE ad_id=:ad_id AND user_id=:user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // تنظيف البيانات
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        
        // ربط المعاملات
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":ad_id", $this->ad_id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }
    
    /**
     * حذف إعلان (تغيير الحالة إلى محذوف)
     */
    public function delete($ad_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status='deleted' 
                  WHERE ad_id=:ad_id AND user_id=:user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * تغيير حالة الإعلان (للمسؤول)
     */
    public function changeStatus($ad_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status=:status 
                  WHERE ad_id=:ad_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":ad_id", $ad_id);
        
        return $stmt->execute();
    }
    
    /**
     * الحصول على الفئات
     */
    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * التحقق من ملكية الإعلان
     */
    public function isOwner($ad_id, $user_id) {
        $query = "SELECT ad_id FROM " . $this->table_name . " 
                  WHERE ad_id = :ad_id AND user_id = :user_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ad_id", $ad_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>

