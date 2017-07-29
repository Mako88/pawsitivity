<?php
include "include/header.php";

// Don't allow clients to create new services.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Service</title>
    </head>
<body>
<?php include "include/menu.php"; ?>
<h2>Add Service</h2>

<?php

if(!empty($_POST['name']) && !empty($_POST['price']) && !empty($_POST['time'])) {
    (!empty($_POST['signature'])) ? $sig = 1 : $sig = 0;
    
    $stmt = $database->prepare('INSERT INTO Services (Name, Price, Time, Signature) VALUES (:Name, :Price, :Time, :Signature)');
    $stmt->bindValue(':Name', $_POST['name']);
    $stmt->bindValue(':Price', $_POST['price']);
    $stmt->bindValue(':Time', $_POST['time']);
    $stmt->bindValue(':Signature', $sig);
    $stmt->execute();
    echo "<p>Service added!</p>";
}
else {
?>
    <form method="post" action="newservice.php">
        <label for="name">Name: </label><input id="name" type="text" name="name" /><br />
        <label for="price">Price: </label><input id="price" type="text" name="price" /><br />
        <label for="time">Time (In Minutes): </label><input id="time" type="text" name="time" /><br />
        <label for="signature">Signature Service: </label><input id="signature" type="checkbox" name="signature" /><br />
        <input type="submit" name="submit" value="Add Service">
    </form>

<?php
}
?>
    
</body>
</html>