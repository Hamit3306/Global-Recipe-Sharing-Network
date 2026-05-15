<?php
include 'header.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';
$message = '';

// --- TAKİP ETME / TAKİBİ BIRAKMA İŞLEMLERİ ---
if (isset($_GET['action']) && isset($_GET['chef_id'])) {
    $chef_id = (int)$_GET['chef_id'];
    
    // Kendini takip etmeyi engelle
    if ($chef_id == $uid) {
        $message = "<div class='text-red-600 font-bold'>Kendinizi takip edemezsiniz.</div>";
    } else {
        if ($_GET['action'] == 'follow') {
            // Takip et (Eğer daha önce takip etmiyorsa)
            $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)")->execute([$uid, $chef_id]);
            $message = "<div class='text-green-600 font-bold'>Şef başarıyla takip edildi!</div>";
        } elseif ($_GET['action'] == 'unfollow') {
            // Takibi bırak
            $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?")->execute([$uid, $chef_id]);
            $message = "<div class='text-orange-600 font-bold'>Takip bırakıldı.</div>";
        }
        // URL'yi temizle ve mesajı koru
        header("Location: find_chefs.php?q=" . urlencode($search) . "&msg=" . urlencode($message));
        exit;
    }
}

// URL'den mesaj çekme
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// --- ŞEF ARAMA SORGUSU ---
$sql = "SELECT id, username, email, profile_pic FROM users WHERE role = 'user' AND id != ?";
$params = [$uid];

if ($search) {
    $sql .= " AND username LIKE ?";
    $params[] = "%$search%";
}

// Takip edilen kullanıcıların ID'lerini çek
$followed = $pdo->query("SELECT followed_id FROM follows WHERE follower_id = $uid")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chefs = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto mt-10 px-6 mb-20">
    <h1 class="text-3xl font-bold text-gray-900 mb-6 flex items-center gap-2">
        <i class="fas fa-users text-orange-600"></i> Find Chefs
    </h1>

    <?php if ($message): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-lg">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <form method="GET" class="flex gap-4">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by chef's username..." 
                   class="flex-1 border border-gray-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 transition" required>
            <button type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-700 transition">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="find_chefs.php" class="bg-gray-200 text-gray-600 px-6 py-3 rounded-xl font-bold hover:bg-gray-300 transition">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($search && count($chefs) > 0): ?>
        <h2 class="text-xl font-bold mb-4 text-gray-800"><?php echo count($chefs); ?> Chefs Found:</h2>
        <div class="space-y-4">
            <?php foreach ($chefs as $chef): ?>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <?php if($chef['profile_pic'] && file_exists($chef['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($chef['profile_pic']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-orange-200">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xl font-bold">
                                <?php echo strtoupper(substr($chef['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <span class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($chef['username']); ?></span>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($chef['email']); ?></p>
                        </div>
                    </div>
                    
                    <?php 
                        $isFollowing = in_array($chef['id'], $followed);
                        $action = $isFollowing ? 'unfollow' : 'follow';
                        $buttonText = $isFollowing ? 'Following' : '+ Follow';
                        $buttonClass = $isFollowing ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-green-600 text-white hover:bg-green-700';
                    ?>
                    <a href="find_chefs.php?action=<?php echo $action; ?>&chef_id=<?php echo $chef['id']; ?>&q=<?php echo urlencode($search); ?>" 
                       class="px-6 py-2 rounded-full font-bold text-sm transition <?php echo $buttonClass; ?>">
                        <?php echo $buttonText; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($search): ?>
        <div class="text-center py-20 bg-gray-50 rounded-xl border border-dashed border-gray-300">
            <i class="fas fa-search text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600 font-medium">No chefs found matching "<?php echo htmlspecialchars($search); ?>".</p>
        </div>
    <?php else: ?>
        <div class="text-center py-20 bg-gray-50 rounded-xl border border-dashed border-gray-300">
            <i class="fas fa-search text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600 font-medium">Start searching to find new chefs to follow!</p>
        </div>
    <?php endif; ?>

</div>
<?php include 'footer.php'; ?>