-- إنشاء قاعدة بيانات Silah
CREATE DATABASE IF NOT EXISTS silah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE silah;

-- جدول المستخدمين
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول الفئات
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الإعلانات
CREATE TABLE ads (
    ad_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    location VARCHAR(100) NOT NULL,
    image_url VARCHAR(255),
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- جدول الرسائل
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    ad_id INT NOT NULL,
    message_content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ad_id) REFERENCES ads(ad_id) ON DELETE CASCADE
);

-- إدراج بيانات تجريبية للفئات
INSERT INTO categories (name) VALUES 
('إلكترونيات'),
('سيارات'),
('عقارات'),
('أثاث'),
('ملابس'),
('كتب'),
('رياضة'),
('خدمات');

-- إدراج مستخدم مسؤول تجريبي
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@silah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- إدراج مستخدمين تجريبيين
INSERT INTO users (username, email, password) VALUES 
('محمد_أحمد', 'mohamed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('فاطمة_علي', 'fatima@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('أحمد_محمود', 'ahmed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- إدراج إعلانات تجريبية
INSERT INTO ads (user_id, category_id, title, description, price, location, image_url) VALUES 
(2, 1, 'لابتوب Dell مستعمل', 'لابتوب Dell Inspiron 15 في حالة ممتازة، مستعمل لمدة سنة واحدة فقط. يحتوي على معالج Intel Core i5 وذاكرة 8GB RAM.', 1200.00, 'غزة', 'images/laptop.jpg'),
(3, 2, 'سيارة تويوتا كورولا 2018', 'سيارة تويوتا كورولا موديل 2018، لون أبيض، في حالة ممتازة. قطعت 50,000 كيلومتر فقط.', 15000.00, 'رفح', 'images/car.jpg'),
(4, 3, 'شقة للإيجار في الرمال', 'شقة مفروشة للإيجار، 3 غرف نوم، صالة، مطبخ، حمامين. الطابق الثالث مع مصعد.', 800.00, 'غزة - الرمال', 'images/apartment.jpg'),
(2, 4, 'طقم صالون جلد طبيعي', 'طقم صالون مكون من 7 قطع، جلد طبيعي، لون بني، في حالة ممتازة. مستعمل لمدة سنتين.', 2500.00, 'خان يونس', 'images/sofa.jpg');

-- إدراج رسائل تجريبية
INSERT INTO messages (sender_id, receiver_id, ad_id, message_content) VALUES 
(3, 2, 1, 'مرحبا، هل اللابتوب ما زال متاحاً؟'),
(2, 3, 1, 'نعم، اللابتوب متاح. هل تريد رؤيته؟'),
(4, 3, 2, 'السلام عليكم، هل يمكنني معاينة السيارة؟'),
(3, 4, 2, 'وعليكم السلام، بالطبع. متى يناسبك الوقت؟');

