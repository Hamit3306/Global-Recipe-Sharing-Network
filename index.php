<?php 
require_once 'db.php'; 

// Admin ID'yi al (Import edilen tariflerin sahibi)
$adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn() ?: 1;

// --- HAFTALIK ETKİNLİK OTOMASYONU ---
$currW = date('W'); 
$dbW = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='current_week'")->fetchColumn();

if ($dbW != $currW) {
    $pdo->exec("DELETE FROM events");
    $recipes = $pdo->query("SELECT id FROM recipes ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
    $locs = ["Gourmet Garden", "Chef's Table", "City Lounge", "Seaside Bistro", "Rustic Kitchen"];
    $ins = $pdo->prepare("INSERT INTO events (recipe_id, event_date, quota, booked, location) VALUES (?, CURDATE() + INTERVAL 3 DAY, 5, 0, ?)");
    foreach ($recipes as $rId) {
        $ins->execute([$rId, $locs[array_rand($locs)]]);
    }
    $pdo->prepare("UPDATE system_settings SET setting_value=? WHERE setting_key='current_week'")->execute([$currW]);
}

include 'header.php'; 

// Favorileri Çek
$myFavs = [];
if (isset($_SESSION['user_id'])) {
    $myFavs = $pdo->query("SELECT recipe_id FROM favorites WHERE user_id=" . $_SESSION['user_id'])->fetchAll(PDO::FETCH_COLUMN);
}

// --- FİLTRELERİ AL ---
$search = $_GET['q'] ?? '';
$f_cuisine = $_GET['cuisine'] ?? '';
$f_diet = $_GET['diet'] ?? '';
$f_time = $_GET['time'] ?? '';
$f_diff = $_GET['difficulty'] ?? '';
$f_type = $_GET['type'] ?? ''; 

// --- SORGUTU OLUŞTUR (YENİ AKILLI ARAMA MANTIĞI İLE) ---
$sql = "SELECT r.*, u.username, u.profile_pic, u.role 
        FROM recipes r 
        JOIN users u ON r.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($search) { $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($f_diff) { $sql .= " AND r.difficulty = ?"; $params[] = $f_diff; }
if ($f_time) { $sql .= " AND r.prep_time <= ?"; $params[] = $f_time; }

// YENİ: CUISINE (MUTFAK) FİLTRESİ - AKILLI ARAMA
if ($f_cuisine) { 
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)"; 
    $params[] = "%$f_cuisine%"; 
    $params[] = "%$f_cuisine%"; 
}

// YENİ: DIET (DİYET) FİLTRESİ - AKILLI ARAMA
if ($f_diet) { 
    // Diyet sorguları daha dikkatli olmalı, kelimenin sadece bir bölümünü arayabilir.
    // Örneğin "Gluten-Free" seçilirse, başlıkta 'Gluten' kelimesi olanlar da gelir.
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)"; 
    $params[] = "%$f_diet%"; 
    $params[] = "%$f_diet%"; 
}

// MEAL TYPE (TARİF TÜRÜ) FİLTRESİ - AKILLI ARAMA (Zaten doğruydu, yerini değiştirdik)
if ($f_type) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$f_type%";
    $params[] = "%$f_type%";
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Sidebar Etkinlikleri
$sidebarEvents = $pdo->query("SELECT e.*, r.title FROM events e JOIN recipes r ON e.recipe_id=r.id WHERE (e.quota - e.booked) > 0 ORDER BY (e.quota - e.booked) DESC LIMIT 3")->fetchAll();

// --- ÖNE ÇIKAN TARİFLERİ ÇEK (GÜVENLİ SÜRÜM) ---
$featuredRecipes = $pdo->query("
    SELECT r.*, u.username 
    FROM recipes r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY RAND() 
    LIMIT 4
")->fetchAll();

// --- RASTGELE TARİFLERİ ÇEK (KEŞFETME BLOĞU İÇİN) ---
$randomRecipes = $pdo->query("
    SELECT r.id, r.title, r.image, r.prep_time, r.cuisine 
    FROM recipes r 
    ORDER BY RAND() 
    LIMIT 3
")->fetchAll();
?>

<div class="w-full px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8 mt-8">

    <div class="flex-1">

        <div class="mb-10 p-6 bg-white rounded-2xl shadow-lg border border-orange-50 relative">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-dice-three text-orange-600"></i> Explore New Recipes
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php 
                foreach ($randomRecipes as $rr): 
                ?>
                <a href="detail.php?id=<?php echo $rr['id']; ?>" class="p-3 bg-gray-50 rounded-xl hover:bg-orange-100 transition border border-gray-200 hover:border-orange-300 flex items-center gap-3">
                    <img src="uploads/<?php echo htmlspecialchars($rr['image']); ?>" class="w-16 h-16 object-cover rounded-full shadow-md">
                    <div>
                        <h4 class="font-bold text-base text-gray-900 line-clamp-1"><?php echo htmlspecialchars($rr['title']); ?></h4>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="text-orange-600 font-medium"><?php echo $rr['cuisine']; ?></span> | <?php echo $rr['prep_time']; ?> min
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 flex justify-end">
                <a href="index.php" class="bg-orange-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-orange-700 transition shadow-md flex items-center gap-2">
                    <i class="fas fa-redo"></i> Next Recipes
                </a>
            </div>
        </div>
        <?php if (!empty($featuredRecipes)): ?>
        <div class="mb-10 p-6 bg-white rounded-2xl shadow-lg border border-red-50 relative overflow-hidden">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-3">
                <i class="fas fa-heart text-red-600"></i> Top-Rated Recipes
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php foreach ($featuredRecipes as $fr): ?>
                <a href="detail.php?id=<?php echo $fr['id']; ?>" class="block p-4 rounded-xl hover:bg-red-50 transition border border-gray-100 hover:border-red-200">
                    <div class="flex items-center gap-4">
                        <img src="uploads/<?php echo htmlspecialchars($fr['image']); ?>" class="w-20 h-20 object-cover rounded-lg shadow-md">
                        <div>
                            <h4 class="font-bold text-base text-gray-900 line-clamp-2 hover:text-red-700 transition"><?php echo htmlspecialchars($fr['title']); ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-thumbs-up text-red-400"></i> Featured Recipe
                            </p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="mb-10 p-5 bg-white rounded-2xl shadow-lg border border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Detailed Search & Filters</h3>
            <form action="index.php" method="GET" class="flex flex-wrap gap-3 items-center">
                
                <div class="flex-1 min-w-[200px] relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search recipes..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-orange-500 text-sm transition">
                </div>
                
                <select name="type" class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none cursor-pointer hover:bg-gray-100 font-medium text-gray-700">
                    <option value="">🍽️ Meal Type</option>
                    <option value="Main Course" <?php echo $f_type=='Main Course'?'selected':''; ?>>Main Course</option>
                    <option value="Starter" <?php echo $f_type=='Starter'?'selected':''; ?>>Starter / Appetizer</option>
                    <option value="Soup" <?php echo $f_type=='Soup'?'selected':''; ?>>Soup</option>
                    <option value="Salad" <?php echo $f_type=='Salad'?'selected':''; ?>>Salad</option>
                    <option value="Dessert" <?php echo $f_type=='Dessert'?'selected':''; ?>>Dessert</option>
                    <option value="Drink" <?php echo $f_type=='Drink'?'selected':''; ?>>Drink / Smoothie</option>
                    <option value="Breakfast" <?php echo $f_type=='Breakfast'?'selected':''; ?>>Breakfast</option>
                </select>

                <select name="cuisine" class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none cursor-pointer hover:bg-gray-100">
                    <option value="">🌍 All Cuisines</option>
                    <option value="Italian" <?php echo $f_cuisine=='Italian'?'selected':''; ?>>Italian</option>
                    <option value="Asian" <?php echo $f_cuisine=='Asian'?'selected':''; ?>>Asian</option>
                    <option value="Mexican" <?php echo $f_cuisine=='Mexican'?'selected':''; ?>>Mexican</option>
                    <option value="American" <?php echo $f_cuisine=='American'?'selected':''; ?>>American</option>
                    <option value="French" <?php echo $f_cuisine=='French'?'selected':''; ?>>French</option>
                    <option value="Indian" <?php echo $f_cuisine=='Indian'?'selected':''; ?>>Indian</option>
                    <option value="Japanese" <?php echo $f_cuisine=='Japanese'?'selected':''; ?>>Japanese</option>
                    <option value="Turkish" <?php echo $f_cuisine=='Turkish'?'selected':''; ?>>Turkish</option>
                    <option value="Mediterranean" <?php echo $f_cuisine=='Mediterranean'?'selected':''; ?>>Mediterranean</option>
                </select>

                <select name="diet" class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none cursor-pointer hover:bg-gray-100">
                    <option value="">🥗 Diet</option>
                    <option value="Vegan" <?php echo $f_diet=='Vegan'?'selected':''; ?>>Vegan</option>
                    <option value="Vegetarian" <?php echo $f_diet=='Vegetarian'?'selected':''; ?>>Vegetarian</option>
                    <option value="Gluten-Free" <?php echo $f_diet=='Gluten-Free'?'selected':''; ?>>Gluten-Free</option>
                </select>

                <select name="time" class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none cursor-pointer hover:bg-gray-100">
                    <option value="">⏱️ Time</option>
                    <option value="30">< 30 min</option>
                    <option value="45">< 45 min</option>
                    <option value="60">< 1 Hour</option>
                </select>

                <select name="difficulty" class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none cursor-pointer hover:bg-gray-100">
                    <option value="">📊 Level</option>
                    <option value="Easy" <?php echo $f_diff=='Easy'?'selected':''; ?>>Easy</option>
                    <option value="Medium" <?php echo $f_diff=='Medium'?'selected':''; ?>>Medium</option>
                    <option value="Hard" <?php echo $f_diff=='Hard'?'selected':''; ?>>Hard</option>
                </select>
                
                <button type="submit" class="bg-gray-900 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-black transition text-sm shadow-md">Apply</button>
                <?php if($search || $f_cuisine || $f_diet || $f_time || $f_diff || $f_type): ?>
                    <a href="index.php" class="text-red-500 text-sm hover:underline font-semibold flex items-center gap-1"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <h2 class="text-2xl font-extrabold text-gray-900 mb-4">All Recipes</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
            <?php foreach ($recipes as $r): 
                $isFav = in_array($r['id'], $myFavs);
            ?>
                <div class="group bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col relative hover:-translate-y-1">
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="index.php?action=fav&id=<?php echo $r['id']; ?>" class="absolute top-2 left-2 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/95 shadow-md hover:scale-110 transition cursor-pointer group-hover:opacity-100">
                        <i class="<?php echo $isFav ? 'fas text-red-500' : 'far text-gray-400'; ?> fa-heart text-lg"></i>
                    </a>
                    <?php endif; ?>

                    <a href="detail.php?id=<?php echo $r['id']; ?>" class="block h-full">
                        <div class="relative h-44 overflow-hidden">
                            <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            
                            <?php if($r['diet']!=='Standard'): ?>
                                <span class="absolute bottom-2 right-2 bg-black/60 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-1">
                                    <i class="fas fa-leaf"></i> <?php echo $r['diet']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4 flex flex-col flex-grow">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-bold px-2 py-1 rounded bg-orange-50 text-orange-600 uppercase tracking-wide"><?php echo $r['cuisine']; ?></span>
                                <span class="text-[10px] text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded"><i class="far fa-clock"></i> <?php echo $r['prep_time']; ?>m</span>
                            </div>
                            
                            <h3 class="font-bold text-gray-800 text-sm mb-1 leading-snug line-clamp-2 group-hover:text-orange-600 transition"><?php echo htmlspecialchars($r['title']); ?></h3>
                            
                            <?php 
                                $authorName = $r['username'];
                                $authorPic = $r['profile_pic'];

                                if ($r['user_id'] == $adminId) {
                                    $authorName = "Kitchen Source"; 
                                    $authorPic = null; 
                                }
                            ?>

                            <div class="mt-auto pt-3 flex items-center gap-2 border-t border-gray-50">
                                <?php if ($authorPic && file_exists($authorPic)): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($authorPic); ?>" class="w-6 h-6 rounded-full object-cover border border-gray-300">
                                <?php elseif ($r['user_id'] == $adminId): ?>
                                    <div class="w-6 h-6 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold text-xs border border-orange-200">
                                        K
                                    </div>
                                <?php else: 
                                    $initial = strtoupper(substr($authorName, 0, 1));
                                ?>
                                    <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-600">
                                        <?php echo $initial; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-400 text-xs truncate font-semibold">by <?php echo htmlspecialchars($authorName); ?></p>
                            </div>
                            </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($recipes) == 0): ?>
            <div class="text-center py-24 bg-white rounded-xl border-2 border-dashed border-gray-200 mt-6">
                <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 font-medium">No recipes found matching your criteria.</p>
                <a href="index.php" class="text-orange-600 font-bold hover:underline text-sm mt-2 inline-block">Clear Filters</a>
            </div>
        <?php endif; ?>
        
    </div>
    
    <div class="w-full lg:w-[360px] shrink-0">
        <div class="sticky top-24 space-y-6">
            
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-purple-100 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-50 rounded-full opacity-50 pointer-events-none"></div>

                <div class="flex items-center gap-3 mb-5 relative z-10">
                    <div class="bg-purple-600 p-2 rounded-xl text-white shadow-lg shadow-purple-200"><i class="fas fa-calendar-star"></i></div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Live Events</h3>
                        <p class="text-xs text-purple-600 font-medium">Limited Seats Available</p>
                    </div>
                </div>

                <?php if(count($sidebarEvents) > 0): ?>
                    <div class="space-y-4 relative z-10">
                        <?php foreach($sidebarEvents as $se): ?>
                            <div class="bg-white p-4 rounded-xl border border-gray-200 hover:border-purple-300 hover:shadow-md transition group">
                                <h4 class="font-bold text-sm text-gray-800 mb-2 line-clamp-1 group-hover:text-purple-700 transition"><?php echo htmlspecialchars($se['title']); ?></h4>
                                <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
                                    <span class="flex items-center gap-1"><i class="fas fa-map-pin text-purple-400"></i> <?php echo substr($se['location'], 0, 18); ?>...</span>
                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold"><?php echo ($se['quota'] - $se['booked']); ?> left</span>
                                </div>
                                <a href="detail.php?id=<?php echo $se['recipe_id']; ?>" class="block w-full text-center bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold py-2 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition">View Details & Join</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="events.php" class="block mt-5 text-center text-sm font-bold text-purple-600 hover:text-purple-800 hover:underline transition">View Full Calendar →</a>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="far fa-calendar-times text-3xl mb-2"></i>
                        <p class="text-sm">No events this week.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-red-600 p-6 rounded-2xl shadow-xl text-white text-center relative overflow-hidden">
                <i class="fas fa-utensils text-6xl absolute -bottom-4 -left-4 text-white opacity-20 transform rotate-12"></i>
                <h3 class="font-bold text-xl mb-1 relative z-10">Share Your Recipe</h3>
                <p class="text-xs text-orange-100 mb-4 relative z-10 opacity-90">Inspire thousands of food lovers!</p>
                <a href="add_recipe.php" class="inline-block bg-white text-orange-600 text-sm font-bold px-6 py-2.5 rounded-xl hover:bg-gray-100 transition shadow-lg relative z-10">Create Now</a>
            </div>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>