// ===============================
// HIRESPHERE BOOKING CALCULATOR
// ===============================

const pickupDate = document.getElementById("start_date");
const returnDate = document.getElementById("end_date");

const totalDays = document.getElementById("totalDays");
const subtotalPrice = document.getElementById("subtotalPrice");
const totalPrice = document.getElementById("totalPrice");

// PRICING
const rateInput = document.getElementById("daily_rate_input");
const dailyRate = rateInput ? parseInt(rateInput.value) : 14000;
const serviceFee = 2000;

// CALCULATE BOOKING
function calculateBooking(){

    if (!pickupDate || !returnDate || !totalDays || !subtotalPrice || !totalPrice) {
        return;
    }

    // STOP IF INPUTS EMPTY
    if(!pickupDate.value || !returnDate.value){
        return;
    }

    // CONVERT DATES
    const returning = new Date(returnDate.value);

    // CALCULATE DIFFERENCE
    const difference = returning - pickup;

    // CONVERT TO DAYS
    const days = difference / (1000 * 60 * 60 * 24);

    // INVALID DATE CHECK
    if(days <= 0){

        alert("Return date must be after pickup date.");

        totalDays.innerText = 0;
        subtotalPrice.innerText = 0;
        totalPrice.innerText = 0;

        return;
    }

    // SUBTOTAL
    const subtotal = days * dailyRate;

    // FINAL TOTAL
    const total = subtotal + serviceFee;

    // UPDATE UI
    totalDays.innerText = days;

    subtotalPrice.innerText =
        subtotal.toLocaleString();

    totalPrice.innerText =
        total.toLocaleString();

}

// EVENT LISTENERS
if (pickupDate && returnDate && totalDays && subtotalPrice && totalPrice) {
    pickupDate.addEventListener("change", calculateBooking);
    returnDate.addEventListener("change", calculateBooking);
}


// ===============================
// PAYMENT METHOD SWITCHING
// ===============================

const methodRadios = document.querySelectorAll('input[name="method"]');
const stripeDetails = document.getElementById("stripe-details");

if (methodRadios && stripeDetails) {
    methodRadios.forEach(radio => {
        radio.addEventListener("change", (e) => {
            if (e.target.value === "stripe") {
                stripeDetails.classList.remove("d-none");
            } else {
                stripeDetails.classList.add("d-none");
            }
        });
    });
}

// ===============================
// PASSWORD TOGGLE
// ===============================

const passwordToggles =
    document.querySelectorAll(".password-toggle");

passwordToggles.forEach(toggle => {

    toggle.addEventListener("click", () => {

        const passwordField =
            toggle.parentElement.querySelector(
                ".password-field"
            );

        const icon =
            toggle.querySelector("i");

        // TOGGLE TYPE
        if (!passwordField || !icon) return;

        if(passwordField.type === "password"){

            passwordField.type = "text";

            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");

        }else{

            passwordField.type = "password";

            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");

        }

    });

});

// ===============================
// HIRESPHERE FILTER SYSTEM
// ===============================

(() => {
    // Run only on pages containing the vehicle filter sidebar.
    const typeFilter = document.getElementById("filterType");
    const priceFilter = document.getElementById("filterPrice");
    const transmissionFilter = document.getElementById("filterTransmission");
    const resetButton = document.getElementById("resetFilters");
    const resultCount = document.getElementById("hsResultsCount");
    const emptyMessage = document.getElementById("hsNoResults");

    if (!typeFilter || !priceFilter || !transmissionFilter) {
        return;
    }

    // Select the complete listing cards, including their filter data.
    const listingCards = document.querySelectorAll(
        "#featured-vehicles .car-card"
    );

    function filterListings() {
        let visibleCount = 0;

        listingCards.forEach((card) => {
            // Parse decimal prices without rounding them down.
            const price = Number(card.dataset.price);

            const matchesType =
                typeFilter.value === "all" ||
                typeFilter.value === card.dataset.type;

            const matchesTransmission =
                transmissionFilter.value === "all" ||
                transmissionFilter.value === card.dataset.transmission;

            // Apply the selected daily budget range.
            let matchesPrice = true;

            if (priceFilter.value === "low") {
                matchesPrice = price < 5000;
            } else if (priceFilter.value === "mid") {
                matchesPrice = price >= 5000 && price <= 10000;
            } else if (priceFilter.value === "high") {
                matchesPrice = price > 10000;
            }

            const visible =
                matchesType && matchesTransmission && matchesPrice;

            // Hide the entire card to avoid leaving empty listing spaces.
            card.classList.toggle("hidden", !visible);

            if (visible) {
                visibleCount++;
            }
        });

        // Announce the current number of matching listings.
        if (resultCount) {
            resultCount.textContent =
                `${visibleCount} vehicle${visibleCount === 1 ? "" : "s"}`;
        }

        // Explain an empty result instead of leaving a blank area.
        if (emptyMessage) {
            emptyMessage.hidden = visibleCount !== 0;
        }
    }

    // Reapply filters whenever a sidebar selection changes.
    [typeFilter, priceFilter, transmissionFilter].forEach((filter) => {
        filter.addEventListener("change", filterListings);
    });

    // Reset every filter and display all available listings.
    resetButton?.addEventListener("click", () => {
        typeFilter.value = "all";
        priceFilter.value = "all";
        transmissionFilter.value = "all";
        filterListings();
    });

    // Initialize the count and empty state when the page loads.
    filterListings();
})();

// ===============================
// SOCIAL LOGIN HANDLER
// ===============================

const socialButtons = document.querySelectorAll(".js-social-login");

socialButtons.forEach(button => {
    button.addEventListener("click", (e) => {
        const provider = e.currentTarget.getAttribute("data-provider");
        
        // Professional feedback instead of inline alert
        console.log(`Initialising ${provider} Auth Flow...`);
        alert(`${provider} login is currently under maintenance. Please use your email to login.`);
    });
});