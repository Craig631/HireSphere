<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'host') {
    die("Access denied");
}

if (!isset($_GET['vehicle_id'])) {
    die("No vehicle selected.");
}

$vehicle_id = (int)$_GET['vehicle_id'];

if (isset($_GET['action']) && isset($_GET['booking_id'])) {

    $booking_id = $_GET['booking_id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        $status = "approved";
    } elseif ($action == "reject") {
        $status = "rejected";
    } else {
        die("Invalid action");
    }

    mysqli_query($conn,
        "UPDATE bookings SET status = '$status' WHERE id = $booking_id"
    );

    header("Location: host-bookings.php?vehicle_id=".$_GET['vehicle_id']);
    exit;
}

$bookings = mysqli_query($conn,
    "SELECT b.*, u.name, u.email
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     WHERE b.vehicle_id = $vehicle_id
     ORDER BY b.id DESC"
);

include 'includes/header.php';
?>

<div class="container py-4">

    <h3 class="fw-bold mb-4">Vehicle Bookings</h3>

    <div class="dashboard-section shadow-sm">
        <table class="table table-striped align-middle">

            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while($b = mysqli_fetch_assoc($bookings)) { ?>
                    <tr>
                        <td><?php echo $b['name']; ?></td>
                        <td><?php echo $b['email']; ?></td>
                        <td>
                            <span class="badge 
                                <?php 
                                    if($b['status'] == 'approved') echo 'bg-success';
                                    elseif($b['status'] == 'rejected') echo 'bg-danger';
                                    else echo 'bg-warning';
                                ?>
                            ">
                                <?php echo $b['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($b['status'] == 'pending') { ?>

                                <a href="host-bookings.php?vehicle_id=<?php echo $vehicle_id; ?>&booking_id=<?php echo $b['id']; ?>&action=approve"
                                   class="btn btn-success btn-sm">
                                    Approve
                                </a>

                                <a href="host-bookings.php?vehicle_id=<?php echo $vehicle_id; ?>&booking_id=<?php echo $b['id']; ?>&action=reject"
                                   class="btn btn-danger btn-sm">
                                    Reject
                                </a>

                            <?php } else { ?>
                                <span class="text-muted">No actions</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php if(mysqli_num_rows($bookings) == 0): ?>
            <div class="text-center py-4 text-muted">No bookings found for this vehicle.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>