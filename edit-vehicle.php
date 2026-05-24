<?php
include 'includes/auth.php';
include 'includes/role_guard.php';
include 'config/database.php';

requireRole('host');

$host_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

if (!isset($_GET['id'])) {
    header("Location: host-dashboard.php");
    exit();
}

$vehicle_id = (int)$_GET['id'];

// Fetch existing vehicle details and verify ownership
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ? AND host_id = ?");
$stmt->bind_param("ii", $vehicle_id, $host_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    die("Vehicle not found or access denied.");
}

// Handle Update Form Submission
if (isset($_POST['update_vehicle'])) {
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $price_per_day = mysqli_real_escape_string($conn, $_POST['price_per_day']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $image_name = $vehicle['image']; // Default to old image

    // Check if a new image was uploaded
    if (!empty($_FILES['image']['name'])) {
        $new_image_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = "uploads/" . $new_image_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_name = $new_image_name;
            // Optional: delete old image file here if desired
        } else {
            $error_message = "Failed to upload new image.";
        }
    }

    if (empty($error_message)) {
        $update_stmt = $conn->prepare("UPDATE vehicles SET vehicle_name=?, price_per_day=?, vehicle_type=?, transmission=?, description=?, image=? WHERE id=? AND host_id=?");
        $update_stmt->bind_param("sdssssii", $vehicle_name, $price_per_day, $vehicle_type, $transmission, $description, $image_name, $vehicle_id, $host_id);
        
        if ($update_stmt->execute()) {
            header("Location: host-dashboard.php?msg=updated");
            exit();
        } else {
            $error_message = "Database error: " . $conn->error;
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">Edit Vehicle</h2>
                    <a href="host-dashboard.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- VEHICLE NAME -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="form-control" value="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" required>
                    </div>

                    <!-- PRICE -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price Per Day (KES)</label>
                        <input type="number" name="price_per_day" class="form-control" value="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>" required>
                    </div>

                    <div class="row">
                        <!-- TYPE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Vehicle Type</label>
                            <select name="vehicle_type" class="form-select">
                                <option <?php if($vehicle['vehicle_type'] == 'SUV') echo 'selected'; ?>>SUV</option>
                                <option <?php if($vehicle['vehicle_type'] == 'Sedan') echo 'selected'; ?>>Sedan</option>
                                <option <?php if($vehicle['vehicle_type'] == 'Luxury') echo 'selected'; ?>>Luxury</option>
                                <option <?php if($vehicle['vehicle_type'] == 'Truck') echo 'selected'; ?>>Truck</option>
                                <option <?php if($vehicle['vehicle_type'] == 'Economy') echo 'selected'; ?>>Economy</option>
                                <option <?php if($vehicle['vehicle_type'] == 'Vans') echo 'selected'; ?>>Vans</option>
                            </select>
                        </div>

                        <!-- TRANSMISSION -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Transmission</label>
                            <select name="transmission" class="form-select">
                                <option <?php if($vehicle['transmission'] == 'Automatic') echo 'selected'; ?>>Automatic</option>
                                <option <?php if($vehicle['transmission'] == 'Manual') echo 'selected'; ?>>Manual</option>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="4" class="form-control"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                    </div>

                    <!-- IMAGE -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Vehicle Image</label>
                        <div class="mb-2">
                            <small class="text-muted">Current image:</small><br>
                            <img src="uploads/<?php echo $vehicle['image']; ?>" alt="Current" style="height: 100px; border-radius: 8px;" class="mt-1">
                        </div>
                        <input type="file" name="image" class="form-control">
                        <div class="form-text">Leave blank to keep the current image.</div>
                    </div>

                    <!-- BUTTON -->
                    <div class="d-grid">
                        <button type="submit" name="update_vehicle" class="btn btn-primary py-2 fw-semibold">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>