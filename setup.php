<?php
// setup.php - SOSYAL MEDYA GÜNCELLEMESİ
$host = 'localhost'; $user = 'root'; $pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS recipe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE recipe_db");

    // MEVCUT TABLOLAR (KORUNUR)
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), email VARCHAR(100), password VARCHAR(255), role ENUM('user','admin') DEFAULT 'user', profile_pic VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "recipes" => "CREATE TABLE IF NOT EXISTS recipes (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(150), description TEXT, ingredients TEXT, instructions TEXT, image VARCHAR(255), difficulty VARCHAR(20), cuisine VARCHAR(50), diet VARCHAR(50), calories INT, prep_time INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "ratings" => "CREATE TABLE IF NOT EXISTS ratings (user_id INT, recipe_id INT, rating TINYINT, PRIMARY KEY (user_id, recipe_id))",
        "follows" => "CREATE TABLE IF NOT EXISTS follows (follower_id INT, followed_id INT, PRIMARY KEY (follower_id, followed_id))",
        "events" => "CREATE TABLE IF NOT EXISTS events (id INT AUTO_INCREMENT PRIMARY KEY, recipe_id INT, event_date DATE, quota INT DEFAULT 5, booked INT DEFAULT 0, location VARCHAR(150))",
        "event_bookings" => "CREATE TABLE IF NOT EXISTS event_bookings (id INT AUTO_INCREMENT PRIMARY KEY, event_id INT, user_id INT)",
        "comments" => "CREATE TABLE IF NOT EXISTS comments (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, recipe_id INT, comment TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "likes" => "CREATE TABLE IF NOT EXISTS likes (user_id INT, recipe_id INT, PRIMARY KEY(user_id, recipe_id))",
        "favorites" => "CREATE TABLE IF NOT EXISTS favorites (user_id INT, recipe_id INT, PRIMARY KEY(user_id, recipe_id))",
        "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value VARCHAR(255))"
    ];

    foreach ($tables as $name => $sql) { $pdo->exec($sql); }

    // --- YENİ TABLO: TARİF ÖNERİLERİ (Recommendation) ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        recipe_id INT NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "<h2 style='color:green'>✅ Veritabanı Güncellendi! (Öneriler tablosu eklendi)</h2>";
    echo "<a href='index.php'>Ana Sayfaya Dön</a>";

} catch (PDOException $e) { echo "Hata: " . $e->getMessage(); }
?>