<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle = 'Pet Haven | Find Your Perfect Companion';
$activePage = 'home';

// Fetch quick statistics
$stats = [
    'available' => 0,
    'adopted' => 0,
    'trials' => 0
];

$rAvailable = $conn->query("SELECT COUNT(*) FROM pets WHERE status = 'Available'");
if ($rAvailable) $stats['available'] = (int)$rAvailable->fetch_row()[0];

$rAdopted = $conn->query("SELECT COUNT(*) FROM pets WHERE status = 'Adopted'");
if ($rAdopted) $stats['adopted'] = (int)$rAdopted->fetch_row()[0];

$rTrials = $conn->query("SELECT COUNT(*) FROM trial_adoptions WHERE status = 'Ongoing'");
if ($rTrials) $stats['trials'] = (int)$rTrials->fetch_row()[0];

// Fetch featured available pets (up to 6)
$stmt = $conn->prepare("SELECT * FROM pets WHERE status = 'Available' ORDER BY id DESC LIMIT 6");
$stmt->execute();
$featuredPets = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="hero-tagline">🐾 A Second Chance at Love</div>
            <h1>Open Your Heart & Home to a Pet in Need</h1>
            <p>
                Pet Haven bridges the gap between rescue animals and responsible adopters. 
                Our science-backed compatibility screening and supportive trial adoptions 
                ensure every adoption is a lifelong success.
            </p>
            <div class="hero-actions">
                <a href="pets.php" class="btn btn-primary btn-lg">Browse Available Pets →</a>
                <?php if (!is_logged_in()): ?>
                    <a href="register.php" class="btn btn-secondary btn-lg">Join as Adopter</a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="btn btn-secondary btn-lg">My Adoption Dashboard</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-img-box">
            <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=900&q=80" alt="Happy dog with human companion">
        </div>
    </div>
</section>

<!-- Quick Stats Bar -->
<section style="background:var(--bg-surface); border-bottom:1px solid var(--border-color); padding: 25px 0;">
    <div class="container">
        <div style="display:flex; justify-content:space-around; align-items:center; flex-wrap:wrap; gap:20px; text-align:center;">
            <div>
                <div style="font-size:32px; font-weight:800; color:var(--primary-forest);"><?php echo $stats['available']; ?></div>
                <div style="font-size:14px; color:var(--text-muted); font-weight:600;">Pets Looking for Homes</div>
            </div>
            <div style="height:35px; width:1px; background:var(--border-color); display:none;" class="divider-desktop"></div>
            <div>
                <div style="font-size:32px; font-weight:800; color:var(--warm-terracotta);">18+</div>
                <div style="font-size:14px; color:var(--text-muted); font-weight:600;">Point Compatibility Test</div>
            </div>
            <div style="height:35px; width:1px; background:var(--border-color); display:none;" class="divider-desktop"></div>
            <div>
                <div style="font-size:32px; font-weight:800; color:#3182CE;">14 Days</div>
                <div style="font-size:14px; color:var(--text-muted); font-weight:600;">Supported Trial Period</div>
            </div>
            <div style="height:35px; width:1px; background:var(--border-color); display:none;" class="divider-desktop"></div>
            <div>
                <div style="font-size:32px; font-weight:800; color:var(--success);"><?php echo max(12, $stats['adopted']); ?>+</div>
                <div style="font-size:14px; color:var(--text-muted); font-weight:600;">Successful Placements</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Available Pets -->
<section style="padding: 60px 0;">
    <div class="container">
        <div class="section-header">
            <h2>Meet Pets Waiting for You</h2>
            <p>Every animal in our care is health-checked, temperament-assessed, and eager for their forever home.</p>
        </div>

        <?php if ($featuredPets && $featuredPets->num_rows > 0): ?>
            <div class="pet-grid">
                <?php while ($pet = $featuredPets->fetch_assoc()): ?>
                    <?php 
                        $img = !empty($pet['image']) ? $pet['image'] : 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80';
                    ?>
                    <div class="pet-card">
                        <div class="pet-card-image">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80'">
                            <span class="pet-status-badge status-available">Available</span>
                        </div>
                        <div class="pet-card-body">
                            <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
                            <div class="pet-meta">
                                <span class="meta-chip"><?php echo htmlspecialchars($pet['type'] ?? 'Pet'); ?></span>
                                <span class="meta-chip"><?php echo htmlspecialchars($pet['breed'] ?? 'Unknown Breed'); ?></span>
                                <span class="meta-chip"><?php echo htmlspecialchars($pet['gender'] ?? 'Unknown'); ?></span>
                                <span class="meta-chip"><?php echo htmlspecialchars($pet['age'] ?? 'Adult'); ?></span>
                            </div>
                            <p class="pet-desc">
                                <?php echo htmlspecialchars($pet['description'] ?: 'A sweet and loving companion ready to join a caring home.'); ?>
                            </p>
                            <div class="pet-card-footer">
                                <a href="pet_details.php?id=<?php echo (int)$pet['id']; ?>" class="btn btn-outline btn-sm">Learn More</a>
                                <a href="eligibility_test.php?pet_id=<?php echo (int)$pet['id']; ?>" class="btn btn-primary btn-sm">Adopt Me →</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div style="text-align:center; margin-top:15px;">
                <a href="pets.php" class="btn btn-outline btn-lg">View All Available Pets →</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🐶</div>
                <h3>No Pets Currently Listed as Available</h3>
                <p>All of our furry companions have found homes or our shelter team is preparing new profiles.</p>
                <?php if (is_admin()): ?>
                    <a href="admin_pet_form.php" class="btn btn-primary">Add a New Pet Profile</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Adoption Process Steps -->
<section id="process" style="padding: 60px 0; background: var(--bg-surface); border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <h2>Our 4-Step Adoption Journey</h2>
            <p>We believe in thoughtful, transparent adoptions that prioritize pet welfare and adopter confidence.</p>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:25px;">
            <div class="content-card" style="text-align:center; border-top:4px solid var(--primary-forest);">
                <div style="font-size:38px; margin-bottom:12px;">🔍</div>
                <h3 style="font-size:18px; color:var(--primary-forest); margin-bottom:10px;">1. Find Your Match</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Explore our searchable profiles. Filter by species, age, temperament, and activity level to find a pet that fits your home.
                </p>
            </div>

            <div class="content-card" style="text-align:center; border-top:4px solid var(--warm-terracotta);">
                <div style="font-size:38px; margin-bottom:12px;">📋</div>
                <h3 style="font-size:18px; color:var(--warm-terracotta); margin-bottom:10px;">2. Eligibility Assessment</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Complete our comprehensive 18-question questionnaire covering living space, schedule, veterinary readiness, and lifelong commitment.
                </p>
            </div>

            <div class="content-card" style="text-align:center; border-top:4px solid var(--sage-accent);">
                <div style="font-size:38px; margin-bottom:12px;">🤝</div>
                <h3 style="font-size:18px; color:var(--sage-accent); margin-bottom:10px;">3. Shelter Review</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Our adoption coordinators review your questionnaire, discuss compatibility, and guide you through essential care preparations.
                </p>
            </div>

            <div class="content-card" style="text-align:center; border-top:4px solid var(--success);">
                <div style="font-size:38px; margin-bottom:12px;">🏡</div>
                <h3 style="font-size:18px; color:var(--success); margin-bottom:10px;">4. Trial & Forever Home</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Begin an agreed trial period at home. Once pet and family bond seamlessly, finalize the adoption certificate and celebrate!
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Responsible Pet Ownership Guide -->
<section id="responsible" style="padding: 60px 0;">
    <div class="container">
        <div class="section-header">
            <h2>Keys to Responsible Pet Ownership</h2>
            <p>Bringing a pet into your life is a joyful, 10 to 18-year commitment. Here is how to ensure a flourishing partnership.</p>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px;">
            <div class="content-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <span style="font-size:28px;">🥗</span>
                    <h3 style="font-size:17px; color:var(--primary-forest);">Proper Nutrition & Hydration</h3>
                </div>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Feed age-appropriate, nutritionally balanced food. Keep clean, fresh drinking water accessible at all times and avoid toxic human foods (chocolate, onions, grapes).
                </p>
            </div>

            <div class="content-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <span style="font-size:28px;">🩺</span>
                    <h3 style="font-size:17px; color:var(--primary-forest);">Proactive Veterinary Care</h3>
                </div>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Keep up with annual wellness exams, vaccinations, flea/tick/heartworm preventatives, microchipping, and timely spaying or neutering.
                </p>
            </div>

            <div class="content-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <span style="font-size:28px;">🎾</span>
                    <h3 style="font-size:17px; color:var(--primary-forest);">Exercise & Mental Enrichment</h3>
                </div>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Daily walks, puzzle toys, and positive interaction prevent boredom and anxiety. Mental stimulation is just as vital as physical play.
                </p>
            </div>

            <div class="content-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <span style="font-size:28px;">❤️</span>
                    <h3 style="font-size:17px; color:var(--primary-forest);">Patience & Lifelong Dedication</h3>
                </div>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                    Rescue animals often require several weeks (the 3-3-3 rule) to decompress and trust. Patience, consistent routines, and gentle reinforcement build unshakable bonds.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
