<?php

include 'includes/auth.php';
include 'config/database.php';
include 'includes/header.php';

// GET VEHICLE ID
if(!isset($_GET['id'])){

    echo "<div class='container py-5'><div class='alert alert-danger'>Vehicle not found.</div></div>";
    include 'includes/footer.php';
    exit;

}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// FETCH VEHICLE
$query = "SELECT * FROM vehicles WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0){

    echo "<div class='container py-5'><div class='alert alert-danger'>Vehicle does not exist.</div></div>";
    include 'includes/footer.php';
    exit;

}

$vehicle = mysqli_fetch_assoc($result);

?>

<div class="container py-5">

    <div class="row g-5">

        <!-- IMAGE -->
        <div class="col-lg-6">

            <img src="uploads/<?php echo $vehicle['image']; ?>"
                 class="img-fluid rounded-4 shadow-sm"
                 alt="Vehicle Image">

            <!-- DESCRIPTION SECTION -->
            <div class="mt-4">
                <h4 class="fw-bold">Description</h4>
                <p class="text-muted">
                    <?php echo $vehicle['description']; ?>
                </p>
            </div>

            <!-- FEATURES GRID -->
            <div class="mt-4">
                <h4 class="fw-bold mb-3">Vehicle Features</h4>
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="feature-box">
                            <i class="bi bi-snow"></i> A/C
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="feature-box">
                            <i class="bi bi-bluetooth"></i> Bluetooth
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="feature-box">
                            <i class="bi bi-camera-video"></i> Camera
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- DETAILS -->
        <div class="col-lg-6">

            <h2 class="fw-bold mb-2">

                <?php echo $vehicle['vehicle_name']; ?>

            </h2>

            <p class="text-muted mb-3">
                <?php echo $vehicle['vehicle_type']; ?> •
                <?php echo $vehicle['transmission']; ?>
            </p>

            <h3 class="text-primary fw-bold mb-4">

                KES <?php echo number_format($vehicle['price_per_day']); ?>
                <small class="text-muted fs-6">/day</small>

            </h3>

            <!-- BOOKING CALCULATOR -->
            <div class="card booking-card shadow-sm border-0 mb-4 bg-light">
                <h5 class="fw-bold mb-3">Booking Summary</h5>
                
                <!-- Hidden rate for JS calculator -->
                <input type="hidden" id="daily_rate_input" value="<?php echo $vehicle['price_per_day']; ?>">

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Pickup</label>
                        <input type="text" id="start_date" name="start_date" class="form-control" placeholder="Select Date">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Return</label>
                        <input type="text" id="end_date" name="end_date" class="form-control" placeholder="Select Date">
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span>Days</span>
                    <span id="totalDays" class="fw-bold">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span class="fw-bold">KES <span id="subtotalPrice">0</span></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Total (incl. KES 2,000 fee)</span>
                    <span class="fw-bold text-primary">KES <span id="totalPrice">0</span></span>
                </div>
            </div>

            <!-- ACTION BUTTONS (Moved inside booking-card) -->
            <div class="d-grid gap-2 mt-4">
                <a href="reserve.php?id=<?php echo $vehicle['id']; ?>" 
                   id="reserve_button"
                   class="btn btn-primary btn-lg">

                    Reserve Now

                </a>
                <button class="btn btn-outline-dark">Add to Wishlist</button>
            </div>

        </div>

    </div>

</div>

<script>
// Initialize Flatpickr with dynamic booked dates
document.addEventListener('DOMContentLoaded', function() {
    fetch('get-booked-dates.php?vehicle_id=<?php echo $vehicle['id']; ?>')
    .then(response => response.json())
    .then(bookedDates => {

        const reserveBtn = document.getElementById('reserve_button');
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');

        const config = {
            minDate: "today",
            disable: bookedDates,
            dateFormat: "Y-m-d",
            onChange: function() {
                // Ensure calculator updates when date is picked
                if (typeof calculateBooking === 'function') {
                    calculateBooking();
                }
                // Update the reservation link with selected dates
                if(startInput.value && endInput.value) {
                    reserveBtn.href = `reserve.php?id=<?php echo $vehicle['id']; ?>&start_date=${startInput.value}&end_date=${endInput.value}`;
                }
            }
        };

        flatpickr("#start_date", config);
        flatpickr("#end_date", config);

    })
    .catch(err => console.error('Error fetching booked dates:', err));
});
</script>

<?php include 'includes/footer.php'; ?>