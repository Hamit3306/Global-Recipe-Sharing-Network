<?php 
// Oturumu başlat (Eğer zaten başlamadıysa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı dosyasını çağır
require_once 'db.php'; 

// Profil Resmini Çek (Varsa)
$userPic = null;
if(function_exists('isLoggedIn') && isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userPicPath = $stmt->fetchColumn();
    
    if ($userPicPath) {
        $userPic = $userPicPath; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeShare Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-green-50 text-gray-800 flex flex-col min-h-screen">

<nav class="bg- shadow-sm sticky top-0 z-50">
    <div class="w-full px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="flex items-center gap-2 text-2xl font-bold text-orange-600 hover:text-orange-700 transition">
            <i class="fas fa-utensils"></i> Kitchen Source
        </a>
        
        <div class="hidden md:flex items-center space-x-8 text-sm font-semibold text-gray-600">
            <a href="index.php" class="hover:text-orange-600 transition">Home</a>
            
            <a href="events.php" class="text-purple-600 hover:text-purple-800 transition flex items-center gap-1">
                <i class="far fa-calendar-alt"></i> Events
            </a>

            <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
                <a href="find_chefs.php" class="text-blue-600 hover:text-blue-800 transition flex items-center gap-1">
                    <i class="fas fa-users"></i> Find Chefs
                </a>
            <?php endif; ?>
            
            <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
                <a href="add_recipe.php" class="hover:text-orange-600 transition">Add Recipe</a>
                
                <a href="profile.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition group">
                    <?php 
                        if($userPic && file_exists($userPic)): 
                    ?>
                        <img src="<?php echo $userPic; ?>" class="w-8 h-8 rounded-full object-cover border border-gray-300">
                    <?php else: ?>
                        <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold text-xs border border-orange-200">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-gray-800 group-hover:text-orange-600 font-bold">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                </a>
                
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="text-red-600 hover:text-red-800">Admin</a>
                <?php endif; ?>
                
                <a href="logout.php" class="bg-gray-100 px-4 py-2 rounded-full hover:bg-red-100 hover:text-red-600 transition">Logout</a>
            <?php else: ?>
                <a href="login.php" class="hover:text-orange-600">Login</a>
                <a href="register.php" class="bg-orange-600 text-white px-5 py-2.5 rounded-full hover:bg-orange-700 transition shadow-lg shadow-orange-200">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="flex-grow"></main>