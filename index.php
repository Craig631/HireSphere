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

<!-- HERO SECTION -->
<div class="hero-section">

    <div class="container">

        <div class="row g-5 align-items-center">

            <div class="col-lg-6">

                <span class="hero-badge">
                    Premium Car Rentals
                </span>

                <h1 class="hero-title mt-4">
                    Find Your Perfect Drive
                </h1>

                <p class="hero-text mt-4">
                    Luxury, economy, SUVs — rent premium cars instantly across Kenya
                </p>

                <!-- AIRBNB-STYLE SEARCH BAR -->
                <div id="main-search-bar" class="main-search-bar">

                    <div class="search-segment">
                        <i class="bi bi-geo-alt segment-icon"></i>
                        <div class="segment-body">
                            <div class="segment-label">Location</div>
                            <input type="text" id="searchLocation" class="segment-input" placeholder="Location near you">
                        </div>
                    </div>

                    <div class="divider-vert" aria-hidden></div>

                    <div class="search-segment">
                        <i class="bi bi-calendar-check segment-icon"></i>
                        <div class="segment-body">
                            <div class="segment-label">Pick up date</div>
                            <input type="text" class="segment-input" placeholder="Add date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>

                    <div class="divider-vert" aria-hidden></div>

                    <div class="search-segment">
                        <i class="bi bi-calendar-x segment-icon"></i>
                        <div class="segment-body">
                            <div class="segment-label">Return date</div>
                            <input type="text" class="segment-input" placeholder="Add date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>

                    <button class="btn btn-primary search-action" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>

                </div>

            </div>

            <div class="col-lg-6 text-center">

                <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7"
                     alt="Hero Car"
                     class="hero-image">

            </div>

        </div>

    </div>

</div>

<!-- FILTER BAR -->
<div class="container my-2">

    <div class="filter-bar bg-white shadow-sm p-3 rounded-4">

        <div class="row g-3 align-items-center">

            <!-- CAR TYPE -->
            <div class="col-md-3">

                <select class="form-select" id="filterType">

                    <option value="all">All Cars</option>
                    <option value="suv">SUV</option>
                    <option value="sedan">Sedan</option>
                    <option value="luxury">Luxury</option>

                </select>

            </div>

            <!-- PRICE -->
            <div class="col-md-3">

                <select class="form-select" id="filterPrice">

                    <option value="all">Any Price</option>
                    <option value="low">Below 5000</option>
                    <option value="mid">5000 - 10000</option>
                    <option value="high">10000+</option>

                </select>

            </div>

            <!-- TRANSMISSION -->
            <div class="col-md-3">

                <select class="form-select" id="filterTransmission">

                    <option value="all">Any Transmission</option>
                    <option value="automatic">Automatic</option>
                    <option value="manual">Manual</option>

                </select>

            </div>

            <!-- RESET -->
            <div class="col-md-3">

                <button class="btn btn-outline-primary w-100"
                        id="resetFilters">

                    Reset Filters

                </button>

            </div>

        </div>

    </div>

</div>

<!-- CATEGORY FILTER BAR -->
<section class="category-bar py-4">

    <div class="container">

        <div class="category-scroll d-flex gap-3 overflow-auto">

            <button class="category-btn active">
                🚗 All
            </button>

            <button class="category-btn">
                🚙 SUV
            </button>

            <button class="category-btn">
                💎 Luxury
            </button>

            <button class="category-btn">
                💰 Economy
            </button>

            <button class="category-btn">
                🚐 Vans
            </button>

            <button class="category-btn">
                ⚡ Electric
            </button>

            <button class="category-btn">
                🏎 Sports
            </button>

        </div>

    </div>

</section>

<!-- FEATURED VEHICLES -->
<section class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Featured Vehicles
            </h2>

            <p class="text-muted mb-0">
                Explore vehicles listed by hosts.
            </p>

        </div>

    </div>

    <div class="row g-4">

        <?php

        $query = "SELECT * FROM vehicles ORDER BY id DESC";

        $result = mysqli_query($conn, $query);

        if(mysqli_num_rows($result) > 0){

            while($vehicle = mysqli_fetch_assoc($result)){

        ?>

        <div class="col-lg-4 col-md-6">

            <div class="card vehicle-card border-0 shadow-sm h-100">

                <!-- IMAGE -->
                <img src="uploads/<?php echo $vehicle['image']; ?>"
                     class="card-img-top vehicle-image"
                     alt="Vehicle Image">

                <div class="card-body">

                    <!-- VEHICLE NAME -->
                    <h5 class="fw-bold">

                        <?php echo $vehicle['vehicle_name']; ?>

                    </h5>

                    <!-- TYPE -->
                    <p class="text-muted mb-2">

                        <?php echo $vehicle['vehicle_type']; ?>

                    </p>

                    <!-- TRANSMISSION -->
                    <p class="mb-3">

                        <span class="badge bg-light text-dark">

                            <?php echo $vehicle['transmission']; ?>

                        </span>

                    </p>

                    <!-- PRICE -->
                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="text-primary fw-bold mb-0">

                            KES <?php echo number_format($vehicle['price_per_day']); ?>

                            <small class="text-muted fs-6">
                                /day
                            </small>

                        </h5>

                        <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>"
                           class="btn btn-primary">

                            View

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <?php

            }

        }else{

        ?>

        <div class="col-12">

            <div class="alert alert-info">

                No vehicles available yet.

            </div>

        </div>

        <?php } ?>

    </div>

</section>

<?php include 'includes/footer.php'; ?>