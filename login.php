<?php 
// Include header (which also includes db.php)
include 'header.php'; 

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password hash
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect to homepage
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 mt-10">
    
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
        <p class="text-gray-500 text-sm mt-1">Please enter your details to sign in.</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
            <input type="email" name="email" placeholder="name@example.com" required 
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
            <input type="password" name="password" placeholder="••••••••" required 
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
        </div>

        <button type="submit" class="w-full bg-orange-600 text-white py-3 rounded-lg font-bold hover:bg-orange-700 transition shadow-sm">
            Sign In
        </button>

    </form>

    <div class="mt-6 text-center text-sm text-gray-600">
        Don't have an account? 
        <a href="register.php" class="text-orange-600 font-bold hover:underline">Sign up</a>
    </div>

</div>

</main>
</body>
</html>