<?php
include "include/header.php";

// Only allow Employees and Admins to create new users.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Breed</title>
<link rel='stylesheet' href='css/styles.css' />
</head>
<body>
<?php include "include/menu.php"; ?>
<h2>Add Breed</h2>
<?php

if(!empty($_POST['Name']) && isset($_POST['Group']) && !empty($_POST['Size']) && isset($_POST['GroomPrice']) && isset($_POST['BathPrice'])) {

    if(is_array($_POST['Time'])) {
        $stmt = $database->prepare('INSERT INTO Breeds (Name, BreedGroup, Size, Time, GroomPrice, BathPrice) VALUES (:Name, :Group, :Size, :Time, :GroomPrice, :BathPrice)');
        $stmt->bindValue(':Name', $_POST['Name']);
        $stmt->bindValue(':Group', $_POST['Group']);
        $stmt->bindValue(':Size', $_POST['Size']);
        $stmt->bindValue(':Time', json_encode($_POST['Time']));
        $stmt->bindValue(':GroomPrice', $_POST['GroomPrice']);
        $stmt->bindValue(':BathPrice', $_POST['BathPrice']);
        $stmt->execute();
        
        echo "<p>Breed added!</p>";
        goto finish;
    }
    else {
        echo "<p>The Time information was corrupted.</p>";
        goto finish;
    }

}
else { ?>
    <form class="infoform" action="newbreed.php" method="post">
        <label for="Name">Breed Name: </label><input type="text" name="Name" id="Name"><br />
        <label for="Group">Group: </label>
        <select name="Group">
            <option value="0">Toy Breeds</option>
            <option value="1">Designer Breeds</option>
            <option value="2">Terriers</option>
            <option value="3">Non-Sporting</option>
            <option value="4">Sporting</option>
            <option value="5">Hound Group</option>
            <option value="6">Herding Group</option>
            <option value="7">Working Group</option>
        </select><br />
        <label for="Size">Size: </label>
        <select name="Size">
            <option value="P">Petite</option>
            <option value="S">Small</option>
            <option value="M">Medium</option>
            <option value="L">Large</option>
            <option value="XL">Extra-Large</option>
        </select>
        <h3 class="nooffset">Time (In Minutes): </h3>
            <h4>Bath Only: </h4>
                <label class="offset" for="BathBath">Bathing Time: </label><input id="BathBath" type="text" name="Time[Bath][BathTime]" /><br />
                <label class="offset" for="BathGroom">Grooming Time: </label><input id="BathGroom" type="text" name="Time[Bath][GroomTime]" />
            <h4>Bath and Haircut: </h4>
                <label class="offset" for="GroomBath">Bathing Time: </label><input id="GroomBath" type="text" name="Time[Groom][BathTime]" /><br />
                <label class="offset" for="GroomGroom">Grooming Time: </label><input id="GroomGroom" type="text" name="Time[Groom][GroomTime]" /><br />
        <label for="BathPrice">Base Bath Price: </label><input type="text" name="BathPrice" id="BathPrice"><br />
        <label for="GroomPrice">Base Grooming Price: </label><input type="text" name="GroomPrice" id="GroomPrice"><br />
        <input type="submit" value="Submit">
    </form>
<?php } finish: ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/menu.js"></script>
</body>
</html>