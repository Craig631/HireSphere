<?php
include 'includes/auth.php';
include 'includes/role_guard.php';

requireRole('host');

// ENABLE ERROR REPORTING FOR DEBUGGING
ini_set('display_errors', 1); // This should ideally be off in production
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config/database.php';

$error_message = "";

// FORM SUBMISSION
if (isset($_POST['publish_vehicle'])) {

    // Ensure directory exists
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Sanitize inputs
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name'] ?? '');
    $price_per_day = mysqli_real_escape_string($conn, $_POST['price_per_day'] ?? '');
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // IMAGE HANDLING
    $image_name = $_FILES['image']['name'];
    $temp_name = $_FILES['image']['tmp_name'];
    $upload_path = "uploads/" . $image_name;

    // Check if file was actually uploaded
    if (!empty($image_name) && move_uploaded_file($temp_name, $upload_path)) {
        
        try {
            $host_id = $_SESSION['user_id'];

            // INSERT QUERY
            $query = "INSERT INTO vehicles(
                vehicle_name,
                price_per_day,
                vehicle_type,
                transmission,
                description,
                image,
                host_id
            ) VALUES(
                '$vehicle_name',
                '$price_per_day',
                '$vehicle_type',
                '$transmission',
                '$description',
                '$image_name',
                '$host_id'
            )";

            if (mysqli_query($conn, $query)) {
                // REDIRECT TO DASHBOARD ON SUCCESS
                header("Location: host-dashboard.php");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            // This catches errors like "Table 'hiresphere.vehicles' doesn't exist"
            $error_message = "Database Error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Failed to upload image. Please check folder permissions.";
    }
}

include 'includes/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow-sm border-0 p-4">

                <h2 class="fw-bold mb-2">
                    List Your Vehicle
                </h2>

                <p class="text-muted mb-4">
                    Add your vehicle to HireSphere and start earning.
                </p>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- VEHICLE NAME -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="form-control" placeholder="e.g. Mercedes Benz E-Class" required>
                    </div>

                    <!-- PRICE -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price Per Day (KES)</label>
                        <input type="number" name="price_per_day" class="form-control" placeholder="e.g. 14000" required>
                    </div>

                    <div class="row">
                        <!-- TYPE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Vehicle Type</label>
                            <select name="vehicle_type" class="form-select">
                                <option>SUV</option>
                                <option>Sedan</option>
                                <option>Luxury</option>
                                <option>Truck</option>
                            </select>
                        </div>

                        <!-- TRANSMISSION -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Transmission</label>
                            <select name="transmission" class="form-select">
                                <option>Automatic</option>
                                <option>Manual</option>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Describe the condition and features of the car..."></textarea>
                    </div>

                    <!-- IMAGE -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload Vehicle Image</label>
                        <input type="file" name="image" class="form-control" required>
                    </div>

                    <!-- BUTTON -->
                    <button type="submit" name="publish_vehicle" class="btn btn-primary w-100 py-2 fw-semibold">
                        Publish Vehicle
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>