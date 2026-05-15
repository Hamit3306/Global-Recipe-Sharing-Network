<?php
include 'header.php';

// Güvenlik: Sadece Admin Girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='text-center py-20 font-bold text-red-600'>Erişim Reddedildi! Sadece yöneticiler girebilir.</div>";
    include 'footer.php'; exit;
}

$msg = "";

// --- İŞLEM 1: ETKİNLİK SİLME ---
if (isset($_GET['delete_event'])) {
    $eid = (int)$_GET['delete_event'];
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$eid]);
    header("Location: admin.php?msg=Etkinlik Silindi"); exit;
}

// --- İŞLEM 2: YENİ ETKİNLİK EKLEME ---
if (isset($_POST['add_event'])) {
    $rid = $_POST['recipe_id'];
    $loc = clean($_POST['location']);
    $date = $_POST['date'];
    $quota = (int)$_POST['quota'];

    $stmt = $pdo->prepare("INSERT INTO events (recipe_id, location, event_date, quota, booked) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$rid, $loc, $date, $quota]);
    $msg = "Yeni etkinlik başarıyla oluşturuldu!";
}

// Verileri Çek
$events = $pdo->query("SELECT e.*, r.title FROM events e JOIN recipes r ON e.recipe_id = r.id ORDER BY e.event_date DESC")->fetchAll();
$recipes = $pdo->query("SELECT id, title FROM recipes ORDER BY title ASC")->fetchAll(); // Dropdown için
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 mt-8 mb-20">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 border-b pb-4"><i class="fas fa-user-shield"></i> Admin Paneli</h1>

    <?php if($msg || isset($_GET['msg'])) echo "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 font-bold'>".($msg ?: $_GET['msg'])."</div>"; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        <div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
                <h2 class="text-xl font-bold text-purple-700 mb-4"><i class="fas fa-plus-circle"></i> Yeni Etkinlik Ekle</h2>
                
                <form method="post" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Yemek Seçin</label>
                        <select name="recipe_id" class="w-full border p-2 rounded-lg" required>
                            <?php foreach($recipes as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Mekan / Lokasyon</label>
                        <input type="text" name="location" placeholder="Örn: Lezzet Atölyesi, Taksim" class="w-full border p-2 rounded-lg" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tarih</label>
                            <input type="date" name="date" class="w-full border p-2 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kontenjan</label>
                            <input type="number" name="quota" value="10" class="w-full border p-2 rounded-lg" required>
                        </div>
                    </div>

                    <button name="add_event" class="w-full bg-purple-600 text-white py-2 rounded-lg font-bold hover:bg-purple-700 transition">Etkinliği Yayınla</button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Aktif Etkinlikler</h2>
                <div class="space-y-3">
                    <?php foreach($events as $e): ?>
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($e['title']); ?></h4>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($e['location']); ?> | 
                                    <i class="far fa-calendar"></i> <?php echo $e['event_date']; ?>
                                </p>
                                <p class="text-xs font-bold text-green-600">Doluluk: <?php echo $e['booked']; ?> / <?php echo $e['quota']; ?></p>
                            </div>
                            <a href="admin.php?delete_event=<?php echo $e['id']; ?>" onclick="return confirm('Bu etkinliği silmek istediğinize emin misiniz?')" class="bg-red-100 text-red-600 px-3 py-1 rounded hover:bg-red-200 text-sm">
                                <i class="fas fa-trash"></i> Sil
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($events)==0) echo "<p class='text-gray-400 text-sm'>Henüz etkinlik yok.</p>"; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-users"></i> Kayıtlı Kullanıcılar</h2>
                <div class="overflow-auto max-h-[600px]">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-600 font-bold">
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">Kullanıcı</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Rol</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach($users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3"><?php echo $u['id']; ?></td>
                                <td class="p-3 font-bold"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="p-3 text-gray-500"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?php echo $u['role']=='admin'?'bg-red-100 text-red-700':'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo strtoupper($u['role']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>