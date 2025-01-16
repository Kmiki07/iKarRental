<?php
include_once("storage/bookingstorage.php");
include_once("storage/userstorage.php");
include_once("auth.php");

$bs = new BookingStorage();
$bookings = $bs->findAll();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

function getUnavailableDates($bookings)
{
    $unavailableDates = [];
    foreach ($bookings as $booking) {
        if ($booking['car_id'] != $_GET['id']) {
            continue;
        }
        $startDate = new DateTime($booking['start_date']);
        $endDate = new DateTime($booking['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate->modify('-1 day'), $interval, $endDate/*->modify('+1 day')*/);

        foreach ($dateRange as $date) {
            $unavailableDates[] = $date->format('Y-m-d');
        }
    }
    return $unavailableDates;
}

$unavailableDates = getUnavailableDates($bookings);

session_start();
$auth = new Auth(new UserStorage());
$isauthenticated = $auth->is_authenticated();
//$user = $auth->authenticated_user();

if (!$isauthenticated) {
    header('Location: login.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="style/calendarstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

</head>

<body>
    <?php if (!$isauthenticated): ?>
        <nav class="navbar navbar-expand-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">iKarRental</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                    <!-- <img src="media/profile pictures/default.png"> -->
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav mb-2 mb-lg-0 d-flex justify-content-end">
                        <li class="nav-item mx-2 mt-3 my-lg-0 d-flex justify-content-end">
                            <a href="login.php"><button class="btn-plain">Bejelentkezés</button></a>
                        </li>
                        <li class="nav-item mx-2 mt-3 my-lg-0 d-flex justify-content-end">
                            <a href="register.php"><button class="btn-yellow">Regisztráció</button></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <?php else: ?>
        <nav class="navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">iKarRental<?= $auth->authorize(["admin"]) ?  " - ADMIN" : "" ?></a>

                <img class="pfp-navbar" src="<?= $user["pfp"] ?? "media/profile pictures/default.png" ?>" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav mb-2 mb-lg-0 d-flex justify-content-end">
                        <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                            <a class="my-1" href="profile.php"><button class="btn-plain">Profil</button></a>
                        </li>
                        <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                            <a class="my-1" href="logout.php"><button class="btn-plain">Kijelentkezés</button></a>
                        </li>
                        <?php if ($auth->authorize(["admin"])): ?>
                            <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                                <a class="my-1" href="add.php"><button class="btn-plain">Add car</button></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </nav>
    <?php endif; ?>

    <div class="calendar-body">
        <!-- <?php echo json_encode($unavailableDates); ?> -->
        <h1 class="text-nowrap">Select Dates</h1>
        <div class="calendar-header">
            <button class="btn btn-primary" id="prev-month">&lt;</button>
            <h2 id="month-year"></h2>
            <button class="btn btn-primary" id="next-month">&gt;</button>
        </div>
        <div id="calendar" class="calendar"></div>
        <p id="selected-dates"></p>
        <div class="confirm-button">
            <a id="confirm-link" href="#">Confirm</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const carId = urlParams.get('id');
            const unavailableDates = <?php echo json_encode($unavailableDates); ?>; // Example unavailable dates
            const calendarElement = document.getElementById('calendar');
            const selectedDatesElement = document.getElementById('selected-dates');
            const monthYearElement = document.getElementById('month-year');
            const confirmLink = document.getElementById('confirm-link');
            const daysOfWeek = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];
            let selectedDates = [];
            let currentYear = new Date().getFullYear();
            let currentMonth = new Date().getMonth();
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Set time to midnight local time

            function createCalendar(year, month) {
                calendarElement.innerHTML = '';

                // Add day headers
                daysOfWeek.forEach(day => {
                    const dayHeader = document.createElement('div');
                    dayHeader.classList.add('day-header');
                    dayHeader.textContent = day;
                    calendarElement.appendChild(dayHeader);
                });

                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();

                monthYearElement.textContent = new Date(year, month).toLocaleString('default', {
                    month: 'long',
                    year: 'numeric'
                });

                // Adjust first day to start from Monday
                const adjustedFirstDay = (firstDay === 0) ? 6 : firstDay - 1;

                for (let i = 0; i < adjustedFirstDay; i++) {
                    calendarElement.innerHTML += '<div></div>';
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const dateString = date.toISOString().split('T')[0];
                    const dayElement = document.createElement('div');
                    dayElement.textContent = day;

                    if (date < today) {
                        dayElement.classList.add('past');
                    } else if (unavailableDates.includes(dateString)) {
                        dayElement.classList.add('disabled');
                    } else {
                        dayElement.addEventListener('click', function() {
                            if (selectedDates.length === 2) {
                                selectedDates = [];
                                updateSelectedDates();
                            }

                            selectedDates.push(new Date(date.getFullYear(), date.getMonth(), date.getDate())); // Ensure the date is set to local midnight
                            selectedDates.sort((a, b) => a - b); // Ensure the earlier date is first
                            updateSelectedDates();

                            if (selectedDates.length === 2) {
                                const [startDate, endDate] = selectedDates;
                                const datesInRange = getDatesInRange(startDate, endDate);
                                const invalidRange = datesInRange.some(date => unavailableDates.includes(date.toISOString().split('T')[0]));

                                if (invalidRange) {
                                    alert('The selected range includes unavailable dates. Please select a different range.');
                                    selectedDates = [];
                                    updateSelectedDates();
                                }
                            }
                        });
                    }

                    calendarElement.appendChild(dayElement);
                }
            }

            function getDatesInRange(startDate, endDate) {
                const dates = [];
                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    dates.push(new Date(currentDate));
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                return dates;
            }

            function updateSelectedDates() {
                const dayElements = calendarElement.querySelectorAll('div');
                dayElements.forEach(dayElement => {
                    dayElement.classList.remove('selected');
                    dayElement.classList.remove('in-range');
                });

                if (selectedDates.length === 1) {
                    const [startDate] = selectedDates;
                    const dateString = startDate.toISOString().split('T')[0];
                    const dayElement = Array.from(dayElements).find(el => {
                        const elDate = new Date(currentYear, currentMonth, el.textContent);
                        elDate.setHours(0, 0, 0, 0); // Set time to midnight local time
                        return el.textContent == startDate.getDate() && elDate.toISOString().split('T')[0] === dateString && !el.classList.contains('disabled');
                    });
                    if (dayElement) {
                        dayElement.classList.add('selected');
                    }
                }

                if (selectedDates.length === 2) {
                    const [startDate, endDate] = selectedDates;
                    const datesInRange = getDatesInRange(startDate, endDate);

                    datesInRange.forEach(date => {
                        const dateString = date.toISOString().split('T')[0];
                        const dayElement = Array.from(dayElements).find(el => {
                            const elDate = new Date(currentYear, currentMonth, el.textContent);
                            elDate.setHours(0, 0, 0, 0); // Set time to midnight local time
                            return el.textContent == date.getDate() && elDate.toISOString().split('T')[0] === dateString && !el.classList.contains('disabled');
                        });
                        if (dayElement) {
                            if (dateString === startDate.toISOString().split('T')[0] || dateString === endDate.toISOString().split('T')[0]) {
                                dayElement.classList.add('selected');
                            } else {
                                dayElement.classList.add('in-range');
                            }
                        }
                    });

                    // Enable the confirm button with the selected dates and car id
                    const startDateString = formatDate(startDate);
                    const endDateString = formatDate(endDate);

                    confirmLink.href = `carpage.php?id=${carId}&startDate=${startDateString}&endDate=${endDateString}`;
                    confirmLink.classList.add('enabled');
                } else {
                    // Disable the confirm button
                    confirmLink.href = '#';
                    confirmLink.classList.remove('enabled');
                }

                selectedDatesElement.textContent = selectedDates.map(date => formatDate(date)).join(' to ');

            }

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            document.getElementById('prev-month').addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                createCalendar(currentYear, currentMonth);
                updateSelectedDates();
            });

            document.getElementById('next-month').addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                createCalendar(currentYear, currentMonth);
                updateSelectedDates();
            });

            createCalendar(currentYear, currentMonth);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>

</html>