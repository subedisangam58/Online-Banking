<?php
session_start();
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: ../login.php');
    exit();
}

// CSRF protection - regenerate token if not set
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

$err = [];
$user_id = $_SESSION['client_id'];
$name = $_SESSION['client_name'] ?? '';
$account_amount = 0;

// Fetch user data with prepared statement
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $name = $user_data['Name'];
    $account_amount = $user_data['Amount'];
}

$receiverBankAcNo = $receiverBank = $receiverAcName = $phone = $amount = $remarks = '';
$msg = '';

// Fetch all bank accounts for the dropdown with prepared statement
$bankAccounts = "SELECT * FROM bankaccounts";
$bankAccountsResult = mysqli_query($connection, $bankAccounts);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate form token to prevent CSRF
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $msg = 'Invalid form submission. Please refresh the page and try again.';
    } else {
        // Save the old token temporarily
        $old_token = $_SESSION['form_token'];

        $current_month = date('m');
        $current_year = date('Y');
        $monthly_limit = 50000;

        // Check monthly transaction limit with prepared statement
        $query = "SELECT SUM(Amount) AS total_amount FROM transactions WHERE Tuser_id = ? AND MONTH(Date) = ? AND YEAR(Date) = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $current_month, $current_year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_amount_transferred = $row['total_amount'] ?? 0;

        // Sanitize and validate all inputs
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($amount === false || $amount <= 0) {
            $err['amount'] = 'Invalid amount format! Please enter a valid non-negative numeric value.';
        } elseif ($total_amount_transferred + $amount > $monthly_limit) {
            $err['amount'] = 'Transaction amount exceeds monthly limit of 50000.';
        } elseif ($amount > $account_amount) {
            $err['amount'] = 'Insufficient balance!';
        }

        $receiverBank = filter_input(INPUT_POST, 'receiverBank', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (empty($receiverBank)) {
            $err['receiverBank'] = "Receiver's Bank is required";
        }

        $receiverBankAcNo = filter_input(INPUT_POST, 'receiverBankAcNo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (empty($receiverBankAcNo)) {
            $err['receiverBankAcNo'] = 'Select an account number';
        } else {
            // Validate the account number belongs to the selected bank
            $validate_query = "SELECT * FROM BankAccounts WHERE Account_Number = ? AND Bank_Name = ?";
            $stmt = mysqli_prepare($connection, $validate_query);
            mysqli_stmt_bind_param($stmt, "ss", $receiverBankAcNo, $receiverBank);
            mysqli_stmt_execute($stmt);
            $validate_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($validate_result) === 0) {
                $err['receiverBankAcNo'] = 'The account number does not belong to the selected bank';
            }
        }

        $receiverAcName = filter_input(INPUT_POST, 'receiverAcName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (empty($receiverAcName)) {
            $err['receiverAcName'] = 'Enter full name';
        } else {
            if (!preg_match("/^([A-Z][a-z\s]+)+$/", $receiverAcName)) {
                $err['receiverAcName'] = 'Enter a valid name';
            }
        }

        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (empty($phone)) {
            $err['phone'] = 'Enter the phone number';
        } else {
            if (!preg_match("/^\d{10}$/", $phone)) {
                $err['phone'] = 'Invalid Number! Please enter a valid 10 digit number';
            }
        }

        $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (empty($remarks)) {
            $err['remarks'] = 'Enter any remarks';
        } else {
            if (!preg_match("/^[a-zA-Z0-9,.!?'\s]{0,255}$/", $remarks)) {
                $err['remarks'] = 'Invalid characters in remarks!';
            }
        }

        // Process transaction if validation passes
        if (count($err) == 0) {
            $m = date('m');
            $d = date('d');
            $Tid = "TRN{$m}{$d}" . rand(100, 999);
            $current_date = date('Y-m-d');
            
            // Begin transaction
            mysqli_begin_transaction($connection);
            
            try {
                // Insert transaction with prepared statement
                $sql = "INSERT INTO transactions(Transaction_id, Receiver_Bank_Name, Receiver_Bank_Number, 
                        Receiver_Account_Name, Phone, Amount, Date, Remarks, Tuser_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "sssssdssi", $Tid, $receiverBank, $receiverBankAcNo, 
                                    $receiverAcName, $phone, $amount, $current_date, $remarks, $user_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to insert transaction: " . mysqli_stmt_error($stmt));
                }
                
                // Update sender's account balance
                $update_query_sender = "UPDATE Users SET Amount = Amount - ? WHERE user_id = ?";
                $stmt_sender = mysqli_prepare($connection, $update_query_sender);
                mysqli_stmt_bind_param($stmt_sender, "di", $amount, $user_id);
                
                if (!mysqli_stmt_execute($stmt_sender)) {
                    throw new Exception("Failed to update sender balance: " . mysqli_stmt_error($stmt_sender));
                }
                
                // Check if receiver's account exists in Users table
                $check_receiver = "SELECT COUNT(*) as count FROM Users WHERE Account_Number = ?";
                $stmt_check = mysqli_prepare($connection, $check_receiver);
                mysqli_stmt_bind_param($stmt_check, "s", $receiverBankAcNo);
                mysqli_stmt_execute($stmt_check);
                $receiver_result = mysqli_stmt_get_result($stmt_check);
                $receiver_exists = mysqli_fetch_assoc($receiver_result);
                
                // Only update receiver's balance if account exists in Users table
                if ($receiver_exists['count'] > 0) {
                    $update_query_receiver = "UPDATE Users SET Amount = Amount + ? WHERE Account_Number = ?";
                    $stmt_receiver = mysqli_prepare($connection, $update_query_receiver);
                    mysqli_stmt_bind_param($stmt_receiver, "ds", $amount, $receiverBankAcNo);
                    
                    if (!mysqli_stmt_execute($stmt_receiver)) {
                        throw new Exception("Failed to update receiver balance: " . mysqli_stmt_error($stmt_receiver));
                    }
                }
                
                // Commit transaction
                mysqli_commit($connection);
                $msg = 'Transaction Successful. Money transferred successfully.';
                
                // Refresh user's session data
                $refresh_query = "SELECT * FROM Users WHERE user_id = ?";
                $stmt_refresh = mysqli_prepare($connection, $refresh_query);
                mysqli_stmt_bind_param($stmt_refresh, "i", $user_id);
                mysqli_stmt_execute($stmt_refresh);
                $refresh_result = mysqli_stmt_get_result($stmt_refresh);
                
                if ($refresh_result && mysqli_num_rows($refresh_result) > 0) {
                    $user_data = mysqli_fetch_assoc($refresh_result);
                    $_SESSION['client_name'] = $user_data['Name'];
                    $account_amount = $user_data['Amount'];
                }
                
                // Generate new CSRF token on successful transaction
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
            } 
            catch (Exception $e) {
                // Rollback on error
                mysqli_rollback($connection);
                $msg = 'Transaction failed. Please try again.';
                error_log("Transaction error: " . $e->getMessage());
                // Keep old token on failure
                $_SESSION['form_token'] = $old_token;
            }
        } 
        else {
            // Validation failed, keep the old token
            $_SESSION['form_token'] = $old_token;
        }
    }
}

// XSS protection function
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Transfer</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <div class="cardBox">
            <div class="card">
                <div>
                    <div class="numbers hidden" id="balance" data-original-value="<?php echo h($account_amount); ?>"><?php echo str_repeat('*', strlen((string)$account_amount)); ?></div>
                    <div class="cardName">Balance</div>
                </div>
                <div class="iconBx">
                    <ion-icon name="eye-outline"></ion-icon>
                </div>
            </div>
        </div>
        <h2>Fund Transfer</h2>
        <?php if (!empty($msg)): ?>
            <div class="message"><?php echo h($msg); ?></div>
        <?php endif; ?>
        <div class="form">
            <form action="" method="post" id="transferForm">
                <input type="hidden" name="form_token" value="<?php echo h($_SESSION['form_token']); ?>">
                
                <label for="receiverBank">Receiver's Bank</label>
                <select name="receiverBank" id="receiverBank">
                    <option value="">---Select Bank---</option>
                    <option value="eBank" <?php if ($receiverBank === "eBank") echo "selected"; ?>>eBank</option>
                    <option value="NIC Asia" <?php if ($receiverBank === "NIC Asia") echo "selected"; ?>>NIC Asia</option>
                    <option value="Kumari Bank" <?php if ($receiverBank === "Kumari Bank") echo "selected"; ?>>Kumari Bank</option>
                    <option value="Rastriya banijya bank" <?php if ($receiverBank === "Rastriya banijya bank") echo "selected"; ?>>Rastriya banijya bank</option>
                </select>
                <div class="error"><?php echo isset($err['receiverBank']) ? h($err['receiverBank']) : ''; ?></div>
                
                <label for="receiverBankAcNo">Receiver's Bank Account Number</label>
                <select name="receiverBankAcNo" id="receiverBankAcNo">
                    <option value="">---Select Account Number---</option>
                </select>
                <div class="error"><?php echo isset($err['receiverBankAcNo']) ? h($err['receiverBankAcNo']) : ''; ?></div>

                <label for="receiverAcName">Receiver's Account Name</label>
                <input type="text" name="receiverAcName" id="receiverAcName" value="<?php echo h($receiverAcName); ?>">
                <div class="error"><?php echo isset($err['receiverAcName']) ? h($err['receiverAcName']) : ''; ?></div>

                <label for="phone">Receiver's Mobile Number</label>
                <input type="text" name="phone" id="phone" value="<?php echo h($phone); ?>">
                <div class="error"><?php echo isset($err['phone']) ? h($err['phone']) : ''; ?></div>

                <label for="amount">Amount</label>
                <input type="text" name="amount" id="amount" value="<?php echo h($amount); ?>">
                <div class="error"><?php echo isset($err['amount']) ? h($err['amount']) : ''; ?></div>

                <label for="remarks">Remarks</label>
                <input type="text" name="remarks" id="remarks" value="<?php echo h($remarks); ?>">
                <div class="error"><?php echo isset($err['remarks']) ? h($err['remarks']) : ''; ?></div><br>

                <input type="submit" value="Proceed" id="button">
                <input type="button" value="Cancel" id="cancelBtn">
            </form>
        </div>
    </div>
    <script src="../script/toggle.js"></script>
    <script src="../script/script.js"></script>
    <script>
    $(document).ready(function(){
        // Toggle balance visibility when eye icon is clicked
        window.addEventListener('DOMContentLoaded', (event) => {
            const balanceSection = document.querySelector('.card:nth-child(4)');
            const eyeIcon = balanceSection.querySelector('.iconBx ion-icon');
            const balanceElement = balanceSection.querySelector('.numbers');

            // Hide the balance amount by default
            balanceElement.dataset.originalValue = balanceElement.textContent;
            balanceElement.textContent = '*'.repeat(balanceElement.textContent.length);
            balanceElement.classList.add('hidden');

            eyeIcon.addEventListener('click', () => {
                if (balanceElement.classList.contains('hidden')) {
                    balanceElement.classList.remove('hidden');
                    balanceElement.textContent = balanceElement.dataset.originalValue;
                    eyeIcon.setAttribute('name', 'eye-outline');
                } else {
                    balanceElement.dataset.originalValue = balanceElement.textContent;
                    balanceElement.textContent = '*'.repeat(balanceElement.textContent.length);
                    balanceElement.classList.add('hidden');
                    eyeIcon.setAttribute('name', 'eye-off-outline');
                }
            });
        });

        // Function to refresh balance
        function refreshBalance() {
            $.ajax({
                url: "loadAmount.php",
                type: "GET",
                dataType: "json",
                cache: false,
                success: function(data) {
                    if (data.status === 'success') {
                        // Update the data attribute with the actual balance
                        $("#balance").attr("data-original-value", data.balance);
                        
                        // Update the displayed text based on visibility state
                        if ($("#balance").hasClass("hidden")) {
                            $("#balance").text("*".repeat(data.balance.toString().length));
                        } else {
                            $("#balance").text(data.balance);
                        }
                        
                        console.log("Balance updated successfully");
                    } else {
                        console.error("Error refreshing balance:", data.error || "Unknown error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                }
            });
        }

        // Initial balance refresh
        refreshBalance();
        
        // Handle form submission
        $("#transferForm").on("submit", function(e){
            e.preventDefault(); // Prevent standard form submission
            
            // Serialize form data
            var formData = $(this).serialize();
            
            // Disable submit button to prevent double submission
            $("#button").prop("disabled", true).val("Processing...");
            
            // Submit form via AJAX
            $.ajax({
                url: window.location.href,
                type: "POST",
                data: formData,
                success: function(response) {
                    // Create a temporary div to parse the HTML response
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = response;
                    
                    // Extract the message if any
                    var messageElement = tempDiv.querySelector('.message');
                    if (messageElement && messageElement.innerHTML.includes('Transaction Successful')) {
                        // Update message
                        $(".message").html('Transaction Successful. Money transferred successfully.');
                        
                        // Clear form fields
                        $("#receiverAcName").val('');
                        $("#phone").val('');
                        $("#amount").val('');
                        $("#remarks").val('');
                        
                        // Clear any previous errors
                        $(".error").html('');
                        
                        // Update form token
                        var newToken = $(tempDiv).find('input[name="form_token"]').val();
                        $('input[name="form_token"]').val(newToken);
                        
                        // Refresh the balance
                        setTimeout(refreshBalance, 500);
                        
                        // Show success message
                        alert('Transaction completed successfully!');
                    } else {
                        // Update form with updated token
                        var newToken = $(tempDiv).find('input[name="form_token"]').val();
                        if (newToken) {
                            $('input[name="form_token"]').val(newToken);
                        }
                        
                        // Update message if available
                        if (messageElement) {
                            $(".message").html(messageElement.innerHTML);
                        }
                        
                        // Update form with validation errors
                        tempDiv.querySelectorAll('.error').forEach(function(errorElement) {
                            var fieldName = errorElement.previousElementSibling.getAttribute('name');
                            if (fieldName) {
                                $("[name='" + fieldName + "']").next('.error').html(errorElement.innerHTML);
                            }
                        });
                    }
                    
                    // Re-enable submit button
                    $("#button").prop("disabled", false).val("Proceed");
                },
                error: function(xhr, status, error) {
                    console.error("Form submission error:", status, error);
                    $(".message").html('An error occurred. Please try again.');
                    
                    // Re-enable submit button
                    $("#button").prop("disabled", false).val("Proceed");
                }
            });
        });
        
        // Cancel button handler
        $("#cancelBtn").click(function() {
            window.location.href = "dashboard.php";
        });

        // Event handler for bank selection
        $("#receiverBank").on('change', function() {
            loadAccountNumbers();
        });
        
        // Event handler for account selection
        $("#receiverBankAcNo").on('change', function() {
            loadAccountHolderInfo();
        });
        
        // Initial account numbers load if bank is already selected
        if ($("#receiverBank").val() !== '') {
            loadAccountNumbers();
        }
    });

    function loadAccountNumbers() {
        var selectedBank = $("#receiverBank").val();
        
        if (selectedBank !== '') {
            // Clear existing options first
            $("#receiverBankAcNo").html('<option value="">Loading accounts...</option>');
            
            $.ajax({
                url: "getAccountNumbers.php",
                type: "POST",
                data: { bank: selectedBank },
                dataType: "html",
                success: function(data) {
                    $("#receiverBankAcNo").html(data);
                    
                    // If the account holder name needs to be loaded for a pre-selected account
                    if ($("#receiverBankAcNo").val() !== '') {
                        loadAccountHolderInfo();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    $("#receiverBankAcNo").html('<option value="">Error loading accounts</option>');
                }
            });
        } else {
            // Clear the account numbers dropdown if no bank is selected
            $("#receiverBankAcNo").html('<option value="">---Select Account Number---</option>');
        }
    }

    function loadAccountHolderInfo() {
        var selectedAccount = $("#receiverBankAcNo").val();
        if (selectedAccount !== '') {
            $.ajax({
                url: "getAccountInfo.php",
                type: "POST",
                data: { account: selectedAccount },
                dataType: "json",
                success: function(data) {
                    if (data.error) {
                        console.error("Error loading account info:", data.error);
                    } else {
                        $("#receiverAcName").val(data.name);
                        $("#phone").val(data.phone);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error loading account info:", status, error);
                }
            });
        }
    }

    // Helper function to repeat characters (for older browsers that don't support String.repeat)
    if (!String.prototype.repeat) {
        String.prototype.repeat = function(count) {
            var str = '' + this;
            var result = '';
            for (var i = 0; i < count; i++) {
                result += str;
            }
            return result;
        };
    }
    </script>
</body>
</html>