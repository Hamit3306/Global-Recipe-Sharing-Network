<?php
// test_images.php - Resim Dosyası Yolu ve İsim Kontrol Aracı
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Resim Sorunu Analizi</title>";
echo "<style>body{font-family:sans-serif; padding:20px;} .success{color:green;} .error{color:red; font-weight:bold;} .info{color:blue;} .box{border:1px solid #ccc; padding:15px; margin-top:15px; border-radius:8px;}</style>";
echo "</head><body>";
echo "<h2>🖼️ Resim Sorunu Analizi</h2>";

$uploads_dir = 'uploads';

// 1. UPLOAD KLASÖRÜNÜN VARLIĞI
if (!is_dir($uploads_dir)) {
    echo "<p class='error'>❌ HATA: '$uploads_dir' klasörü bulunamadı!</p>";
    echo "<p>Lütfen bu klasörü oluşturun: <code>C:\\xampp\\htdocs\\recipeshare\\uploads</code></p>";
    exit;
} else {
    echo "<p class='success'>✅ '$uploads_dir' klasörü mevcut.</p>";
}

// 2. KLASÖRDEKİ GERÇEK DOSYALARI ÇEK
$files_in_dir = glob($uploads_dir . '/*.jpg');
$real_file_names = [];
foreach ($files_in_dir as $file) {
    // Yalnızca dosya adını al
    $real_file_names[] = basename($file); 
}

// 3. VERİTABANINDAN BEKLENEN İSİMLERİ ÇEK
$expected_images = $pdo->query("SELECT title, image FROM recipes LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

echo "<div class='box'><h3>📂 Klasördeki Gerçek Dosyalar (" . count($real_file_names) . " adet)</h3>";
$i=0;
foreach($real_file_names as $name) {
    echo "- " . htmlspecialchars($name) . "<br>";
    if ($i++ > 10) { echo "... (Devamı var)"; break; }
}
echo "</div>";


echo "<div class='box'><h3>🗄️ Veritabanındaki İsimler (İlk 100 Tariften Örnek)</h3>";
$total_missing = 0;
foreach ($expected_images as $recipe) {
    $expected_name = htmlspecialchars($recipe['image']);
    $title = htmlspecialchars($recipe['title']);
    
    // Klasördeki isimlerle tam eşleşme kontrolü (Büyük/küçük harf kontrolü dahil)
    if (in_array($expected_name, $real_file_names)) {
        // Tam eşleşme varsa
        echo "<p class='success'>✅ BULUNDU! - Tarif: $title | Beklenti: <code>$expected_name</code></p>";
    } else {
        // Tam eşleşme yoksa, dosyanın büyük/küçük harf farkıyla var olup olmadığını kontrol et (Windows'ta yaygın hata)
        $found_case_insensitive = false;
        foreach($real_file_names as $real_name) {
            if (strtolower($real_name) == strtolower($expected_name)) {
                echo "<p class='error'>❌ BULUNAMADI! (Büyük/Küçük Harf Farkı) - Tarif: $title | Beklenti: <code>$expected_name</code> | Gerçek: <code>$real_name</code></p>";
                $found_case_insensitive = true;
                break;
            }
        }

        if (!$found_case_insensitive) {
            // Hiçbir şekilde bulunamadıysa (Sizin durumunuz buydu)
            echo "<p class='error'>❌ BULUNAMADI! - Tarif: $title | Beklenti: <code>$expected_name</code></p>";
            echo "<p class='info' style='margin-left:20px;'>Sebep: '$uploads_dir' klasöründe <code>$expected_name</code> adında bir dosya yok.</p>";
        }
        $total_missing++;
    }
}
echo "</div>";

if ($total_missing > 0) {
    echo "<h3 class='error'>⚠️ Özet: $total_missing adet resim bulunamadı.</h3>";
    echo "<p>Lütfen 'uploads' klasöründeki resim isimlerini veritabanının beklediği <code>image_XXXX.jpg</code> formatına getirin.</p>";
} else {
    echo "<h3 class='success'>🎉 Özet: Tüm resimler bulundu! Site sorunsuz çalışmalı.</h3>";
}

echo "</body></html>";
?>