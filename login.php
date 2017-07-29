<?php
include 'include/header.php';

if(!empty($_GET['redirect'])) {
    $redirect = htmlspecialchars($_GET['redirect']);
}
else {
    $redirect = "index.php";
}

$url = $http . $_SERVER['HTTP_HOST'] . "/" . $redirect;

$header = "Location: " . $http . $_SERVER['HTTP_HOST'] . "/" . $redirect;

// If we're logging out, reset authentication and redirect
if (!empty($_GET['logout'])) {
    $_SESSION['authenticated'] = 0;
    header($header);
    die();
}

// If we're already logged in, redirect
if ($_SESSION['authenticated'] != 0) {
    header($header);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
    </head>
<body>
    <?php include "include/menu.php"; ?>
    <h2>Please Login</h2>

<?php

// If we're logging in check the username and password
if (!empty($_POST['username']) && !empty($_POST['password'])) {
   $password = $_POST['password'];

   $stmt = $database->prepare("SELECT * FROM Users WHERE ID = :ID");
   $stmt->bindValue(':ID', $_POST['username']);
   $stmt->execute();
   $user = $stmt->fetch();
   if(!empty($user)) {
       if(password_verify($password, $user["Password"])) {
           $_SESSION['authenticated'] = $user["Access"];
           $_SESSION['ID'] = $user['ID'];
           if($user["Visited"] == 0) {
               $url = $http . $_SERVER['HTTP_HOST'] . "/newpass.php";
           }
           echo "<script type='text/javascript'>document.location.href='{$url}';</script>";
           echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
       }
       else {
           echo "<p>Wrong password.</p>";
       }
   }
   else {
       echo "<p>Wrong username.</p>";
   }
}
?>
<form action="login.php?redirect=<?php echo $redirect ?>" method="post">
    <label for="username">Username: </label><input type="text" name="username" /><br />
    <label for="password">Password: </label><input type="password" name="password" /><br />
    <input type="submit" value="login" />
</form>
</body>
</html>