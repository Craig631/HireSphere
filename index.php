<?php
require_once "auth/guard.php";
include 'includes/auth.php';
include 'config/database.php';
include 'includes/header.php';
?>

<!-- ADMIN DASHBOARD ACCESS -->
<?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
<div class="container-fluid bg-light border-bottom">
    <div class="container d-flex justify-content-end py-1">
        <a href="/hiresphere/admin/dashboard.php" class="text-decoration-none text-muted small fw-medium">
            <i class="bi bi-speedometer2 me-1"></i> Admin Access
        </a>
    </div>
</div>
<?php endif; ?>

<!-- HERO: a dark promotional banner inspired by the reference design. -->
<section class="hs-premium-hero" aria-labelledby="hsHeroTitle">

    <!-- The existing car photograph fills the right side of the banner. -->
    <div class="hs-hero-visual">
        <img
            src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1800&q=90"
            alt="Silver sports car in a dark showroom"
            class="hs-hero-car"
            fetchpriority="high">
    </div>

    <div class="container hs-hero-container">

        <!-- Text remains separate from the photograph for readability. -->
        <div class="hs-hero-copy">

            <span class="hs-hero-eyebrow">
                <span aria-hidden="true"></span>
                EXPERIENCE HIRESPHERE
            </span>

            <h1 id="hsHeroTitle" class="hs-hero-heading">
                YOUR JOURNEY.<br>
                YOUR RULES.<br>
                <span>YOUR DRIVE.</span>
            </h1>

            <p class="hs-hero-description">
                Discover your next drive. Explore luxury cars, everyday
                favourites, and spacious SUVs for journeys across Kenya.
            </p>

            <!-- Browse scrolls directly to your existing vehicle cards. -->
            <div class="hs-hero-actions">
                <a href="#featured-vehicles" class="hs-hero-primary">
                    Explore vehicles
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>

                <a href="add-vehicle.php" class="hs-hero-secondary">
                    Become a host
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Feature labels avoid displaying invented business statistics. -->
            <div class="hs-hero-highlights" aria-label="Rental highlights">

                <div>
                    <i class="bi bi-car-front" aria-hidden="true"></i>
                    <span>Cars for every journey</span>
                </div>

                <div>
                    <i class="bi bi-phone" aria-hidden="true"></i>
                    <span>Book online</span>
                </div>

                <div>
                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                    <span>Explore Kenya</span>
                </div>

            </div>

        </div>
    </div>

    <!-- Decorative caption reinforces the automotive presentation. -->
    <div class="hs-hero-caption" aria-hidden="true">
        <span class="hs-caption-line"></span>
        MADE FOR THE ROAD
    </div>

</section>

<!-- VEHICLE MARKETPLACE: filters on the left, database listings on the right. -->
<section class="container py-5" id="featured-vehicles">

    <?php
    // Read actual vehicles; no sample listings or invented specifications are added.
    $result = mysqli_query($conn, "SELECT * FROM vehicles ORDER BY id DESC");

    // Escape database text before inserting it into HTML.
    $listingEscape = static function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    ?>

    <div class="row g-4">

        <!-- FILTER SIDEBAR: existing filter IDs keep the JavaScript connected. -->
        <aside class="col-lg-3" aria-label="Vehicle filters">
            <div class="hs-filter-panel">

                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    <h2 class="h5 fw-bold mb-0">Filter vehicles</h2>
                </div>

                <!-- Vehicle type values match the normalized card data attributes. -->
                <div class="mb-4">
                    <label for="filterType" class="form-label fw-semibold">
                        Vehicle type
                    </label>
                    <select class="form-select" id="filterType">
                        <option value="all">All vehicles</option>
                        <option value="suv">SUV</option>
                        <option value="sedan">Sedan</option>
                        <option value="luxury">Luxury</option>
                        <option value="economy">Economy</option>
                        <option value="van">Van</option>
                        <option value="vans">Vans</option>
                        <option value="electric">Electric</option>
                        <option value="sports">Sports</option>
                    </select>
                </div>

                <!-- Prices retain your existing daily-rate filter boundaries. -->
                <div class="mb-4">
                    <label for="filterPrice" class="form-label fw-semibold">
                        Daily budget
                    </label>
                    <select class="form-select" id="filterPrice">
                        <option value="all">Any price</option>
                        <option value="low">Below KES 5,000</option>
                        <option value="mid">KES 5,000–10,000</option>
                        <option value="high">Above KES 10,000</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="filterTransmission" class="form-label fw-semibold">
                        Transmission
                    </label>
                    <select class="form-select" id="filterTransmission">
                        <option value="all">Any transmission</option>
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>

                <!-- Reset restores all listings through your existing filter script. -->
                <button type="button"
                        class="btn btn-outline-primary w-100"
                        id="resetFilters">
                    <i class="bi bi-arrow-counterclockwise me-2"
                       aria-hidden="true"></i>
                    Reset filters
                </button>

            </div>
        </aside>

        <!-- LISTING COLUMN: each database record becomes one horizontal card. -->
        <div class="col-lg-9">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-2 mb-4">
                <div>
                    <span class="hs-listing-eyebrow">FIND YOUR NEXT DRIVE</span>
                    <h2 class="fw-bold mt-2 mb-1">Explore vehicles</h2>
                    <p class="text-muted mb-0">
                        Compare daily rates and find your ideal ride.
                    </p>
                </div>

                <!-- The count updates whenever filters change. -->
                <span class="hs-results-count"
                      id="hsResultsCount"
                      role="status"
                      aria-live="polite">
                    <?= mysqli_num_rows($result) ?> vehicles
                </span>
            </div>

            <div class="hs-listings">

                <?php while ($vehicle = mysqli_fetch_assoc($result)): ?>
                    <?php
                    // Normalize filter values so "SUV" and "suv" compare equally.
                    $listingType = strtolower(trim($vehicle['vehicle_type']));
                    $listingTransmission = strtolower(trim($vehicle['transmission']));
                    $listingId = (int) $vehicle['id'];
                    ?>

                    <!-- Filter data belongs on the entire card so it hides as one unit. -->
                    <article class="hs-listing-card car-card"
                             data-type="<?= $listingEscape($listingType) ?>"
                             data-price="<?= (float) $vehicle['price_per_day'] ?>"
                             data-transmission="<?= $listingEscape($listingTransmission) ?>">

                        <!-- Vehicle image links to the existing details page. -->
                        <a class="hs-listing-photo"
                           href="vehicle-details.php?id=<?= $listingId ?>"
                           aria-label="<?= $listingEscape('View ' . $vehicle['vehicle_name']) ?>">
                            <img
                                src="uploads/<?= $listingEscape($vehicle['image']) ?>"
                                alt="<?= $listingEscape($vehicle['vehicle_name']) ?>"
                                loading="lazy">
                        </a>

                        <div class="hs-listing-body">

                            <!-- Display only details actually stored in your database. -->
                            <div class="hs-listing-top">
                                <div>
                                    <span class="hs-vehicle-type">
                                        <?= $listingEscape($vehicle['vehicle_type']) ?>
                                    </span>

                                    <h3 class="hs-listing-title">
                                        <a href="vehicle-details.php?id=<?= $listingId ?>">
                                            <?= $listingEscape($vehicle['vehicle_name']) ?>
                                        </a>
                                    </h3>
                                </div>

                                <div class="hs-listing-price">
                                    <strong>
                                        KES <?= number_format((float) $vehicle['price_per_day']) ?>
                                    </strong>
                                    <span>per day</span>
                                </div>
                            </div>

                            <!-- Compact specification boxes mirror the reference layout. -->
                            <div class="hs-listing-specs">
                                <div>
                                    <i class="bi bi-car-front" aria-hidden="true"></i>
                                    <span>Vehicle type</span>
                                    <strong>
                                        <?= $listingEscape($vehicle['vehicle_type']) ?>
                                    </strong>
                                </div>

                                <div>
                                    <i class="bi bi-gear" aria-hidden="true"></i>
                                    <span>Transmission</span>
                                    <strong>
                                        <?= $listingEscape($vehicle['transmission']) ?>
                                    </strong>
                                </div>
                            </div>

                            <!-- Keep the existing details-to-booking flow. -->
                            <div class="hs-listing-bottom">
                                <span class="text-muted small">
                                    View details to choose your rental dates.
                                </span>

                                <a href="vehicle-details.php?id=<?= $listingId ?>"
                                   class="btn btn-primary">
                                    View vehicle
                                    <i class="bi bi-arrow-right ms-2"
                                       aria-hidden="true"></i>
                                </a>
                            </div>

                        </div>
                    </article>
                <?php endwhile; ?>

            </div>

            <!-- Covers both an empty database and filters with no matches. -->
            <div id="hsNoResults"
                 class="alert alert-info mt-3"
                 <?= mysqli_num_rows($result) > 0 ? 'hidden' : '' ?>>
                No vehicles match your selection. Try changing the filters.
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>