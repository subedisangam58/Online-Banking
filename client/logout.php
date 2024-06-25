<?php
session_start();
session_destroy();
setcookie('client_id',false,time()-86400*7);
setcookie('client_name',false,time()-86400*7);
setcookie('client_email',false,time()-86400*7);
header('location:login.php');
?>