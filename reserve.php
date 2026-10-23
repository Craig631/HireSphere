<?php
require_once 'auth/guard.php';
require_once 'config/database.php';
require_once 'includes/booking-rules.php';

class BookingRuleException extends RuntimeException
{
}

$userId = (int) $_SESSION['user_id'];
$vehicleId = (int) ($_GET['id'] ?? 0);
$start = (string) ($_POST['start_date'] ?? $_GET['start_date'] ?? '');
$end = (string) ($_POST['end_date'] ?? $_GET['end_date'] ?? '');
$name = trim((string) ($_POST['customer_name'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? ''));
$phone = trim((string) ($_POST['customer_phone'] ?? ''));
$error = '';

$vehicleStmt = $conn->prepare(
    'SELECT id, host_id, vehicle_name, price_per_day, image FROM vehicles WHERE id = ?'
);
$vehicleStmt->bind_param('i', $vehicleId);
$vehicleStmt->execute();
$vehicle = $vehicleStmt->get_result()->fetch_assoc();

if (!$vehicle) {
    http_response_code(404);
    exit('Vehicle not found.');
}

if ((int) $vehicle['host_id'] === $userId) {
    http_response_code(403);
    exit('You cannot reserve your own vehicle.');
}

$dateError = !hsValidBookingDates($start, $end);
$days = 0;
$total = 0.0;

if (!$dateError) {
    $days = (int) (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days;
    $total = ($days * (float) $vehicle['price_per_day']) + 2000;
} else {
    $error = 'Choose a valid pickup and return date from the vehicle calendar.';
}

if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dateError) {
    if (!hash_equals(
        $_SESSION['booking_csrf'],
        (string) ($_POST['csrf_token'] ?? '')
    )) {
        $error = 'Your form expired. Refresh the page and try again.';
    } elseif ($name === '' || strlen($name) > 150) {
        $error = 'Enter a name of up to 150 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
        $error = 'Enter a valid email address.';
    } elseif ($phone === '' || strlen($phone) > 30) {
        $error = 'Enter a valid contact phone number.';
    } else {
        $conn->begin_transaction();

        try {
            $lock = $conn->prepare(
                'SELECT host_id, price_per_day FROM vehicles WHERE id = ? FOR UPDATE'
            );
            $lock->bind_param('i', $vehicleId);
            $lock->execute();
            $currentVehicle = $lock->get_result()->fetch_assoc();

            if (!$currentVehicle || (int) $currentVehicle['host_id'] === $userId) {
                throw new BookingRuleException('This vehicle cannot be reserved.');
            }

            if (hsHasConflict($conn, $vehicleId, $start, $end)) {
                throw new BookingRuleException(
                    'Someone has reserved these dates. Please choose another period.'
                );
            }

            $total = ($days * (float) $currentVehicle['price_per_day']) + 2000;
            $insert = $conn->prepare(
                "INSERT INTO bookings
                 (vehicle_id, user_id, customer_name, customer_email,
                  customer_phone, start_date, end_date, status,
                  payment_status, paid_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?)"
            );
            $insert->bind_param(
                'iisssssd',
                $vehicleId,
                $userId,
                $name,
                $email,
                $phone,
                $start,
                $end,
                $total
            );
            $insert->execute();
            $bookingId = $conn->insert_id;
            $conn->commit();

            $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
            header("Location: payment.php?vehicle_id=$vehicleId&booking_id=$bookingId");
            exit;
        } catch (BookingRuleException $exception) {
            $conn->rollback();
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log($exception->getMessage());
            $error = 'The reservation could not be saved. Please try again.';
        }
    }
}

include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4">
                <img src="uploads/<?= hsEscape($vehicle['image']) ?>"
                     alt="<?= hsEscape($vehicle['vehicle_name']) ?>"
                     class="img-fluid rounded-3">
                <h2 class="h4 fw-bold mt-3"><?= hsEscape($vehicle['vehicle_name']) ?></h2>

                <?php if (!$dateError): ?>
                    <p class="mb-2"><?= hsEscape($start) ?> &rarr; <?= hsEscape($end) ?></p>
                    <p class="text-muted">
                        <?= $days ?> days &middot; KES
                        <?= number_format((float) $vehicle['price_per_day'], 2) ?>/day
                    </p>
                    <p class="mb-1">Service fee: KES 2,000</p>
                    <h3 class="h5 mt-3">Total: KES <?= number_format($total, 2) ?></h3>
                <?php endif; ?>

                <a href="vehicle-details.php?id=<?= $vehicleId ?>" class="mt-3">Change dates</a>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4">
                <h1 class="h3 fw-bold">Customer details</h1>
                <p class="text-muted">Next: choose your payment method.</p>

                <?php if ($error): ?>
                    <div class="alert alert-warning" role="alert"><?= hsEscape($error) ?></div>
                <?php endif; ?>

                <?php if (!$dateError): ?>
                    <form method="post" action="reserve.php?id=<?= $vehicleId ?>">
                        <input type="hidden" name="start_date" value="<?= hsEscape($start) ?>">
                        <input type="hidden" name="end_date" value="<?= hsEscape($end) ?>">
                        <input type="hidden" name="csrf_token"
                               value="<?= hsEscape($_SESSION['booking_csrf']) ?>">

                        <div class="mb-3">
                            <label for="customerName" class="form-label">Name</label>
                            <input id="customerName" name="customer_name"
                                   class="form-control" maxlength="150"
                                   autocomplete="name" value="<?= hsEscape($name) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Email</label>
                            <input type="email" id="customerEmail" name="customer_email"
                                   class="form-control" maxlength="191" autocomplete="email"
                                   value="<?= hsEscape($email) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="customerPhone" class="form-label">Phone</label>
                            <input type="tel" id="customerPhone" name="customer_phone"
                                   class="form-control" maxlength="30" autocomplete="tel"
                                   value="<?= hsEscape($phone) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Continue to payment
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>