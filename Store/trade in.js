document.addEventListener('DOMContentLoaded', function () {
    const tradeInForm = document.getElementById('tradeInForm');
    const tradeInEstimate = document.getElementById('tradeInEstimate');
    const tradeInConfirmation = document.getElementById('tradeInConfirmation');
    const acceptButton = document.getElementById('acceptTradeIn');
    const cancelButton = document.getElementById('cancelTradeIn');

    const baseValues = {
        'iphone14': { 128: 400, 256: 450 },
        'oneplus9': { 128: 300, 256: 350 },
        'pixel9': { 128: 350, 256: 400 },
        'galaxyS22': { 128: 450, 500: 500 },
    };

    tradeInForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const deviceName = document.getElementById('deviceName').value;
        const storageSize = parseInt(document.getElementById('storageSize').value, 10);
        const deviceCondition = document.getElementById('deviceCondition').value;

        if (!baseValues[deviceName] || !baseValues[deviceName][storageSize]) {
            alert("Invalid device or storage size.");
            return;
        }

        const baseValue = baseValues[deviceName][storageSize];
        const conditionMultiplier = deviceCondition === 'yes' ? 1.0 : 0.7;
        const finalValue = baseValue * conditionMultiplier;

        tradeInEstimate.innerText = `Your estimated trade-in value is $${finalValue.toFixed(2)}`;
        tradeInConfirmation.style.display = 'block';

        acceptButton.onclick = function () {
            handleAcceptTradeIn(finalValue, deviceName);
        };

        cancelButton.onclick = function () {
            tradeInConfirmation.style.display = 'none';
        };
    });

    function handleAcceptTradeIn(value, deviceName) {
        submitTradeIn(value, deviceName);
    }

    function submitTradeIn(value, deviceName) {
        fetch('trade in.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                device_name: deviceName,
                trade_in_value: value,
            })
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message); // Show message to the user
                if (data.success) {
                    tradeInConfirmation.style.display = 'none';
                    window.location.href = "cart.php"; // Redirect to cart page
                } else {
                    console.error('Error: ', data.message);
                }
            })
            .catch(error => {
                alert("An error occurred while saving the trade-in.");
                console.error('Error during fetch request: ', error);
            });
    }
});
