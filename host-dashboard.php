<?php
/* Start the session before sending any HTML to the browser. */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Only authenticated hosts may access this dashboard. */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'host') {
    http_response_code(403);
    exit('Access denied. A host account is required.');
}

require 'config/database.php';

/* Cast the session ID to an integer before using it in SQL queries. */
$hostId = (int) $_SESSION['user_id'];

/* Escape database and session values whenever they are displayed in HTML. */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

/*
 * Fetch this host's fleet with booking counts in one query.
 * This replaces the previous extra query for every vehicle row.
 */
$vehicleResult = mysqli_query($conn, "
    SELECT v.*,
           COALESCE(b.booking_count, 0) AS booking_count
    FROM vehicles v
    LEFT JOIN (
        SELECT vehicle_id, COUNT(*) AS booking_count
        FROM bookings
        GROUP BY vehicle_id
    ) b ON b.vehicle_id = v.id
    WHERE v.host_id = $hostId
    ORDER BY v.id DESC
");

$fleet = mysqli_fetch_all($vehicleResult, MYSQLI_ASSOC);
$totalVehicles = count($fleet);

/* Group booking statuses for the analytics panel and total booking count. */
$statusResult = mysqli_query($conn, "
    SELECT b.status, COUNT(*) AS total
    FROM bookings b
    JOIN vehicles v ON v.id = b.vehicle_id
    WHERE v.host_id = $hostId
    GROUP BY b.status
");

$statusCounts = [];
$totalBookings = 0;

while ($row = mysqli_fetch_assoc($statusResult)) {
    /* Normalize status labels so capitalization does not split categories. */
    $status = strtolower(trim((string) $row['status']));
    $status = $status !== '' ? $status : 'unknown';

    $statusCounts[$status] = ($statusCounts[$status] ?? 0)
        + (int) $row['total'];

    $totalBookings += (int) $row['total'];
}

$pendingBookings = $statusCounts['pending'] ?? 0;

/*
 * Preserve the existing earnings definition: successful payment records.
 * M-Pesa receipts stored only in mpesa_payments are not included here.
 */
$earningsResult = mysqli_query($conn, "
    SELECT COALESCE(SUM(p.amount), 0) AS total
    FROM payments p
    JOIN vehicles v ON v.id = p.vehicle_id
    WHERE v.host_id = $hostId
      AND p.status = 'success'
");

$totalEarnings = (float) mysqli_fetch_assoc($earningsResult)['total'];

/* Measure how many listed vehicles have received at least one booking. */
$bookedVehicles = 0;

foreach ($fleet as $vehicle) {
    if ((int) $vehicle['booking_count'] > 0) {
        $bookedVehicles++;
    }
}

$fleetReach = $totalVehicles > 0
    ? (int) round(($bookedVehicles / $totalVehicles) * 100)
    : 0;

/* Load recent bookings belonging only to this host's vehicles. */
$recentResult = mysqli_query($conn, "
    SELECT b.id, b.start_date, b.end_date, b.status,
           v.id AS vehicle_id, v.vehicle_name,
           COALESCE(NULLIF(b.customer_name, ''), u.name) AS renter_name
    FROM bookings b
    JOIN vehicles v ON v.id = b.vehicle_id
    LEFT JOIN users u ON u.id = b.user_id
    WHERE v.host_id = $hostId
    ORDER BY b.id DESC
    LIMIT 5
");

/* Load the shared navbar, including your workspace switch and theme controls. */
include 'includes/header.php';
?>

<!-- Dashboard shell: sidebar and main workspace mirror the reference layout. -->
<div class="hs-host-shell">

    <!-- SIDEBAR: links lead to existing pages or real sections below. -->
    <aside class="hs-host-sidebar">
        <div class="hs-host-brand">
            <span class="hs-host-brand-icon">
                <i class="bi bi-car-front-fill" aria-hidden="true"></i>
            </span>
            <div>
                <strong>Host workspace</strong>
                <small>HireSphere</small>
            </div>
        </div>

        <p class="hs-host-nav-label">MANAGEMENT</p>

        <nav class="hs-host-nav" aria-label="Host navigation">
            <a href="host-dashboard.php" class="active" aria-current="page">
                <i class="bi bi-grid" aria-hidden="true"></i>
                Overview
            </a>

            <a href="#host-fleet">
                <i class="bi bi-car-front" aria-hidden="true"></i>
                My vehicles
            </a>

            <a href="add-vehicle.php">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                Add vehicle
            </a>

            <a href="#host-recent-bookings">
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                Recent bookings
            </a>

            <a href="#host-earnings">
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                Earnings
            </a>

            <a href="#subscription-section">
                <i class="bi bi-box" aria-hidden="true"></i>
                Subscription
            </a>
        </nav>

        <!-- Reuse the account settings modal already provided by the header. -->
        <p class="hs-host-nav-label mt-4">ACCOUNT</p>

        <div class="hs-host-nav">
            <button type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#hsSettingsModal">
                <i class="bi bi-gear" aria-hidden="true"></i>
                Settings
            </button>

            <a href="index.php">
                <i class="bi bi-house-door" aria-hidden="true"></i>
                Guest homepage
            </a>
        </div>

        <a href="logout.php" class="hs-host-logout">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            Logout
        </a>
    </aside>

    <main class="hs-host-main">

        <!-- Page title and a useful action replace decorative dashboard controls. -->
        <header class="hs-host-heading">
            <div>
                <p class="hs-host-breadcrumb">Dashboard / Overview</p>
                <h1>Welcome back, <?= $escape($_SESSION['name'] ?? 'Host') ?></h1>
                <p class="hs-host-muted mb-0">
                    Here is an overview of your rental activity.
                </p>
            </div>

            <a href="add-vehicle.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>
                Add vehicle
            </a>
        </header>

        <!-- OVERVIEW BANNER: all figures represent current all-time records. -->
        <section class="hs-host-overview" aria-labelledby="hostOverviewTitle">
            <div class="hs-host-section-heading">
                <h2 id="hostOverviewTitle">Business overview</h2>
                <span class="hs-host-period">All time</span>
            </div>

            <div class="hs-host-stats">
                <div class="hs-host-stat">
                    <span><i class="bi bi-car-front me-2" aria-hidden="true"></i>Listed vehicles</span>
                    <strong><?= number_format($totalVehicles) ?></strong>
                    <small>Vehicles in your fleet</small>
                </div>

                <div class="hs-host-stat">
                    <span><i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Total bookings</span>
                    <strong><?= number_format($totalBookings) ?></strong>
                    <small><?= number_format($pendingBookings) ?> pending</small>
                </div>

                <div class="hs-host-stat" id="host-earnings">
                    <span><i class="bi bi-wallet2 me-2" aria-hidden="true"></i>Recorded earnings</span>
                    <strong>KES <?= number_format($totalEarnings, 2) ?></strong>
                    <small>Successful payment records</small>
                </div>
            </div>
        </section>

        <!-- ANALYTICS: status bars and a fleet booking indicator use real totals. -->
        <div class="hs-host-analytics">

            <section class="hs-host-panel" aria-labelledby="hostStatusTitle">
                <div class="hs-host-section-heading">
                    <h2 id="hostStatusTitle">Booking breakdown</h2>
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                </div>

                <?php if ($totalBookings === 0): ?>
                    <p class="hs-host-muted mb-0">
                        Your booking breakdown will appear after your first booking.
                    </p>
                <?php else: ?>
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <?php
                        /* Each bar represents this status's share of all bookings. */
                        $percentage = (int) round(($count / $totalBookings) * 100);
                        ?>
                        <div class="hs-host-status-row">
                            <div>
                                <span><?= $escape(ucfirst($status)) ?></span>
                                <strong><?= $count ?> · <?= $percentage ?>%</strong>
                            </div>

                            <div class="progress"
                                 role="progressbar"
                                 aria-label="<?= $escape(ucfirst($status) . ' bookings') ?>"
                                 aria-valuenow="<?= $percentage ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                <div class="progress-bar"
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="hs-host-panel" aria-labelledby="hostFleetTitle">
                <div class="hs-host-section-heading">
                    <h2 id="hostFleetTitle">Fleet booking reach</h2>
                    <i class="bi bi-pie-chart" aria-hidden="true"></i>
                </div>

                <div class="hs-host-reach">
                    <!-- The ring is decorative; its percentage is also shown as text. -->
                    <div class="hs-host-ring"
                         style="--reach: <?= $fleetReach ?>%;"
                         aria-hidden="true">
                        <div>
                            <strong><?= $fleetReach ?>%</strong>
                            <span>of fleet</span>
                        </div>
                    </div>

                    <div>
                        <h3><?= $bookedVehicles ?> of <?= $totalVehicles ?> vehicles</h3>
                        <p class="hs-host-muted mb-0">
                            Have received at least one booking.
                            This measures booking reach, not current availability.
                        </p>
                    </div>
                </div>
            </section>

        </div>

        <!-- FLEET TABLE: preserve viewing, editing, and vehicle-specific bookings. -->
        <section class="hs-host-panel mb-4" id="host-fleet">
            <div class="hs-host-section-heading">
                <div>
                    <h2>My vehicles</h2>
                    <p class="hs-host-muted small mb-0">Manage your published listings.</p>
                </div>
                <span class="hs-host-pill"><?= $totalVehicles ?> listed</span>
            </div>

            <div class="table-responsive">
                <table class="table hs-host-table align-middle">
                    <caption class="visually-hidden">Vehicles owned by your host account</caption>
                    <thead>
                        <tr>
                            <th scope="col">Vehicle</th>
                            <th scope="col">Daily rate</th>
                            <th scope="col">Transmission</th>
                            <th scope="col">Bookings</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$fleet): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    No vehicles listed yet.
                                    <a href="add-vehicle.php">Add your first vehicle</a>.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($fleet as $vehicle): ?>
                            <tr>
                                <td>
                                    <div class="hs-host-vehicle">
                                        <img src="uploads/<?= $escape($vehicle['image']) ?>"
                                             alt=""
                                             loading="lazy">
                                        <div>
                                            <strong><?= $escape($vehicle['vehicle_name']) ?></strong>
                                            <small><?= $escape($vehicle['vehicle_type']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>KES <?= number_format((float) $vehicle['price_per_day'], 2) ?></td>
                                <td><?= $escape($vehicle['transmission']) ?></td>
                                <td><?= (int) $vehicle['booking_count'] ?></td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="edit-vehicle.php?id=<?= (int) $vehicle['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                        <a href="host-bookings.php?vehicle_id=<?= (int) $vehicle['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            Bookings
                                        </a>
                                        <a href="vehicle-details.php?id=<?= (int) $vehicle['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- The original Delete button had no handler, so no fake action is shown. -->
        </section>

        <!-- RECENT BOOKINGS: a second table provides quick access to rental activity. -->
        <section class="hs-host-panel mb-4" id="host-recent-bookings">
            <div class="hs-host-section-heading">
                <h2>Recent bookings</h2>
                <span class="hs-host-muted small">Latest 5</span>
            </div>

            <div class="table-responsive">
                <table class="table hs-host-table align-middle">
                    <caption class="visually-hidden">Your five most recent bookings</caption>
                    <thead>
                        <tr>
                            <th scope="col">Vehicle</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Rental dates</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recentResult) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    No bookings yet.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($booking = mysqli_fetch_assoc($recentResult)): ?>
                            <tr>
                                <td><?= $escape($booking['vehicle_name']) ?></td>
                                <td><?= $escape($booking['renter_name'] ?? 'Customer') ?></td>
                                <td>
                                    <?= $escape($booking['start_date'] ?? 'Not set') ?>
                                    →
                                    <?= $escape($booking['end_date'] ?? 'Not set') ?>
                                </td>
                                <td>
                                    <span class="hs-host-pill">
                                        <?= $escape(ucfirst($booking['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="host-bookings.php?vehicle_id=<?= (int) $booking['vehicle_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Keep subscription and insurance sections without claiming unverified coverage. -->
        <div class="hs-host-analytics" id="subscription-section">
            <section class="hs-host-panel">
                <div class="hs-host-section-heading">
                    <h2>Subscription</h2>
                    <i class="bi bi-box" aria-hidden="true"></i>
                </div>
                <p class="hs-host-muted">
                    Subscription billing is not connected yet.
                    Your current account plan cannot be verified here.
                </p>
                <span class="hs-host-pill">Not connected</span>
            </section>

            <section class="hs-host-panel">
                <div class="hs-host-section-heading">
                    <h2>Vehicle insurance</h2>
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                </div>
                <p class="hs-host-muted">
                    Insurance records are not connected yet.
                    Confirm each vehicle's cover with your insurer.
                </p>
                <span class="hs-host-pill">Not connected</span>
            </section>
        </div>

    </main>
</div>

<?php
/* Reuse existing Bootstrap scripts and the saved light/dark appearance controls. */
include 'includes/footer.php';
?>