    const passengerInput = document.getElementById('passenger-input');
    const buttonMinus = document.getElementById('button-minus');
    const buttonPlus = document.getElementById('button-plus');
    
    buttonMinus.addEventListener('click', function () {
        if (passengerInput.value === "") {
            passengerInput.value = 0;
        }
        let currentValue = parseInt(passengerInput.value);
        if (currentValue > 0) {
            passengerInput.value = currentValue - 1;
        }
    });
    
    buttonPlus.addEventListener('click', function () {
        if (passengerInput.value === "") {
            passengerInput.value = 0;
        }
        let currentValue = parseInt(passengerInput.value);
        passengerInput.value = currentValue + 1;
    });