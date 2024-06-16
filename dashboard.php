<?php
session_start();
require_once 'connection.php';
if(!isset($_SESSION['admin_id'])){
    header('location:login.php?err=1');
    exit;
}

$user_id = $_SESSION['admin_id'];
$query = "SELECT *
          FROM users WHERE user_id = $user_id";
$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $name = $user_data['Name'];
    $account_amount = $user_data['Amount'];
}
$sql = "SELECT count(id) AS transaction_count FROM Transactions where Tuser_id = $user_id";
$results = mysqli_query($connection,$sql);
if ($results) {
    $row = mysqli_fetch_assoc($results);
    $transaction_count = $row['transaction_count'];
} else {
    $transaction_count = "Error: " . mysqli_error($connection);
}

$deposit_sql = "SELECT SUM(amount) AS total_deposit FROM Transactions WHERE Tuser_id = $user_id AND remarks = 'deposit'";
$withdrawal_sql = "SELECT SUM(amount) AS total_withdrawal FROM Transactions WHERE Tuser_id = $user_id AND remarks = 'withdrawal'";

$deposit_result = mysqli_query($connection, $deposit_sql);
$withdrawal_result = mysqli_query($connection, $withdrawal_sql);

$total_deposit = ($deposit_result && mysqli_num_rows($deposit_result) > 0) ? mysqli_fetch_assoc($deposit_result)['total_deposit'] : 0;
$total_withdrawal = ($withdrawal_result && mysqli_num_rows($withdrawal_result) > 0) ? mysqli_fetch_assoc($withdrawal_result)['total_withdrawal'] : 0;

// Fetching total amount spent by the user on each date in the current month
$currentDate = date('Y-m-d');
$firstDayOfMonth = date('Y-m-01');

$sql = "SELECT DATE(Date) AS transaction_date, SUM(amount) AS total_amount 
        FROM Transactions 
        WHERE Tuser_id = $user_id 
        AND Date >= '$firstDayOfMonth' 
        AND Date <= '$currentDate'
        GROUP BY DATE(Date)";

$result = mysqli_query($connection, $sql);

$labels = array(); // Array to store dates
$data = array(); // Array to store total amounts spent on each date

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['transaction_date']; // Store dates
        $data[] = $row['total_amount']; // Store total amounts spent on each date
    }
} else {
    // If no data found, set default values
    $labels[] = $currentDate;
    $data[] = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>
        <h4>Welcome,<?php echo $name;?></h4>
        <div class="cardBox">
            <div class="card">
                <div>
                    <div class="numbers">Rs. <?php echo number_format($total_deposit, 2); ?></div>
                    <div class="cardName">Deposits</div>
                </div>
                <div class="iconBx">
                    <i class="fa-solid fa-money-bills"></i>
                </div>
            </div>
            <div class="card">
                <div>
                    <div class="numbers">Rs. <?php echo number_format($total_withdrawal, 2); ?></div>
                    <div class="cardName">Withdrawals</div>
                </div>
                <div class="iconBx">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
            </div>
            <div class="card">
                <div>
                    <div class="numbers"><?php echo $transaction_count; ?></div>
                    <div class="cardName">Transfers</div>
                </div>
                <div class="iconBx">
                    <ion-icon name="swap-horizontal-outline"></ion-icon>
                </div>
            </div>
            <div class="card">
                <div>
                    <div class="numbers">Rs.<?php echo $account_amount; ?></div>
                    <div class="cardName">Balance</div>
                </div>
                <div class="iconBx">
                    <ion-icon name="eye-outline"></ion-icon>
                </div>
            </div>
        </div>
        <div class="graphBox">
            <h2></h2>
            <div class="box">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>
        
      
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="admin/toggle.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            const balanceSection = document.querySelector('.card:nth-child(4)'); // Selecting the balance section
            const eyeIcon = balanceSection.querySelector('.iconBx ion-icon'); // Selecting the eye icon within the balance section
            const balanceElement = balanceSection.querySelector('.numbers'); // Selecting the balance element within the balance section

            // Hide the balance amount by default
            balanceElement.dataset.originalValue = balanceElement.textContent;
            balanceElement.textContent = '*'.repeat(balanceElement.textContent.length);
            balanceElement.classList.add('hidden');

            eyeIcon.addEventListener('click', () => {
                if (balanceElement.classList.contains('hidden')) {
                    // Show numbers
                    balanceElement.classList.remove('hidden');
                    balanceElement.textContent = balanceElement.dataset.originalValue;
                    eyeIcon.setAttribute('name', 'eye-outline');
                } else {
                    // Hide numbers
                    balanceElement.dataset.originalValue = balanceElement.textContent;
                    balanceElement.textContent = '*'.repeat(balanceElement.textContent.length);
                    balanceElement.classList.add('hidden');
                    eyeIcon.setAttribute('name', 'eye-off-outline');
                }
            });
        });

        // for chart
        const labels = []; // Initialize labels array
        const dataPoints = <?php echo json_encode($data); ?>; // Data points array

        // Fill labels array starting from the 6th day of the month
        for (let i = 1; i <= 30; i++) {
            labels.push(i);
        }

        // If the current day is less than 6, add labels for the previous month
        const currentDate = new Date().getDate();
        if (currentDate < 6) {
            const daysInPrevMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 0).getDate();
            const remainingDays = 6 - currentDate;
            for (let i = daysInPrevMonth - remainingDays + 1; i <= daysInPrevMonth; i++) {
                labels.push(i);
            }
        }

        const data = {
            labels: labels,
            datasets: [{
                label: 'Total Amount Spent',
                data: dataPoints,
                fill: false,
                borderColor: 'rgba(255, 99, 132, 1)',
                tension: 0.1
            }]
        };
        const config = {
            type: 'line',
            data: data,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };
        var myChart = new Chart(
            document.getElementById('myChart'), config
        );
    </script>
</body>
</html>