<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle = 'Available Pets for Adoption | Pet Haven';
$activePage = 'pets';

// Get query parameters for filtering
$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$status = trim($_GET['status'] ?? 'Available');

// Build SQL query dynamically with prepared statement
$whereClauses = [];
$params = [];
$types = '';

if ($search !== '') {
    $whereClauses[] = "(name LIKE ? OR breed LIKE ? OR description LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($type !== '' && $type !== 'All') {
    $whereClauses[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($gender !== '' && $gender !== 'All') {
    $whereClauses[] = "gender = ?";
    $params[] = $gender;
    $types .= 's';
}

if ($status !== '' && $status !== 'All') {
    $whereClauses[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql = "SELECT * FROM pets";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$petsResult = $stmt->get_result();

// Get unique types from database for filter dropdown
$typesRes = $conn->query("SELECT DISTINCT type FROM pets WHERE type IS NOT NULL AND type != '' ORDER BY type");
$availableTypes = [];
if ($typesRes) {
    while ($r = $typesRes->fetch_assoc()) {
        $availableTypes[] = $r['type'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <div style="margin-bottom: 30px;">
        <h1 style="color:var(--primary-forest); font-size:32px; margin-bottom:6px;">Browse Pets Available for Adoption</h1>
        <p style="color:var(--text-muted);">Use the filters below to find the ideal animal companion for your household.</p>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="pets.php" class="filter-grid">
            <div class="form-group" style="margin-bottom:0;">
                <label for="search">Search by Name or Keyword</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="e.g. Bella, Retriever..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="type">Animal Species</label>
                <select id="type" name="type" class="form-control">
                    <option value="All">All Species</option>
                    <?php foreach ($availableTypes as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="All">Any Gender</option>
                    <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="status">Adoption Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available Only</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending Adoption</option>
                    <option value="Trial" <?php echo $status === 'Trial' ? 'selected' : ''; ?>>In Trial</option>
                    <option value="Adopted" <?php echo $status === 'Adopted' ? 'selected' : ''; ?>>Adopted (Successes)</option>
                    <option value="All" <?php echo $status === 'All' ? 'selected' : ''; ?>>All Statuses</option>
                </select>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Filter</button>
                <a href="pets.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Pet Grid -->
    <?php if ($petsResult && $petsResult->num_rows > 0): ?>
        <div class="pet-grid">
            <?php while ($pet = $petsResult->fetch_assoc()): ?>
                <?php 
                    $img = !empty($pet['image']) ? $pet['image'] : 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80';
                    $statusClass = 'status-' . strtolower($pet['status'] ?? 'available');
                ?>
                <div class="pet-card">
                    <div class="pet-card-image">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80'">
                        <span class="pet-status-badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($pet['status']); ?>
                        </span>
                    </div>

                    <div class="pet-card-body">
                        <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
                        <div class="pet-meta">
                            <span class="meta-chip"><?php echo htmlspecialchars($pet['type'] ?? 'Pet'); ?></span>
                            <span class="meta-chip"><?php echo htmlspecialchars($pet['breed'] ?? 'Mixed'); ?></span>
                            <span class="meta-chip"><?php echo htmlspecialchars($pet['gender'] ?? 'Unknown'); ?></span>
                            <span class="meta-chip"><?php echo htmlspecialchars($pet['age'] ?? 'Adult'); ?></span>
                        </div>
                        <p class="pet-desc">
                            <?php echo htmlspecialchars($pet['description'] ?: 'Friendly and awaiting a loving companion.'); ?>
                        </p>

                        <div class="pet-card-footer">
                            <a href="pet_details.php?id=<?php echo (int)$pet['id']; ?>" class="btn btn-outline btn-sm">Full Profile</a>
                            <?php if ($pet['status'] === 'Available'): ?>
                                <a href="eligibility_test.php?pet_id=<?php echo (int)$pet['id']; ?>" class="btn btn-primary btn-sm">Adopt Me →</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled style="opacity:0.7; cursor:not-allowed;">
                                    <?php echo htmlspecialchars($pet['status']); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔎</div>
            <h3>No Pets Found Matching Your Search</h3>
            <p>Try broadening your filter criteria or view all available pets.</p>
            <a href="pets.php" class="btn btn-primary">Clear All Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
