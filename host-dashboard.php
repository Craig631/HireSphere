<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'host') {
    die("Access denied");
}

$host_id = $_SESSION['user_id'];

/* REAL VEHICLES */
$vehicles = mysqli_query($conn,
    "SELECT * FROM vehicles WHERE host_id = $host_id"
);

/* TOTAL VEHICLES */
$totalVehiclesQuery = mysqli_query($conn,
    "SELECT COUNT(*) as total FROM vehicles WHERE host_id = $host_id"
);
$totalVehicles = mysqli_fetch_assoc($totalVehiclesQuery)['total'];

/* BOOKINGS */
$totalBookingsQuery = mysqli_query($conn,
    "SELECT COUNT(*) as total 
     FROM bookings b
     JOIN vehicles v ON b.vehicle_id = v.id
     WHERE v.host_id = $host_id"
);
$totalBookings = mysqli_fetch_assoc($totalBookingsQuery)['total'];

/* EARNINGS */
$totalEarningsQuery = mysqli_query($conn,
    "SELECT SUM(amount) as total
     FROM payments p
     JOIN vehicles v ON p.vehicle_id = v.id
     WHERE v.host_id = $host_id AND p.status = 'success'"
);
$totalEarnings = mysqli_fetch_assoc($totalEarningsQuery)['total'] ?? 0;
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">

    <div class="row">

        <!-- SIDEBAR (UNCHANGED) -->
        <div class="col-lg-2 dashboard-sidebar p-0">

            <div class="p-4">

                <h4 class="fw-bold text-white mb-4">
                    Host Panel
                </h4>

                <ul class="nav flex-column dashboard-nav">

                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link active">
                            <i class="bi bi-grid"></i> Dashboard
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="add-vehicle.php" class="nav-link">
                            <i class="bi bi-car-front"></i> Add Vehicle
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="host-bookings.php" class="nav-link">
                            <i class="bi bi-calendar-check"></i> Bookings
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link">
                            <i class="bi bi-cash-stack"></i> Earnings
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="subscriptions.php" class="nav-link">
                            <i class="bi bi-box"></i> Subscription
                        </a>
                    </li>

                </ul>

            </div>

        </div>

        <!-- MAIN CONTENT -->
        <div class="col-lg-10 p-4 dashboard-content">

            <div class="mb-4">

                <h2 class="fw-bold">Dashboard Overview</h2>

                <p class="text-muted">
                    Welcome back, <?php echo $_SESSION['name'] ?? 'Host'; ?>.
                </p>

            </div>

            <!-- OVERVIEW CARDS -->
            <div class="row g-4 mb-5">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="bi bi-car-front"></i>
                        </div>

                        <h3 class="fw-bold mt-3">
                            <?php echo $totalVehicles; ?>
                        </h3>

                        <p class="text-muted mb-0">Active Vehicles</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>

                        <h3 class="fw-bold mt-3">
                            <?php echo $totalBookings; ?>
                        </h3>

                        <p class="text-muted mb-0">Total Bookings</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <h3 class="fw-bold mt-3">
                            KES <?php echo number_format($totalEarnings); ?>
                        </h3>

                        <p class="text-muted mb-0">Earnings</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="bi bi-award"></i>
                        </div>

                        <h3 class="fw-bold mt-3">
                            Premium
                        </h3>

                        <p class="text-muted mb-0">Current Plan</p>
                    </div>
                </div>

            </div>

            <!-- ACTIVE LISTINGS -->
            <div class="dashboard-section mb-5">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h4 class="fw-bold mb-0">Active Listings</h4>

                    <a href="add-vehicle.php" class="btn btn-primary">
                        Add Vehicle
                    </a>

                </div>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Price/Day</th>
                                <th>Status</th>
                                <th>Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while($v = mysqli_fetch_assoc($vehicles)) { ?>

                            <?php
                            $vid = $v['id'];

                            $bookingCount = mysqli_query($conn,
                                "SELECT COUNT(*) as total FROM bookings WHERE vehicle_id = $vid"
                            );
                            $bookingCount = mysqli_fetch_assoc($bookingCount)['total'];
                            ?>

                            <tr>

                                <td><?php echo $v['vehicle_name']; ?></td>
                                <td>KES <?php echo $v['price_per_day'] ?? 'N/A'; ?></td>

                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>

                                <td><?php echo $bookingCount; ?></td>

                                <td>

                                    <a href="edit-vehicle.php?id=<?php echo $vid; ?>" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>

                                    <button class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>

                                </td>

                            </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- SUBSCRIPTION (UNCHANGED UI) -->
            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="dashboard-card h-100">

                        <h5 class="fw-bold mb-3">Subscription Plan</h5>

                        <p class="text-muted">
                            You are currently on the <strong>Premium Plan</strong>.
                        </p>

                        <ul class="list-unstyled">
                            <li class="mb-2">✅ Unlimited Listings</li>
                            <li class="mb-2">✅ Featured Vehicles</li>
                            <li class="mb-2">✅ Priority Support</li>
                        </ul>

                        <button class="btn btn-outline-primary">
                            Manage Subscription
                        </button>

                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dashboard-card h-100">

                        <h5 class="fw-bold mb-3">Insurance Status</h5>

                        <p class="text-muted">
                            Your fleet is protected under DriveHub Vehicle Insurance.
                        </p>

                        <div class="alert alert-success">
                            Insurance Active until Dec 2026
                        </div>

                        <button class="btn btn-outline-success">
                            View Insurance
                        </button>

                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>