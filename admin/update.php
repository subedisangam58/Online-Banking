<?php
session_start();
require_once '../connection.php';
$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    $sql = "SELECT * FROM users WHERE User_id = $userId";
    $result = $connection->query($sql);
    
    if (!$result) {
        trigger_error('Invalid query: ' . $connection->error);
    }
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['Name'];
        $client_id = $row['Client_id'];
        $national_id = $row['National_id'];
        $phone = $row['Phone'];
        $email = $row['Email'];
        $address = $row['Address'];
    } else {
        $msg = "User not found.";
    }
} else {
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_update'])) {
        $name = $_POST['name'];
        $client_id = $_POST['client_id'];
        $national_id = $_POST['national_id'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $update_query = "UPDATE users SET 
                        Name = '$name',
                        Client_id = '$client_id',
                        National_id = '$national_id',
                        Phone = '$phone',
                        Email = '$email',
                        Address = '$address'
                        WHERE user_id = $user_id";
        
        if ($connection->query($update_query) === TRUE) {
            $msg = "Record updated successfully";
            header("Location: clients.php");
        } else {
            $msg = "Error updating record: " . $connection->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" href="../account.css">
    <link rel="stylesheet" href="../index.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <h1>Update User</h1>
        <?php echo $msg; ?>
        <form method="post">
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" value="<?php echo $name; ?>"><br><br>
            
            <label for="client_id">Client ID:</label><br>
            <input type="text" id="client_id" name="client_id" value="<?php echo $client_id; ?>" readonly><br><br>
            
            <label for="national_id">National ID:</label><br>
            <input type="text" id="national_id" name="national_id" value="<?php echo $national_id; ?>"><br><br>
            
            <label for="phone">Phone:</label><br>
            <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>"><br><br>
            
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>"><br><br>
            
            <label for="address">Address:</label><br>
            <input type="text" id="address" name="address" value="<?php echo $address; ?>"><br><br>
            
            <input type="submit" name="submit_update" value="Update">
        </form>
    </div>
</div>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="../script/toggle.js"></script>
</body>
</html>

<?php
$connection->close();
?>
