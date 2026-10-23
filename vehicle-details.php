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
/* Wait until the page, shared scripts, and Flatpickr are available. */
document.addEventListener('DOMContentLoaded', () => {
    const vehicleId = <?= (int) $vehicle['id'] ?>;

    const pickup = document.getElementById('start_date');
    const returning = document.getElementById('end_date');
    const reserve = document.getElementById('reserve_button');
    const rateInput = document.getElementById('daily_rate_input');

    const daysOutput = document.getElementById('totalDays');
    const subtotalOutput = document.getElementById('subtotalPrice');
    const totalOutput = document.getElementById('totalPrice');

    /* Stop safely if the booking summary markup is incomplete. */
    if (!pickup || !returning || !rateInput ||
        !daysOutput || !subtotalOutput || !totalOutput) {
        return;
    }

    const dailyRate = Number(rateInput.value);
    const serviceFee = 2000;
    let blockedDates = new Set();
    let availabilityReady = false;

    /* Reuse the message element, or create one beneath the reserve control. */
    let message = document.getElementById('bookingDateMessage');

    if (!message) {
        message = document.createElement('p');
        message.id = 'bookingDateMessage';
        message.className = 'small text-muted mt-2';
        message.setAttribute('role', 'status');

        (reserve?.parentElement || totalOutput.parentElement)
            .appendChild(message);
    }

    /* Validate YYYY-MM-DD dates and convert them to whole UTC days. */
    function dateNumber(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return NaN;

        const [year, month, day] = value.split('-').map(Number);
        const milliseconds = Date.UTC(year, month - 1, day);
        const normalized = new Date(milliseconds).toISOString().slice(0, 10);

        return normalized === value ? milliseconds / 86400000 : NaN;
    }

    /* Check dates and every day within the selected rental period. */
    function validateDates() {
        const start = dateNumber(pickup.value);
        const end = dateNumber(returning.value);

        if (!Number.isFinite(start) || !Number.isFinite(end)) {
            return 'Choose your pickup and return dates.';
        }

        if (end <= start) {
            return 'The return date must be after the pickup date.';
        }

        for (let day = start; day <= end; day++) {
            const date = new Date(day * 86400000)
                .toISOString().slice(0, 10);

            if (blockedDates.has(date)) {
                return 'This period includes reserved dates. Choose another period.';
            }
        }

        return '';
    }

    /* Update prices and prepare the URL containing BOTH selected dates. */
    function updateSummary() {
        const error = validateDates();
        const valid = !error && Number.isFinite(dailyRate) && dailyRate > 0;

        const days = valid
            ? dateNumber(returning.value) - dateNumber(pickup.value)
            : 0;

        const subtotal = days * dailyRate;

        daysOutput.textContent = days;
        subtotalOutput.textContent = subtotal.toLocaleString('en-KE');
        totalOutput.textContent = valid
            ? (subtotal + serviceFee).toLocaleString('en-KE')
            : '0';

        message.textContent = !availabilityReady
            ? 'Checking vehicle availability…'
            : error;

        if (reserve) {
            const canContinue = availabilityReady && valid;

            reserve.setAttribute('aria-disabled', String(!canContinue));

            /* Support either the original link or the newer button. */
            if (reserve.tagName === 'BUTTON') {
                reserve.disabled = !canContinue;
            }

            if (canContinue && reserve.tagName === 'A') {
                reserve.href = reservationUrl();
            } else if (reserve.tagName === 'A') {
                reserve.removeAttribute('href');
            }
        }
    }

    /* Pass dates to reserve.php instead of asking the customer to enter them again. */
    function reservationUrl() {
        const parameters = new URLSearchParams({
            id: vehicleId,
            start_date: pickup.value,
            end_date: returning.value
        });

        return `reserve.php?${parameters.toString()}`;
    }

    /* Remove earlier picker instances before installing the corrected handlers. */
    pickup._flatpickr?.destroy();
    returning._flatpickr?.destroy();

    const pickers = [];

    if (typeof flatpickr === 'function') {
        [pickup, returning].forEach((input) => {
            pickers.push(flatpickr(input, {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disableMobile: true,
                onChange: updateSummary
            }));
        });
    } else {
        /* Native date inputs remain usable if the calendar library fails to load. */
        pickup.type = 'date';
        returning.type = 'date';
    }

    /* Also handle typed dates and native date inputs. */
    [pickup, returning].forEach((input) => {
        input.addEventListener('input', updateSummary);
        input.addEventListener('change', updateSummary);
    });

    /* Reload availability so rejected bookings release their calendar dates. */
    async function refreshAvailability() {
        availabilityReady = false;
        updateSummary();

        try {
            const response = await fetch(
                `get-booked-dates.php?vehicle_id=${vehicleId}`,
                { cache: 'no-store' }
            );

            if (!response.ok) throw new Error('Availability request failed.');

            const dates = await response.json();

            if (!Array.isArray(dates)) {
                throw new Error('Invalid availability response.');
            }

            blockedDates = new Set(dates);
            pickers.forEach((picker) => picker.set('disable', dates));

            availabilityReady = true;
            updateSummary();
            return true;
        } catch (error) {
            message.textContent =
                'Availability could not load. Refresh the page and try again.';
            return false;
        }
    }

    reserve?.addEventListener('click', async (event) => {
        /* Prevent the original link from navigating without the selected dates. */
        event.preventDefault();

        if (!await refreshAvailability()) return;

        const error = validateDates();

        if (error) {
            message.textContent = error;
            return;
        }

        window.location.assign(reservationUrl());
    });

    /* Refresh dates when returning to this tab. */
    window.addEventListener('focus', refreshAvailability);

    setInterval(() => {
        if (!document.hidden) refreshAvailability();
    }, 30000);

    refreshAvailability();
});
</script>

<?php include 'includes/footer.php'; ?>