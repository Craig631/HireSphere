<?php
require_once "auth/guard.php";

$user_id = $_SESSION['user_id'];

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config/database.php';
include 'includes/auth.php';

if(!isset($_GET['id'])){
    die("Invalid request");
}

$vehicle_id = (int)$_GET['id'];

// FETCH VEHICLE
$stmt = mysqli_prepare($conn, "SELECT * FROM vehicles WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $vehicle_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    if (mysqli_num_rows($result) === 0) {
        die("Vehicle not found. Please return to the homepage.");
    } else {
        die("Database query failed: " . mysqli_error($conn));
    }
}

$vehicle = mysqli_fetch_assoc($result);


// HANDLE BOOKING
if(isset($_POST['book'])){

    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $customer_phone = $_POST['customer_phone'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // 1️⃣ BOOKING CONFLICT PREVENTION
    $check = mysqli_prepare($conn,
        "SELECT id FROM bookings
         WHERE vehicle_id = ?
         AND status != 'cancelled'
         AND (
                (start_date <= ? AND end_date >= ?)
             OR (start_date <= ? AND end_date >= ?)
             OR (? <= start_date AND ? >= end_date)
         )
    "
    );

    mysqli_stmt_bind_param(
        $check,
        "issssss",
        $vehicle_id,
        $start_date,
        $start_date,
        $end_date,
        $end_date,
        $start_date,
        $end_date
    );

    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {

        die("
            <div style='
                padding:40px;
                font-family:sans-serif;
                text-align:center;
            '>

                <h2>Vehicle Already Booked</h2>

                <p>
                    The selected dates conflict with an existing booking.
                </p>

                <a href='javascript:history.back()'>
                    Go Back
                </a>

            </div>
        ");
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO bookings 
        (vehicle_id, user_id, customer_name, customer_email, customer_phone, start_date, end_date, status, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NULL, 'pending')"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iisssss",
        $vehicle_id,
        $user_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $start_date,
        $end_date
    );

    $exec_result = mysqli_stmt_execute($stmt);

    if($exec_result){
        $booking_id = mysqli_insert_id($conn);

        // Calculate duration and total price
        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        $days = $date1->diff($date2)->days;
        if($days < 1) $days = 1;
        $price = ($days * $vehicle['price_per_day']) + 2000;

        // Save the calculated price to the booking for M-Pesa to use later
        mysqli_query($conn, "UPDATE bookings SET paid_amount = $price WHERE id = $booking_id");

        header("Location: payment.php?vehicle_id=" . (int)$vehicle_id . "&amount=" . (float)$price . "&booking_id=" . $booking_id);
        exit();
    } else {
        die(mysqli_error($conn));
    }
}

// INCLUDE HEADER ONLY AFTER LOGIC
include 'includes/header.php';
?>

<div class="container py-5">

    <div class="row">

        <!-- VEHICLE SUMMARY -->
        <div class="col-lg-5">

            <div class="card p-3 shadow-sm border-0">

                <img src="uploads/<?php echo $vehicle['image']; ?>"
                     class="img-fluid rounded">

                <h4 class="mt-3 fw-bold">
                    <?php echo $vehicle['vehicle_name']; ?>
                </h4>

                <p class="text-muted">
                    KES <?php echo number_format($vehicle['price_per_day']); ?>/day
                </p>

            </div>

        </div>

        <!-- BOOKING FORM -->
        <div class="col-lg-7">

            <div class="card p-4 shadow-sm border-0">

                <h3 class="fw-bold mb-4">
                    Reserve This Vehicle
                </h3>

                <form method="POST">

                    <div class="mb-3">

                        <label>Name</label>
                        <input type="text"
                               name="customer_name"
                               class="form-control"
                               required>

                    </div>

                    <div class="mb-3">

                        <label>Email Address</label>
                        <input type="email"
                               name="customer_email"
                               class="form-control"
                               required>

                    </div>

                    <div class="mb-3">

                        <label>Phone</label>
                        <input type="text"
                               name="customer_phone"
                               class="form-control">

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>Start Date</label>
                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   value="<?php echo $_GET['start_date'] ?? ''; ?>"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label>End Date</label>
                            <input type="date"
                                   name="end_date"
                                   class="form-control"
                                   value="<?php echo $_GET['end_date'] ?? ''; ?>"
                                   required>

                        </div>

                    </div>

                    <button type="submit"
                            name="book"
                            class="btn btn-primary w-100">

                        Confirm Booking

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>