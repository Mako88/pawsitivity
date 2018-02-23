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
        <link rel='stylesheet' href='css/styles.css' />
    </head>
<body>
<?php include "include/menu.php"; ?>
<h2>Add Service</h2>

<?php

if(!empty($_POST['name']) && isset($_POST['type']) && !empty($_POST['time']) && !empty($_POST['price'])) {
    if(is_array($_POST['time']) && is_array($_POST['price'])) {
        $stmt = $database->prepare('INSERT INTO Services (Name, Description, Type, Time, Price) VALUES (:Name, :Description, :Type, :Time, :Price)');
        $stmt->bindValue(':Name', $_POST['name']);
        $stmt->bindValue(':Description', $_POST['description']);
        $stmt->bindValue(':Type', $_POST['type']);
        $stmt->bindValue(':Time', json_encode($_POST['time']));
        $stmt->bindValue(':Price', json_encode($_POST['price']));
        $stmt->execute();
        echo "<p>Service added!</p>";
    }
    else {
        echo "<p>Could not add service. The time and/or price information was corrupted.</p>";
    }
    
}
else {
?>
    <form class="infoform" method="post" action="newservice.php">
        <label for="name">Name: </label><input id="name" type="text" name="name" /><br />
        <label for="description">Description: </label><textarea id="price" name="description"></textarea><br />
        <label for="type">Type: </label>
        <select name="type" id="type">
            <option value="0">Signature Service</option>
            <option value="1">Bathing Service</option>
            <option value="2">Grooming Service</option>
        </select>
        <h3>Petite Dogs: </h3>
            <label class="offset" for="pt">Time: </label><input id="pt" type="text" name="time[P]" /><br />
            <label class="offset" for="pp">Price: </label><input id="pp" type="text" name="price[P]" />
        <h3>Small Dogs: </h3>
            <label class="offset" for="st">Time: </label><input id="st" type="text" name="time[S]" /><br />
            <label class="offset" for="sp">Price: </label><input id="sp" type="text" name="price[S]" />
        <h3>Medium Dogs: </h3>
            <label class="offset" for="mt">Time: </label><input id="mt" type="text" name="time[M]" /><br />
            <label class="offset" for="mp">Price: </label><input id="mp" type="text" name="price[M]" />
        <h3>Large Dogs: </h3>
            <label class="offset" for="lt">Time: </label><input id="lt" type="text" name="time[L]" /><br />
            <label class="offset" for="lp">Price: </label><input id="lp" type="text" name="price[L]" />
        <h3>Extra Large Dogs: </h3>
            <label class="offset" for="xt">Time: </label><input id="xt" type="text" name="time[XL]" /><br />
            <label class="offset" for="xp">Price: </label><input id="xp" type="text" name="price[XL]" /><br />
        
        <input type="submit" name="submit" value="Add Service">
    </form>

<?php
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/menu.js"></script>
</body>
</html>