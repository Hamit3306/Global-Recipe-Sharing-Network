<?php
include 'header.php';

// --- BOOKING LOGIC (Resource Consumption) ---
if (isset($_POST['book']) && isset($_SESSION['user_id'])) {
    $eid = (int)$_POST['eid']; 
    $uid = $_SESSION['user_id'];
    
    // Check event details
    $evt = $pdo->query("SELECT * FROM events WHERE id=$eid")->fetch();
    
    // Check if already booked
    $chk = $pdo->query("SELECT * FROM event_bookings WHERE event_id=$eid AND user_id=$uid");
    
    if ($chk->rowCount() == 0 && $evt['booked'] < $evt['quota']) {
        // 1. Create Booking
        $pdo->exec("INSERT INTO event_bookings (event_id, user_id) VALUES ($eid, $uid)");
        // 2. Consume Resource (Reduce Stock)
        $pdo->exec("UPDATE events SET booked = booked + 1 WHERE id = $eid");
        
        echo "<script>alert('Seat booked successfully! The remaining spots have decreased.'); window.location.href='events.php';</script>";
    } else { 
        echo "<script>alert('Booking failed! Event is full or you already joined.');</script>"; 
    }
}

// Fetch Events sorted by availability
$events = $pdo->query("SELECT e.*, r.title, r.image, r.id as rid FROM events e JOIN recipes r ON e.recipe_id=r.id ORDER BY (e.quota-e.booked) DESC")->fetchAll();
?>

<div class="max-w-6xl mx-auto mt-10 px-4 mb-20">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900">📅 Live Tasting Events</h1>
        <p class="text-gray-500 mt-2">Join our exclusive weekly tasting sessions. <span class="text-purple-600 font-bold">Limited seats available!</span></p>
    </div>

    <?php if (count($events) > 0): ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($events as $e): 
                $remaining = $e['quota'] - $e['booked'];
                $isFull = $remaining <= 0;
            ?>
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden group hover:-translate-y-1 transition duration-300">
                <div class="relative h-48">
                    <img src="uploads/<?php echo $e['image']; ?>" class="w-full h-full object-cover">
                    <div class="absolute top-3 left-3 bg-white/90 backdrop-blur px-3 py-1 rounded-lg text-xs font-bold text-purple-700 shadow">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($e['location']); ?>
                    </div>
                </div>

                <div class="p-5">
                    <h3 class="font-bold text-lg text-gray-900 mb-1 truncate"><?php echo htmlspecialchars($e['title']); ?></h3>
                    
                    <div class="mt-4 mb-2 flex justify-between text-sm font-semibold">
                        <span class="text-gray-500">Availability:</span>
                        <span class="<?php echo $isFull ? 'text-red-500' : 'text-green-600'; ?>">
                            <?php echo $isFull ? 'SOLD OUT' : "$remaining / {$e['quota']} Left"; ?>
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden mb-4">
                        <div class="h-full <?php echo $isFull ? 'bg-red-500' : 'bg-green-500'; ?>" style="width: <?php echo ($e['booked'] / $e['quota']) * 100; ?>%"></div>
                    </div>

                    <?php if(!$isFull && isset($_SESSION['user_id'])): ?>
                        <form method="post">
                            <input type="hidden" name="eid" value="<?php echo $e['id']; ?>">
                            <button name="book" class="w-full bg-purple-600 text-white py-2 rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200">
                                Book a Seat
                            </button>
                        </form>
                    <?php elseif(!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="block text-center text-purple-600 font-bold text-sm underline">Login to Book</a>
                    <?php else: ?>
                        <button disabled class="w-full bg-gray-300 text-gray-500 py-2 rounded-xl font-bold cursor-not-allowed">Full</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-500">No events scheduled for this week.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>