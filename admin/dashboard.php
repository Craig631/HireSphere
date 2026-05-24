<?php
session_start();
// Only allow access if the admin session is active.
// Standard users (Hosts/Customers) do not have this session key.
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php"); // Redirects to admin/login.php
    exit;
}
include '../config/database.php';

$revenue_result = $conn->query("SELECT SUM(paid_amount) AS total FROM bookings WHERE payment_status='paid'");
$revenue_row = $revenue_result->fetch_assoc();
$total_revenue = $revenue_row['total'] ?? 0;

$bookings_result = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$bookings_row = $bookings_result->fetch_assoc();
$total_bookings = $bookings_row['total'] ?? 0;

$paid_result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE payment_status='paid'");
$paid_row = $paid_result->fetch_assoc();
$paid_bookings = $paid_row['total'] ?? 0;

$payments_result = $conn->query("SELECT COUNT(*) AS total FROM mpesa_payments");
$payments_row = $payments_result->fetch_assoc();
$total_payments = $payments_row['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireSphere Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="/hiresphere/" style="text-decoration: none;">
            <div class="logo-mark">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#0d0f12" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div>
                    <div class="logo-text">HireSphere</div>
                    <div class="logo-badge">Admin Panel</div>
                </div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="dashboard.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        
        <div class="nav-section-label">Management</div>
        <a href="#bookings" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Bookings
        </a>
        <a href="#payments" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            Payments
        </a>
        <a href="../index.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            View Site
        </a>
        
        <div class="nav-section-label">Account</div>
        <a href="logout.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-chip">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?></div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></div>
                <div class="admin-role"><?php echo htmlspecialchars(ucfirst($_SESSION['admin_role'] ?? 'Admin')); ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Bookings, payments & revenue at a glance</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-ghost" onclick="window.location.reload()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Refresh
            </button>
            <a href="logout.php" class="btn btn-ghost" style="color: var(--red);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value mono">KES <?php echo number_format($total_revenue, 0); ?></div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="stat-label">Total Bookings</div>
            <div class="stat-value"><?php echo number_format($total_bookings); ?></div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-label">Paid Bookings</div>
            <div class="stat-value"><?php echo number_format($paid_bookings); ?></div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </div>
            <div class="stat-label">M-PESA Transactions</div>
            <div class="stat-value"><?php echo number_format($total_payments); ?></div>
        </div>
    </div>

    <!-- BOOKINGS TABLE -->
    <div class="section-card" id="bookings">
        <div class="section-header">
            <div class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                Bookings
            </div>
            <span class="count-badge"><?php echo number_format($total_bookings); ?> total</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehicle</th>
                        <th>Phone</th>
                        <th>Booking Status</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = $conn->query("SELECT * FROM bookings ORDER BY id DESC");
                if ($res && $res->num_rows > 0):
                    while ($row = $res->fetch_assoc()):
                        $payment_class = match(strtolower($row['payment_status'] ?? '')) {
                            'paid'    => 'badge-paid',
                            'pending' => 'badge-pending',
                            'failed', 'cancelled' => 'badge-failed',
                            'unpaid'  => 'badge-unpaid',
                            default   => 'badge-default',
                        };
                        $status_class = match(strtolower($row['status'] ?? '')) {
                            'confirmed', 'active', 'completed' => 'badge-confirmed',
                            'pending', 'processing'            => 'badge-pending',
                            'cancelled', 'failed'              => 'badge-failed',
                            default                            => 'badge-default',
                        };
                ?>
                    <tr>
                        <td class="cell-id">#<?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['vehicle_id']); ?></td>
                        <td class="cell-mono"><?php echo htmlspecialchars($row['customer_phone']); ?></td>
                        <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                        <td><span class="badge <?php echo $payment_class; ?>"><?php echo htmlspecialchars(ucfirst($row['payment_status'])); ?></span></td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr><td colspan="5" class="empty-state">No bookings found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAYMENTS TABLE -->
    <div class="section-card" id="payments">
        <div class="section-header">
            <div class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                M-PESA Payments
            </div>
            <span class="count-badge"><?php echo number_format($total_payments); ?> transactions</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No.</th>
                        <th>Phone</th>
                        <th>Amount (KES)</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = $conn->query("SELECT * FROM mpesa_payments ORDER BY id DESC");
                if ($res && $res->num_rows > 0):
                    while ($row = $res->fetch_assoc()):
                        $result_code = $row['result_code'] ?? '';
                        $result_class = ($result_code == '0' || strtolower($result_code) === 'success')
                            ? 'badge-success'
                            : ($result_code === '' ? 'badge-default' : 'badge-failed');
                        $result_label = ($result_code == '0' || strtolower($result_code) === 'success')
                            ? 'Success'
                            : ($result_code === '' ? 'Unknown' : 'Failed (' . htmlspecialchars($result_code) . ')');
                ?>
                    <tr>
                        <td class="cell-mono"><?php echo htmlspecialchars($row['receipt_number']); ?></td>
                        <td class="cell-mono"><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td style="font-weight: 500;"><?php echo number_format($row['amount'], 2); ?></td>
                        <td><span class="badge <?php echo $result_class; ?>"><?php echo $result_label; ?></span></td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr><td colspan="4" class="empty-state">No payment records found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="last-updated">Last updated: <?php echo date('D, d M Y · H:i:s'); ?></p>

</main>

</body>
</html>