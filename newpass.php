<?php
include "include/header.php";

// Redirect to login if we're not logged in
if($_SESSION['authenticated'] == 0) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

if($_SESSION['authenticated'] > 1) {
    $admin = true;
}
else {
    $admin = false;
}

$id = $_SESSION['ID'];

if(!empty($_GET['id'])) {
    $id = $_GET['id'];
}

if(!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $newpass = $_POST['password'];
    
    $stmt = $database->prepare("UPDATE Users SET Password = :Password WHERE ID = :ID");
    if(!empty($_POST['username'])) {
        $id = $_POST['username'];
    }
    $stmt->bindValue(':ID', $id);
    $stmt->bindValue(':Password', $password);
    $stmt->execute();
    $count = $stmt->rowCount();
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Reset Password</title>
    </head>
<body>
    <?php
    include "include/menu.php"; ?>
    <h2>Reset Password</h2>
    <?php if(!empty($_POST['password'])) { 
        if($count != 0) {
            $stmt = $database->prepare("UPDATE Users SET Visited = 1 WHERE ID = :ID");
            $stmt->bindValue(':ID', $id);
            $stmt->execute();
            echo '<p>Password updated!</p>';
        }
        else {
            echo '<p>There was a problem updating your password</p>';
        }
     } else { ?>
        <form method="post" action="newpass.php">
            <?php if($admin) { ?>
            <label for="username">Username: </label><input type="text" name="username" value="<?php echo $id; ?>" /><br />
            <?php } ?>
            <label for="password">New Password: </label><input type="password" name="password" /><br />
            <input type="submit" name="submit" value="Submit">
        </form>
    <?php } ?>
</body>
</html>