<?php 
include 'header.php';
$id = (int)$_GET['id'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 1. TARİFİ GETİR (Resim ve Yazar Bilgisi İçin)
$r = $pdo->query("SELECT r.*, u.username FROM recipes r JOIN users u ON r.user_id=u.id WHERE r.id=$id")->fetch();
if(!$r) die("<div class='text-center mt-20 text-gray-500'>Recipe not found.</div>");

// 2. DİĞER VERİLER
$evt = $pdo->query("SELECT * FROM events WHERE recipe_id=$id")->fetch(); // Etkinlik Kontrolü
$avg = $pdo->query("SELECT AVG(rating) FROM ratings WHERE recipe_id=$id")->fetchColumn(); // Ortalama Puan

// Favori Kontrolü
$isFav = false;
if($uid) {
    $checkFav = $pdo->query("SELECT * FROM favorites WHERE user_id=$uid AND recipe_id=$id");
    $isFav = $checkFav->rowCount() > 0;
}

// Arkadaş Listesi (Göndermek için)
$friends = [];
if($uid) {
    $friends = $pdo->query("SELECT u.id, u.username FROM follows f JOIN users u ON f.followed_id = u.id WHERE f.follower_id = $uid")->fetchAll();
}

// --- İŞLEMLER (POST REQUESTS) ---

// A. Favoriye Ekle/Çıkar
if(isset($_POST['toggle_fav']) && $uid){
    if($isFav) {
        $pdo->exec("DELETE FROM favorites WHERE user_id=$uid AND recipe_id=$id");
    } else {
        $pdo->exec("INSERT INTO favorites (user_id, recipe_id) VALUES ($uid, $id)");
    }
    echo "<script>window.location.href='detail.php?id=$id';</script>";
}

// B. Puan Ver
if(isset($_POST['rate']) && $uid){
    $score = (int)$_POST['score'];
    $pdo->prepare("DELETE FROM ratings WHERE user_id=? AND recipe_id=?")->execute([$uid, $id]);
    $pdo->prepare("INSERT INTO ratings (user_id, recipe_id, rating) VALUES (?,?,?)")->execute([$uid, $id, $score]);
    echo "<script>window.location.href='detail.php?id=$id';</script>";
}

// C. Yorum Yap
if(isset($_POST['comment']) && $uid){
    $c = htmlspecialchars($_POST['comment_text']);
    $pdo->prepare("INSERT INTO comments (user_id, recipe_id, comment) VALUES (?,?,?)")->execute([$uid, $id, $c]);
    echo "<script>window.location.href='detail.php?id=$id';</script>";
}

// D. Arkadaşa Gönder
if(isset($_POST['send_recipe']) && $uid) {
    $receiver = $_POST['receiver_id'];
    $msg = $_POST['message'];
    $pdo->prepare("INSERT INTO recommendations (sender_id, receiver_id, recipe_id, message) VALUES (?, ?, ?, ?)")->execute([$uid, $receiver, $id, $msg]);
    echo "<script>alert('Recipe sent successfully!'); window.location.href='detail.php?id=$id';</script>";
}

$comments = $pdo->query("SELECT c.*, u.username, u.profile_pic, c.created_at FROM comments c JOIN users u ON c.user_id=u.id WHERE c.recipe_id=$id ORDER BY c.created_at DESC")->fetchAll();
?>

<?php if($evt && ($evt['quota']-$evt['booked']) > 0): ?>
<div class="max-w-6xl mx-auto mt-6 px-4">
    <a href="events.php" class="block bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 rounded-xl shadow-lg hover:scale-[1.01] transition flex justify-between items-center group">
        <div class="flex items-center gap-3">
            <i class="fas fa-bullhorn text-2xl animate-pulse"></i>
            <div>
                <p class="font-bold text-lg">Live Tasting Event Available!</p>
                <p class="text-xs text-purple-200">Limited seats available. Join now.</p>
            </div>
        </div>
        <span class="bg-white text-purple-700 px-4 py-2 rounded-lg font-bold text-sm group-hover:bg-purple-50">View Event &rarr;</span>
    </a>
</div>
<?php endif; ?>

<div class="max-w-6xl mx-auto mt-6 px-4 mb-20">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        
        <div class="relative h-[400px]">
            <img src="uploads/<?php echo $r['image']; ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
            
            <div class="absolute bottom-0 left-0 p-8 text-white w-full">
                <div class="flex gap-2 mb-3">
                    <span class="bg-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"><?php echo $r['cuisine']; ?></span>
                    <?php if($r['diet']!=='Standard'): ?><span class="bg-green-600 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"><?php echo $r['diet']; ?></span><?php endif; ?>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-2 shadow-sm"><?php echo htmlspecialchars($r['title']); ?></h1>
                <p class="text-gray-200 text-lg line-clamp-1"><?php echo htmlspecialchars($r['description']); ?></p>
            </div>
        </div>

        <div class="px-8 py-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-6">
            
            <div class="flex items-center gap-6 w-full md:w-auto">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-500">
                        <?php echo substr($r['username'],0,1); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-bold uppercase">Recipe By</p>
                        <a href="user_profile.php?id=<?php echo $r['user_id']; ?>" class="font-bold text-gray-900 hover:text-orange-600 transition"><?php echo htmlspecialchars($r['username']); ?></a>
                    </div>
                </div>

                <div class="h-8 w-px bg-gray-200"></div>

                <div class="flex items-center gap-4 text-sm font-medium text-gray-600">
                    <span class="flex items-center gap-1"><i class="far fa-clock text-orange-500"></i> <?php echo $r['prep_time']; ?> min</span>
                    <span class="flex items-center gap-1"><i class="fas fa-fire text-orange-500"></i> <?php echo $r['calories']; ?> kcal</span>
                    <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo number_format($avg, 1); ?></span>
                </div>
            </div>

            <?php if($uid): ?>
            <div class="flex gap-3 w-full md:w-auto">
                <form method="post" class="w-full md:w-auto">
                    <button name="toggle_fav" class="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold transition border <?php echo $isFav ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'; ?>">
                        <i class="<?php echo $isFav ? 'fas' : 'far'; ?> fa-heart text-xl"></i>
                        <?php echo $isFav ? 'Saved' : 'Save'; ?>
                    </button>
                </form>

                <button onclick="document.getElementById('sendModal').classList.remove('hidden')" class="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold bg-blue-600 text-white hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="p-8 grid md:grid-cols-3 gap-12">
            
            <div class="md:col-span-1">
                <h3 class="font-bold text-xl text-gray-900 mb-6">Ingredients</h3>
                <div class="space-y-3">
                    <?php 
                    // MALZEME LİSTESİ TEMİZLİĞİ
                    $rawIng = str_replace(['[', ']', "'", '"'], '', $r['ingredients']);
                    
                    foreach(explode(',', $rawIng) as $i): 
                        if(trim($i) == '') continue;
                    ?>
                        <label class="flex items-start gap-3 cursor-pointer group p-2 hover:bg-gray-50 rounded-lg transition">
                            <input type="checkbox" class="mt-1 w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 leading-snug"><?php echo htmlspecialchars(trim($i)); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="md:col-span-2">
                <h3 class="font-bold text-xl text-gray-900 mb-6">Instructions</h3>
                <div class="prose max-w-none text-gray-600 leading-loose space-y-4">
                    <?php 
                        $rawIns = str_replace(['[', ']', "'", '"'], '', $r['instructions']);
                        $steps = explode('.', $rawIns);
                        $stepCount = 1;
                        foreach($steps as $step):
                            if(trim($step) == '') continue;
                    ?>
                        <div class="flex gap-4">
                            <span class="flex-shrink-0 w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold text-sm"><?php echo $stepCount++; ?></span>
                            <p class="text-gray-700"><?php echo trim($step); ?>.</p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-gray-100 my-10"></div>

                <?php if($uid): ?>
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                    <h3 class="font-bold text-lg mb-4 text-gray-800">Rate & Review</h3>
                    
                    <form method="post" class="flex flex-col gap-4">
                        <div class="flex justify-between items-center">
                            <label class="font-bold text-gray-700 text-sm">Your Rating:</label>
                            <select name="score" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-orange-500 p-2">
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Good</option>
                                <option value="3">⭐⭐⭐ Average</option>
                                <option value="2">⭐⭐ Poor</option>
                                <option value="1">⭐ Bad</option>
                            </select>
                            <button name="rate" class="bg-yellow-400 text-white px-4 py-1.5 rounded-lg font-bold text-xs hover:bg-yellow-500">Rate</button>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" name="comment_text" placeholder="Write your review here..." required 
                                   class="flex-1 bg-white border border-gray-300 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
                            <button name="comment" class="bg-gray-900 text-white px-6 py-2 rounded-xl font-bold hover:bg-black transition shadow-lg">Post</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div>
                    <h3 class="font-bold text-xl mb-6">Reviews (<?php echo count($comments); ?>)</h3>
                    <div class="space-y-6">
                        <?php foreach($comments as $c): ?>
                            <div class="flex gap-4">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-gray-500 text-sm">
                                    <?php echo substr($c['username'],0,1); ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($c['username']); ?></span>
                                        <span class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></span>
                                    </div>
                                    <p class="text-gray-700 text-sm"><?php echo htmlspecialchars($c['comment']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(count($comments)==0) echo "<p class='text-gray-400 text-sm italic'>No reviews yet. Be the first!</p>"; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div id="sendModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl w-96 shadow-2xl">
        <h3 class="font-bold text-xl mb-4 text-gray-800">Send to Friend</h3>
        <form method="post">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Select Friend</label>
            <select name="receiver_id" class="w-full border bg-gray-50 p-2.5 rounded-xl mb-4 focus:ring-2 focus:ring-blue-500 outline-none" required>
                <?php foreach($friends as $f): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['username']); ?></option>
                <?php endforeach; ?>
                <?php if(empty($friends)) echo "<option disabled selected>No friends (Follow someone first)</option>"; ?>
            </select>
            
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Message</label>
            <textarea name="message" rows="3" class="w-full border bg-gray-50 p-3 rounded-xl mb-4 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Hey! You must try this..."></textarea>
            
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('sendModal').classList.add('hidden')" class="flex-1 bg-gray-100 py-2 rounded-xl font-bold text-gray-600 hover:bg-gray-200">Cancel</button>
                <button name="send_recipe" class="flex-1 bg-blue-600 text-white py-2 rounded-xl font-bold hover:bg-blue-700" <?php echo empty($friends)?'disabled':''; ?>>Send</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>