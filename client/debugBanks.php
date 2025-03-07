<?php
// A temporary script to check what banks are available in your database
// Place this in the same directory as your other scripts and access it directly in your browser

session_start();
require_once '../connection.php';

echo "<h2>Database Connection Test</h2>";
if ($connection) {
    echo "<p>Database connection: <strong>SUCCESS</strong></p>";
} else {
    echo "<p>Database connection: <strong>FAILED</strong> - " . mysqli_connect_error() . "</p>";
    exit;
}

// Check table structure
echo "<h2>BankAccounts Table Structure</h2>";
$structureQuery = "DESCRIBE BankAccounts";
$structureResult = mysqli_query($connection, $structureQuery);

if (!$structureResult) {
    echo "<p>Query error: " . mysqli_error($connection) . "</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($structureResult)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Available Banks in Database</h2>";
$query = "SELECT DISTINCT Bank_name FROM BankAccounts";
$result = mysqli_query($connection, $query);

if (!$result) {
    echo "<p>Query error: " . mysqli_error($connection) . "</p>";
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . htmlspecialchars($row['Bank_name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No banks found in the database!</p>";
}

echo "<h2>Bank Accounts Sample</h2>";
$accountsQuery = "SELECT account_number, Bank_name, bank_code FROM BankAccounts LIMIT 5";
$accountsResult = mysqli_query($connection, $accountsQuery);

if (!$accountsResult) {
    echo "<p>Query error: " . mysqli_error($connection) . "</p>";
    exit;
}

if (mysqli_num_rows($accountsResult) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Account Number</th><th>Bank Name</th><th>Bank Code</th></tr>";
    while ($row = mysqli_fetch_assoc($accountsResult)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['account_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Bank_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bank_code']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No accounts found in the database!</p>";
}

// Check Users table structure for account info
echo "<h2>Users Table Structure</h2>";
$usersStructureQuery = "DESCRIBE Users";
$usersStructureResult = mysqli_query($connection, $usersStructureQuery);

if (!$usersStructureResult) {
    echo "<p>Query error: " . mysqli_error($connection) . "</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($usersStructureResult)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>