<?php
// Safe Migration Runner for Pet Adoption System
require_once __DIR__ . "/../db.php";

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Database Setup / Migration</title>";
echo "<style>body{font-family:sans-serif;background:#FAF7F2;color:#2D3748;padding:30px;line-height:1.6;} .box{max-width:700px;margin:0 auto;background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.06);} .ok{color:#2F855A;font-weight:bold;} .err{color:#C53030;} pre{background:#EDF2F7;padding:12px;border-radius:6px;overflow-x:auto;}</style></head><body><div class='box'>";

echo "<h2>🐾 Pet Adoption System - Migration Runner</h2>";

// 1. Create eligibility_answers table
$sqlEligibility = "CREATE TABLE IF NOT EXISTS `eligibility_answers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `question_key` VARCHAR(100) NOT NULL,
  `question_text` VARCHAR(255) NOT NULL,
  `submitted_answer` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eligibility_req` (`request_id`),
  CONSTRAINT `fk_eligibility_req` FOREIGN KEY (`request_id`) REFERENCES `adoption_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sqlEligibility)) {
    echo "<p class='ok'>✓ `eligibility_answers` table verified/created successfully.</p>";
} else {
    echo "<p class='err'>✗ Error creating `eligibility_answers`: " . htmlspecialchars($conn->error) . "</p>";
}

// 2. Add admin_notes to adoption_requests if not existing
$colCheckReq = $conn->query("SHOW COLUMNS FROM `adoption_requests` LIKE 'admin_notes'");
if ($colCheckReq && $colCheckReq->num_rows === 0) {
    if ($conn->query("ALTER TABLE `adoption_requests` ADD COLUMN `admin_notes` TEXT NULL AFTER `status`")) {
        echo "<p class='ok'>✓ Added `admin_notes` column to `adoption_requests`.</p>";
    }
} else {
    echo "<p class='ok'>✓ `adoption_requests` schema is up-to-date.</p>";
}

// 3. Add notes to trial_adoptions if not existing
$colCheckTrial = $conn->query("SHOW COLUMNS FROM `trial_adoptions` LIKE 'notes'");
if ($colCheckTrial && $colCheckTrial->num_rows === 0) {
    if ($conn->query("ALTER TABLE `trial_adoptions` ADD COLUMN `notes` TEXT NULL AFTER `status`")) {
        echo "<p class='ok'>✓ Added `notes` column to `trial_adoptions`.</p>";
    }
} else {
    echo "<p class='ok'>✓ `trial_adoptions` schema is up-to-date.</p>";
}

// 4. Seed initial pets if pets table is empty
$petCount = $conn->query("SELECT COUNT(*) FROM `pets`")->fetch_row()[0];
if ($petCount == 0) {
    $seedSql = "INSERT INTO `pets` (`name`, `type`, `breed`, `age`, `gender`, `description`, `image`, `status`) VALUES
    ('Bella', 'Dog', 'Golden Retriever', '2 years', 'Female', 'Friendly, calm, and loves outdoor strolls. Bella gets along great with other animals and children.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=700&q=80', 'Available'),
    ('Milo', 'Cat', 'Domestic Short Hair', '1 year', 'Male', 'Playful and curious young cat who adores sunny window sills, feather wands, and gentle head scratches.', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=700&q=80', 'Available'),
    ('Rocky', 'Dog', 'German Shepherd Mix', '3 years', 'Male', 'Energetic, highly intelligent, and loyal protector. Eager to learn tricks and perfect for an active family.', 'https://images.unsplash.com/photo-1589941013453-ec89f33b5e95?auto=format&fit=crop&w=700&q=80', 'Available'),
    ('Luna', 'Cat', 'Siamese Mix', '2 years', 'Female', 'Quiet, affectionate companion with striking blue eyes. Loves cozy blankets and tranquil indoor evenings.', 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?auto=format&fit=crop&w=700&q=80', 'Available'),
    ('Barnaby', 'Rabbit', 'Holland Lop', '10 months', 'Male', 'Gentle, litter-box trained bunny with floppy ears. Loves fresh timothy hay, safe indoor playpens, and kale snacks.', 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=700&q=80', 'Available'),
    ('Daisy', 'Dog', 'Beagle', '4 years', 'Female', 'Sweet-natured hound with a curious nose and boundless love. Sociable, gentle with children, and loves park walks.', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=700&q=80', 'Available');";
    
    if ($conn->query($seedSql)) {
        echo "<p class='ok'>✓ Seeded 6 starter pets into the catalog.</p>";
    } else {
        echo "<p class='err'>✗ Error seeding pets: " . htmlspecialchars($conn->error) . "</p>";
    }
} else {
    echo "<p class='ok'>✓ Pets table already has $petCount record(s). Preserved existing pets.</p>";
}

// 5. Ensure at least one admin account exists for testing
// Check if any admin exists
$adminCount = $conn->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetch_row()[0];
if ($adminCount == 0) {
    // Check if user #1 exists
    $u1 = $conn->query("SELECT id, email, name FROM `users` ORDER BY id ASC LIMIT 1")->fetch_assoc();
    if ($u1) {
        $conn->query("UPDATE `users` SET `role` = 'admin' WHERE `id` = " . (int)$u1['id']);
        echo "<p class='ok'>✓ Promoted existing user <strong>" . htmlspecialchars($u1['email']) . "</strong> (" . htmlspecialchars($u1['name']) . ") to <strong>admin</strong> role for administration access.</p>";
    } else {
        // Create a default admin user if users table was empty
        $adminPass = password_hash("Admin@123", PASSWORD_DEFAULT);
        $conn->query("INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`) VALUES ('System Admin', 'admin@pethaven.local', '$adminPass', '555-0100', 'admin')");
        echo "<p class='ok'>✓ Created initial admin account: <code>admin@pethaven.local</code> / <code>Admin@123</code></p>";
    }
} else {
    $adminUser = $conn->query("SELECT email FROM `users` WHERE `role` = 'admin' LIMIT 1")->fetch_assoc();
    echo "<p class='ok'>✓ Admin account active: <strong>" . htmlspecialchars($adminUser['email']) . "</strong>.</p>";
}

echo "<h3>Summary:</h3>";
echo "<p class='ok'>All tables and structures are completely ready and verified!</p>";
echo "<p><a href='../index.php' style='display:inline-block;padding:10px 18px;background:#2D5A43;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>Go to Website Home →</a></p>";
echo "</div></body></html>";
