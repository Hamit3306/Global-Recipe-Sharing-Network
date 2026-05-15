<?php 
include 'header.php'; 
// Giriş yapılmamışsa yönlendir
if(!isLoggedIn()) header("Location: login.php");

$uid = $_SESSION['user_id'];
$msg = "";
$error = "";

// --- PROFİL GÜNCELLEME VE AVATAR YÜKLEME İŞLEMİ ---
if(isset($_POST['update_profile'])){
    $u = clean($_POST['u']); 
    $e = clean($_POST['e']); 
    $p = $_POST['p'] ?? ''; 
    
    $sql="UPDATE users SET username=?, email=?"; 
    $params=[$u, $e];

    // 1. Şifre Güncelleme Kontrolü
    if (!empty($p)) {
        if (strlen($p) < 6) {
            $error = "Şifre en az 6 karakter olmalıdır.";
        } else {
            $sql.=", password=?"; 
            $params[]=password_hash($p, PASSWORD_DEFAULT);
        }
    }

    if (!$error) {
        // 2. Resim Yükleme 
        if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0){
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if(in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] < 5000000) { 
                
                if (!file_exists('uploads/avatars')) {
                    mkdir('uploads/avatars', 0777, true);
                }

                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = "uploads/avatars/avatar_" . $uid . "_" . time() . "." . $ext;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filename)) {
                    $oldPic = $pdo->query("SELECT profile_pic FROM users WHERE id=$uid")->fetchColumn();
                    if ($oldPic && file_exists($oldPic) && strpos($oldPic, 'uploads/avatars') !== false) {
                         // Eski resim silme alanı
                    }
                    
                    $sql.=", profile_pic=?"; 
                    $params[]=$filename;
                    $_SESSION['profile_pic'] = $filename; 
                } else {
                    $error = "Resim yüklenirken bir hata oluştu.";
                }
            } else {
                $error = "Resim boyutu çok büyük (Max 5MB) veya dosya formatı desteklenmiyor (JPG, PNG, GIF).";
            }
        }

        // 3. Veritabanı Güncelleme
        try {
            $sql.=" WHERE id=?"; 
            $params[]=$uid;
            $pdo->prepare($sql)->execute($params); 
            $_SESSION['username']=$u; 
            
            if (!$error) {
                $msg="Profil başarıyla güncellendi!";
            }
        } catch (PDOException $e) {
            $error = "Veritabanı hatası: Kullanıcı adı/E-posta zaten kullanılıyor olabilir.";
        }
    }
}

// Kullanıcı verilerini çek
$user = $pdo->query("SELECT * FROM users WHERE id=$uid")->fetch();

// --- VERİLERİ ÇEK ---
$saved = $pdo->query("SELECT r.* FROM favorites f JOIN recipes r ON f.recipe_id=r.id WHERE f.user_id=$uid")->fetchAll();
$inbox = $pdo->query("SELECT rec.*, u.username as sender, r.title, r.id as rid FROM recommendations rec JOIN users u ON rec.sender_id=u.id JOIN recipes r ON rec.recipe_id=r.id WHERE rec.receiver_id=$uid ORDER BY rec.created_at DESC")->fetchAll();
$feed = $pdo->query("SELECT u.username, r.title, r.id as rid FROM likes l JOIN users u ON l.user_id=u.id JOIN recipes r ON l.recipe_id=r.id WHERE l.user_id IN (SELECT followed_id FROM follows WHERE follower_id=$uid) LIMIT 10")->fetchAll();
?>

<div class="max-w-4xl mx-auto px-6 mb-20" style="padding-top: 0 !important; margin-top: 0 !important;"> 
    
    <div class="bg-white p-8 rounded-3xl shadow-lg border border-gray-100 text-center relative overflow-hidden mb-10">
        <div class="bg-orange-50 h-24 absolute top-0 left-0 w-full z-0"></div>
        <div class="relative z-10">
            <?php if($user['profile_pic'] && file_exists($user['profile_pic'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                     class="w-28 h-28 rounded-full mx-auto object-cover border-4 border-white shadow-lg mb-4">
            <?php else: ?>
                <div class="w-28 h-28 bg-orange-500 rounded-full mx-auto flex items-center justify-center text-5xl text-white font-bold border-4 border-white shadow-lg mb-4">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            
            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <div class="flex justify-center border-b mb-8 gap-8">
        <button onclick="openTab('saved', this)" class="pb-2 border-b-2 border-orange-500 font-bold text-orange-600 tab-btn">Saved Recipes</button>
        <button onclick="openTab('friends', this)" class="pb-2 border-b-2 border-transparent text-gray-500 hover:text-orange-600 tab-btn">Friends Activity</button>
        <button onclick="openTab('inbox', this)" class="pb-2 border-b-2 border-transparent text-gray-500 hover:text-orange-600 tab-btn">Inbox <?php if(count($inbox)>0) echo "(".count($inbox).")"; ?></button>
        <button onclick="openTab('settings', this)" class="pb-2 border-b-2 border-transparent text-gray-500 hover:text-orange-600 tab-btn">Settings</button>
    </div>
    
    <?php if ($msg): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 font-bold"><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 font-bold"><?php echo $error; ?></div>
    <?php endif; ?>

    <div id="saved" class="tab-content">
        <?php if(count($saved)>0): ?>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach($saved as $s): ?>
                    <a href="detail.php?id=<?php echo $s['id']; ?>" class="flex items-center gap-4 bg-white p-3 rounded-xl border hover:shadow-md transition">
                        <img src="uploads/<?php echo $s['image']; ?>" class="w-16 h-16 rounded-lg object-cover">
                        <h4 class="font-bold"><?php echo htmlspecialchars($s['title']); ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 py-10">You haven't saved any recipes yet.</p>
        <?php endif; ?>
    </div>

    <div id="friends" class="tab-content hidden">
        <?php if(count($feed)>0): ?>
            <div class="space-y-3">
                <?php foreach($feed as $f): ?>
                    <div class="bg-white p-4 rounded-xl border flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">
                            <?php echo substr($f['username'],0,1); ?>
                        </div>
                        <p class="text-sm"><strong><?php echo htmlspecialchars($f['username']); ?></strong> liked <a href="detail.php?id=<?php echo $f['rid']; ?>" class="text-orange-600 font-bold hover:underline"><?php echo htmlspecialchars($f['title']); ?></a></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-10">
                <p class="text-gray-500 mb-4">No activity or no friends.</p>
                <a href="find_chefs.php" class="bg-gray-900 text-white px-6 py-2 rounded-lg font-bold hover:bg-black transition">Find Chefs to Follow</a>
            </div>
        <?php endif; ?>
    </div>

    <div id="inbox" class="tab-content hidden">
        <?php if(count($inbox)>0): ?>
            <div class="space-y-3">
                <?php foreach($inbox as $i): ?>
                    <div class="bg-purple-50 p-4 rounded-xl border border-purple-100 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-purple-900"><strong><?php echo htmlspecialchars($i['sender']); ?></strong> sent you a recipe:</p>
                            <a href="detail.php?id=<?php echo $i['rid']; ?>" class="font-bold text-lg hover:underline"><?php echo htmlspecialchars($i['title']); ?></a>
                            <p class="text-xs text-gray-500 mt-1">"<?php echo htmlspecialchars($i['message']); ?>"</p>
                        </div>
                        <a href="detail.php?id=<?php echo $i['rid']; ?>" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-bold text-sm shadow-sm hover:bg-gray-100">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 py-10">Your inbox is empty.</p>
        <?php endif; ?>
    </div>

    <div id="settings" class="tab-content hidden text-center">
        <form method="post" enctype="multipart/form-data" class="max-w-md mx-auto bg-white p-8 rounded-2xl border space-y-6 shadow-md text-left">
            <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Account Settings</h2>
            
            <div class="border border-gray-200 p-4 rounded-xl">
                <label class="block text-sm font-bold text-gray-700 mb-2">Profile Picture (Max 5MB)</label>
                <input type="file" name="avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                <?php if($user['profile_pic'] && file_exists($user['profile_pic'])): ?>
                    <p class="text-xs text-gray-500 mt-2">Mevcut resminiz yüklü. Yenisini seçerek değiştirebilirsiniz.</p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Username</label>
                <input type="text" name="u" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="Username" class="w-full border p-3 rounded-lg" required>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                <input type="email" name="e" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email" class="w-full border p-3 rounded-lg" required>
            </div>
            
            <div class="border border-red-200 p-4 rounded-xl bg-red-50">
                <label class="block text-sm font-bold text-red-700 mb-2">New Password (Min 6 chars)</label>
                <input type="password" name="p" placeholder="Leave blank to keep current password" class="w-full border p-3 rounded-lg" minlength="6">
                <p class="text-xs text-red-500 mt-1">Sadece değiştirmek istiyorsanız doldurun.</p>
            </div>
            
            <button name="update_profile" class="w-full bg-orange-600 text-white py-3 rounded-lg font-bold hover:bg-orange-700 transition shadow-lg">Save Changes</button>
        </form>
    </div>

</div>

<script>
// Sayfa yüklendiğinde varsayılan sekme ayarını yap
document.addEventListener('DOMContentLoaded', () => {
    // URL'deki sekmeyi kontrol et, yoksa varsayılan olarak 'saved' seçeneğini başlat
    const hash = window.location.hash.substring(1);
    const initialTab = hash || 'saved'; 
    
    // Varsayılan butonu bul
    let initialBtn = document.querySelector(`.tab-btn[onclick*="'${initialTab}'"]`);
    
    if (!initialBtn) {
        // Geçersiz hash varsa 'saved' kullan
        initialBtn = document.querySelector(`.tab-btn[onclick*="'saved'"]`);
    }

    if (initialBtn) {
        openTab(initialTab, initialBtn);
    }
});

function openTab(name, element){
    // Tüm içeriği gizle
    document.querySelectorAll('.tab-content').forEach(d => d.classList.add('hidden'));
    
    // İstenen içeriği göster
    document.getElementById(name).classList.remove('hidden');
    
    // Tüm butonların stilini sıfırla
    document.querySelectorAll('.tab-btn').forEach(b => { 
        b.classList.remove('border-orange-500','text-orange-600'); 
        b.classList.add('border-transparent', 'text-gray-500'); 
    });
    
    // Aktif butona stil uygula
    element.classList.add('border-orange-500','text-orange-600');
    element.classList.remove('border-transparent', 'text-gray-500');
    
    // URL'yi güncelle
    window.location.hash = name;
}
</script>
<?php include 'footer.php'; ?>