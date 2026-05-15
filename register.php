<?php 
// Include header (includes db.php)
include 'header.php'; 

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $error = "This email address is already registered.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert new user into database
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $insert->execute([$username, $email, $hashed_password]);

            // Success: Redirect to login page with javascript alert
            echo "<script>
                alert('Registration successful! You can now log in.');
                window.location.href = 'login.php';
            </script>";
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 mt-10">
    
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
        <p class="text-gray-500 text-sm mt-1">Join our community to share your recipes.</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php">
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
            <input type="text" name="username" placeholder="ChefJohn" required 
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
            <input type="email" name="email" placeholder="name@example.com" required 
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
            <input type="password" name="password" placeholder="••••••••" required minlength="6"
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
            <p class="text-xs text-gray-400 mt-1">Must be at least 6 characters long.</p>
        </div>

        <button type="submit" class="w-full bg-orange-600 text-white py-3 rounded-lg font-bold hover:bg-orange-700 transition shadow-sm">
            Create Account
        </button>

    </form>

    <div class="mt-6 text-center text-sm text-gray-600">
        Already have an account? 
        <a href="login.php" class="text-orange-600 font-bold hover:underline">Log in</a>
    </div>

</div>

</main>
</body>
</html>