<?php
// import_csv.php - DATA LOADER (English)
require_once 'db.php';

// Allow unlimited execution time for large files
set_time_limit(0); 
ini_set('memory_limit', '512M');

$csvFile = 'recipes_data.csv';

if (!file_exists($csvFile)) {
    die("<h3 style='color:red'>ERROR: 'recipes_data.csv' file not found!</h3>");
}

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    echo "<h3>🚀 Loading Data... Please wait.</h3>";
    
    // Clear old data to prevent duplicates
    $pdo->exec("DELETE FROM recipes");
    $pdo->exec("ALTER TABLE recipes AUTO_INCREMENT = 1");
    
    // Skip header row
    fgetcsv($handle);
    
    // Get Admin ID
    $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn() ?: 1;

    // Begin Transaction for speed
    $pdo->beginTransaction();
    $sql = "INSERT INTO recipes (user_id, title, ingredients, instructions, image, description, difficulty, cuisine, diet, calories, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    $count = 0;
    while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
        // Stop after 1000 recipes for performance
        if ($count >= 1000) break;
        
        // Fix Column Shift (Detect if column 0 is ID)
        $offset = is_numeric($data[0]) ? 1 : 0;
        
        $title = $data[0+$offset] ?? 'Untitled';
        $ingredients = $data[1+$offset] ?? '';
        $instructions = $data[2+$offset] ?? '';
        $rawImage = $data[3+$offset] ?? 'default';
        
        // FIX IMAGE EXTENSION (Add .jpg if missing)
        if (strpos($rawImage, '.jpg') === false && strpos($rawImage, '.png') === false) {
            $image = $rawImage . ".jpg";
        } else {
            $image = $rawImage;
        }
        
        // Generate Description
        $description = substr($instructions, 0, 150) . "...";
        
        // Determine Difficulty based on ingredient count
        $diff = (substr_count($ingredients, ',') < 8) ? 'Easy' : 'Medium';
        
        // Guess Cuisine based on keywords
        $text = strtolower($title . ' ' . $ingredients);
        $cuisine = 'General';
        if (strpos($text, 'pasta')!==false || strpos($text, 'pizza')!==false) $cuisine = 'Italian';
        elseif (strpos($text, 'soy')!==false || strpos($text, 'rice')!==false) $cuisine = 'Asian';
        elseif (strpos($text, 'taco')!==false || strpos($text, 'bean')!==false) $cuisine = 'Mexican';
        elseif (strpos($text, 'burger')!==false) $cuisine = 'American';
        
        // Guess Diet (Simple Logic)
        $diet = 'Standard';
        $meat = ['chicken','beef','pork','fish','meat','bacon'];
        $hasMeat = false;
        foreach($meat as $m) { if(strpos($text, $m)!==false) $hasMeat=true; }
        
        if(!$hasMeat) {
            $diet = (strpos($text, 'cheese')===false && strpos($text, 'egg')===false && strpos($text, 'milk')===false) ? 'Vegan' : 'Vegetarian';
        }

        // Random Stats for display
        $calories = rand(200, 900);
        $time = rand(15, 90);

        $stmt->execute([$adminId, $title, $ingredients, $instructions, $image, $description, $diff, $cuisine, $diet, $calories, $time]);
        $count++;
    }
    
    $pdo->commit();
    fclose($handle);
    
    echo "<h2>✅ Success! Imported $count recipes.</h2>";
    echo "<a href='index.php' style='background:orange; color:white; padding:10px; text-decoration:none; border-radius:5px;'>Go to Homepage</a>";
}
?>