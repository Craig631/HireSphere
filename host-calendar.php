<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'host') {
    die("Access denied");
}

$host_id = $_SESSION['user_id'];

$data = mysqli_query($conn,
"SELECT b.vehicle_id, b.start_date, b.end_date, v.vehicle_name
 FROM bookings b
 JOIN vehicles v ON b.vehicle_id = v.id
 WHERE v.host_id = $host_id"
);

include 'includes/header.php';
?>

<div class="container py-4">
    <h3 class="fw-bold mb-4">Vehicle Bookings Calendar</h3>
    <div class="row">
        <?php while($row = mysqli_fetch_assoc($data)) { ?>
            <div class="col-md-4 mb-3">
                <div class="card card-modern p-3 shadow-sm border-0">
                    <h5 class="fw-bold"><?php echo $row['vehicle_name']; ?></h5>
                    <p class="text-muted mb-0 small">From: <b><?php echo $row['start_date']; ?></b> To: <b><?php echo $row['end_date']; ?></b></p>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>