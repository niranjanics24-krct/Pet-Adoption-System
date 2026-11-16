<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$petId = (int)($_GET['id'] ?? ($_POST['pet_id'] ?? 0));
$isEditing = $petId > 0;

$pageTitle = ($isEditing ? 'Edit Pet Profile' : 'Add New Pet Profile') . ' | Pet Haven';
$activePage = 'admin_pets';

$name = '';
$type = 'Dog';
$breed = '';
$age = '';
$gender = 'Female';
$description = '';
$status = 'Available';
$image = '';

// If editing, load current pet record
if ($isEditing && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $petId);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pet) {
        flash_set('error', 'Pet record not found.');
        header('Location: admin_pets.php');
        exit;
    }

    $name = $pet['name'];
    $type = $pet['type'];
    $breed = $pet['breed'];
    $age = $pet['age'];
    $gender = $pet['gender'];
    $description = $pet['description'];
    $status = $pet['status'];
    $image = $pet['image'];
}

$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Security token invalid or expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');
    $existingImage = trim($_POST['existing_image'] ?? '');
    $imageUrlInput = trim($_POST['image_url'] ?? '');

    if ($name === '') {
        $errors[] = 'Pet name is required.';
    }

    // Process Image Upload
    $uploadedImagePath = $existingImage;

    // Check if an image URL was provided
    if (!empty($imageUrlInput)) {
        $uploadedImagePath = $imageUrlInput;
    }

    // Check if a file was uploaded
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['pet_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading image file. Code: ' . $file['error'];
        } else {
            // Check file size (5MB max)
            $maxBytes = 5 * 1024 * 1024;
            if ($file['size'] > $maxBytes) {
                $errors[] = 'Image file size exceeds the 5MB limit.';
            }

            // Verify MIME type using finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!array_key_exists($mime, $allowedMimes)) {
                $errors[] = 'Invalid image format. Allowed formats: JPEG, PNG, and WebP.';
            } else {
                // Ensure target directory exists
                $uploadDir = __DIR__ . '/uploads/pets/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $ext = $allowedMimes[$mime];
                $uniqueName = 'pet_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetFile = $uploadDir . $uniqueName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $uploadedImagePath = 'uploads/pets/' . $uniqueName;
                } else {
                    $errors[] = 'Failed to save uploaded image on the server.';
                }
            }
        }
    }

    // If adding a new pet and no image was specified, use default fallback
    if (empty($uploadedImagePath)) {
        $uploadedImagePath = 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80';
    }

    if (empty($errors)) {
        if ($isEditing) {
            $stmt = $conn->prepare("UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, gender = ?, description = ?, image = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $name, $type, $breed, $age, $gender, $description, $uploadedImagePath, $status, $petId);
            if ($stmt->execute()) {
                flash_set('success', "Pet profile for " . htmlspecialchars($name) . " updated successfully!");
                header('Location: admin_pets.php');
                exit;
            } else {
                $errors[] = 'Failed to update pet: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO pets (name, type, breed, age, gender, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $type, $breed, $age, $gender, $description, $uploadedImagePath, $status);
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                flash_set('success', "Pet profile for " . htmlspecialchars($name) . " added successfully with ID #$newId!");
                header('Location: admin_pets.php');
                exit;
            } else {
                $errors[] = 'Failed to add pet: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 14px; color: var(--text-muted);">
        <a href="admin_dashboard.php">Admin Panel</a> &rsaquo; 
        <a href="admin_pets.php">Pets Inventory</a> &rsaquo; 
        <span style="color:var(--text-dark); font-weight:600;"><?php echo $isEditing ? 'Edit Pet #' . $petId : 'Add New Pet'; ?></span>
    </div>

    <div class="content-card" style="padding:32px;">
        <h1 style="font-size:26px; color:var(--primary-forest); margin-bottom:8px;">
            <?php echo $isEditing ? '✏️ Edit Pet: ' . htmlspecialchars($name) : '🐾 Add a New Pet to Catalog'; ?>
        </h1>
        <p style="color:var(--text-muted); font-size:15px; margin-bottom:25px;">
            Provide complete details, health highlights, and a high-quality photo to help this rescue companion find their home.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0; padding-left:20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_pet_form.php<?php echo $isEditing ? '?id=' . $petId : ''; ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pet_id" value="<?php echo $petId; ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($image); ?>">

            <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:20px;">
                <div class="form-group">
                    <label for="name">Pet Name *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Bella" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="type">Animal Species *</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="Dog" <?php echo $type === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="Cat" <?php echo $type === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="Rabbit" <?php echo $type === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="Bird" <?php echo $type === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="Other" <?php echo $type === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="breed">Breed</label>
                    <input type="text" id="breed" name="breed" class="form-control" placeholder="e.g. Golden Retriever" value="<?php echo htmlspecialchars($breed); ?>">
                </div>

                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="text" id="age" name="age" class="form-control" placeholder="e.g. 2 years, 6 months" value="<?php echo htmlspecialchars($age); ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Unknown" <?php echo $gender === 'Unknown' ? 'selected' : ''; ?>>Unknown</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Adoption Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending Adoption</option>
                    <option value="Trial" <?php echo $status === 'Trial' ? 'selected' : ''; ?>>In Trial Period</option>
                    <option value="Adopted" <?php echo $status === 'Adopted' ? 'selected' : ''; ?>>Adopted (Success)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Pet Biography & Temperament Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe the pet's personality, energy level, compatibility with kids/other pets, and health notes..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <!-- Image Upload & Preview Box -->
            <div style="background:var(--bg-cream); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; margin-bottom:25px;">
                <label style="font-size:15px; color:var(--primary-forest); margin-bottom:10px;">📸 Pet Photograph</label>
                
                <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                    <div>
                        <img 
                            id="pet_image_preview" 
                            src="<?php echo htmlspecialchars($image ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=200&q=80'); ?>" 
                            alt="Preview" 
                            style="width:120px; height:120px; border-radius:10px; object-fit:cover; border:2px solid white; box-shadow:var(--shadow-sm);"
                        >
                    </div>

                    <div style="flex:1; min-width:260px;">
                        <div class="form-group" style="margin-bottom:12px;">
                            <label for="pet_image_input" style="font-size:13px;">Upload Image File (JPEG, PNG, WebP up to 5MB):</label>
                            <input type="file" id="pet_image_input" name="pet_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label for="image_url" style="font-size:13px;">Or Direct Image URL (optional):</label>
                            <input type="text" id="image_url" name="image_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars(strpos($image, 'http') === 0 ? $image : ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                <a href="admin_pets.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $isEditing ? 'Save Changes' : 'Create Pet Profile →'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
