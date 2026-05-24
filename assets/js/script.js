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
    const pickup = new Date(pickupDate.value);
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

const filterType = document.getElementById("filterType");
const filterPrice = document.getElementById("filterPrice");
const filterTransmission = document.getElementById("filterTransmission");
const resetFilters = document.getElementById("resetFilters");

const cars = document.querySelectorAll(".car-card");

// APPLY FILTERS
function applyFilters(){

    cars.forEach(car => {

        let type = car.dataset.type;
        let price = parseInt(car.dataset.price);
        let transmission = car.dataset.transmission;

        let typeMatch =
            filterType.value === "all" ||
            filterType.value === type;

        let transmissionMatch =
            filterTransmission.value === "all" ||
            filterTransmission.value === transmission;

        let priceMatch = true;

        if(filterPrice.value === "low"){
            priceMatch = price < 5000;
        }
        else if(filterPrice.value === "mid"){
            priceMatch = price >= 5000 && price <= 10000;
        }
        else if(filterPrice.value === "high"){
            priceMatch = price > 10000;
        }

        if(typeMatch && transmissionMatch && priceMatch){
            car.classList.remove("hidden");
        }else{
            car.classList.add("hidden");
        }

    });

}

// EVENT LISTENERS
if (filterType && filterPrice && filterTransmission) {
    filterType.addEventListener("change", applyFilters);
    filterPrice.addEventListener("change", applyFilters);
    filterTransmission.addEventListener("change", applyFilters);
}

// RESET
if (resetFilters) {
    resetFilters.addEventListener("click", () => {

        if (filterType) filterType.value = "all";
        if (filterPrice) filterPrice.value = "all";
        if (filterTransmission) filterTransmission.value = "all";

        applyFilters();

    });
}

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