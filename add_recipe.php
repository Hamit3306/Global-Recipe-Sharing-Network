<?php 
// Include header (includes db.php)
include 'header.php'; 

// Security Check: Redirect to login if user is not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize text inputs
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $ingredients = clean($_POST['ingredients']);
    $instructions = clean($_POST['instructions']);
    $user_id = $_SESSION['user_id'];
    
    // File Upload Logic
    $imageName = "";
    $uploadError = "";
    
    // Check if a file was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $fileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        
        // Generate unique filename to prevent overwriting (timestamp_random.jpg)
        $newFileName = time() . "_" . rand(1000, 9999) . "." . $fileType;
        $targetFilePath = $targetDir . $newFileName;
        
        // Allowed file types
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if (in_array($fileType, $allowedTypes)) {
            // Upload file to server
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imageName = $newFileName;
            } else {
                $uploadError = "Error uploading your file.";
            }
        } else {
            $uploadError = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
        }
    } else {
        $uploadError = "Please select an image for your recipe.";
    }

    // Insert into Database if no upload errors
    if (empty($uploadError)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, ingredients, instructions, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $description, $ingredients, $instructions, $imageName]);
            
            // Success: Redirect to home
            echo "<script>alert('Recipe published successfully!'); window.location.href='index.php';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = $uploadError;
    }
}
?>

<div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 mb-10">
    
    <div class="mb-8 border-b border-gray-100 pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Add New Recipe</h1>
        <p class="text-gray-500 mt-1">Share your culinary masterpiece with the world.</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-6">
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Recipe Title</label>
            <input type="text" name="title" placeholder="e.g. Classic Beef Lasagna" required 
                   class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
        </div>

        <div>
            <label class="block text-gray-700 font-bold mb-2">Short Description</label>
            <textarea name="description" rows="2" placeholder="Briefly describe your dish..." required
                      class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Ingredients</label>
                <p class="text-xs text-gray-400 mb-2">List each ingredient on a new line or separated by commas.</p>
                <textarea name="ingredients" rows="8" placeholder="- 500g Pasta&#10;- 2 tbsp Olive Oil&#10;- 1 Onion" required
                          class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"></textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Instructions</label>
                <p class="text-xs text-gray-400 mb-2">Explain the cooking process step-by-step.</p>
                <textarea name="instructions" rows="8" placeholder="1. Boil the water...&#10;2. Chop the vegetables...&#10;3. Mix everything together..." required
                          class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"></textarea>
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-bold mb-2">Recipe Photo</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition bg-gray-50">
                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                <input type="file" name="image" required class="block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-full file:border-0
                  file:text-sm file:font-semibold
                  file:bg-orange-50 file:text-orange-700
                  hover:file:bg-orange-100
                "/>
                <p class="text-xs text-gray-400 mt-2">Allowed: JPG, PNG, WEBP</p>
            </div>
        </div>

        <button type="submit" class="w-full bg-orange-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-orange-700 transition shadow-lg shadow-orange-200">
            Publish Recipe <i class="fas fa-paper-plane ml-2"></i>
        </button>

    </form>
</div>

</main>
</body>
</html>