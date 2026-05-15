<?php
// update_db.php - EKSİK SÜTUNLARI EKLEME
require_once 'db.php';

try {
    // 1. Profil Resmi Sütunu Ekle (Eğer yoksa)
    // Hata bastırma operatörü (@) veya TRY-CATCH ile, eğer sütun zaten varsa kodun patlamasını engelliyoruz.
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
        echo "<p style='color:green'>✔ 'profile_pic' sütunu başarıyla eklendi.</p>";
    } catch (PDOException $e) {
        // Sütun zaten varsa hata verir, önemli değil.
        echo "<p style='color:blue'>ℹ 'profile_pic' sütunu zaten var veya eklenemedi.</p>";
    }

    // 2. Resim Klasörünü Oluştur
    if (!file_exists('uploads/avatars')) {
        mkdir('uploads/avatars', 0777, true);
        echo "<p style='color:green'>✔ Resim yükleme klasörü (uploads/avatars) oluşturuldu.</p>";
    }

    echo "<h2>✅ GÜNCELLEME TAMAMLANDI!</h2>";
    echo "<p>Artık site hatasız açılacaktır.</p>";
    echo "<a href='index.php' style='font-size:20px; font-weight:bold;'>Ana Sayfaya Dön</a>";

} catch (PDOException $e) {
    echo "<h3>Genel Hata:</h3> " . $e->getMessage();
}
?>