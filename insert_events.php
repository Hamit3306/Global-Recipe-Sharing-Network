<?php
// insert_events.php - VARSAYILAN ETKİNLİK YÜKLEYİCİ
require_once 'db.php';

try {
    // Önce mevcut etkinlikleri temizleyelim (Çakışma olmasın)
    $pdo->exec("DELETE FROM events");

    // Rastgele 3 tarif ID'si çek
    $recipes = $pdo->query("SELECT id FROM recipes ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);

    if (count($recipes) < 3) {
        die("<h3 style='color:red'>HATA: Önce sisteme en az 3 yemek tarifi eklemelisiniz! (import_csv.php çalıştırın)</h3>");
    }

    // Örnek Mekanlar
    $locations = [
        "Gastronomi Atölyesi - Taksim",
        "Boğaz Manzaralı Teras - Ortaköy",
        "Şefin Gizli Mutfağı - Kadıköy"
    ];

    $sql = "INSERT INTO events (recipe_id, event_date, quota, booked, location) VALUES (?, CURDATE() + INTERVAL ? DAY, 10, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // 1. Etkinlik
    $stmt->execute([$recipes[0], 2, rand(0, 5), $locations[0]]);
    
    // 2. Etkinlik
    $stmt->execute([$recipes[1], 5, rand(2, 8), $locations[1]]);
    
    // 3. Etkinlik
    $stmt->execute([$recipes[2], 7, 0, $locations[2]]);

    echo "<h2 style='color:green'>✅ 3 Adet Varsayılan Etkinlik Eklendi!</h2>";
    echo "<p>Artık 'Events' sayfasında bunları görebilirsiniz.</p>";
    echo "<a href='events.php'>Etkinlikleri Gör</a> | <a href='admin.php'>Admin Paneline Git</a>";

} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>