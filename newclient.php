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
<title>Add Client</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/moment-timezone.min.js"></script>
<script src="js/pikaday.js"></script>
<script src="js/pikaday.jquery.js"></script>
<link rel="stylesheet" type="text/css" href="css/pikaday.css" />
</head>
<body>
<?php include "include/menu.php"; ?>
<h2>Add Client</h2>
<?php

if(!empty($_POST['FirstName']) && !empty($_POST['LastName']) && !empty($_POST['Phone'])) {

    $name = $_POST['FirstName'] . " " . $_POST['LastName'];
    echo $name;

    // Generate a password
    $password = '';
    $length = rand(4, 8); // Make it between 4 and 8 characters

    for($i = 0; $i < $length; $i++) {
        do {
            $n = rand(50, 90); // Ascii 2-Z range
        } while(in_array($n, array(58, 59, 60, 61, 62, 63, 64, 65, 69, 73, 76, 79, 85))); // Exclude 0, o, 1, l, i, symbols, and all vowels
        $password .= chr($n);
    }

    $newpass = $password;

    $password = password_hash($password, PASSWORD_DEFAULT);

    // Create SQL query based on fields recieved
    $stmt = $database->prepare('INSERT INTO Owners (FirstName, LastName, Phone, Address1, Address2, City, State, Zip, Country, Email, SpouseName, SpousePhone, Emergency, EmergencyPhone, AuthorizedPickup, APPhone, ReferredBy, DateCreated) VALUES (:FirstName, :LastName, :Phone, :Address1, :Address2, :City, :State, :Zip, :Country, :Email, :SpouseName, :SpousePhone, :Emergency, :EmergencyPhone, :AuthorizedPickup, :APPhone, :ReferredBy, :DateCreated)');
    (!empty($_POST['FirstName'])) ? $stmt->bindValue(':FirstName', $_POST['FirstName']) : $stmt->bindValue(':FirstName', NULL);
    (!empty($_POST['LastName'])) ? $stmt->bindValue(':LastName', $_POST['LastName']) : $stmt->bindValue(':LastName', NULL);
    (!empty($_POST['Phone'])) ? $stmt->bindValue(':Phone', $_POST['Phone']) : $stmt->bindValue(':Phone', NULL);
    (!empty($_POST['Address1'])) ? $stmt->bindValue(':Address1', $_POST['Address1']) : $stmt->bindValue(':Address1', NULL);
    (!empty($_POST['Address2'])) ? $stmt->bindValue(':Address2', $_POST['Address2']) : $stmt->bindValue(':Address2', NULL);
    (!empty($_POST['City'])) ? $stmt->bindValue(':City', $_POST['City']) : $stmt->bindValue(':City', NULL);
    (!empty($_POST['State'])) ? $stmt->bindValue(':State', $_POST['State']) : $stmt->bindValue(':State', NULL);
    (!empty($_POST['Zip'])) ? $stmt->bindValue(':Zip', $_POST['Zip']) : $stmt->bindValue(':Zip', NULL);
    (!empty($_POST['Country'])) ? $stmt->bindValue(':Country', $_POST['Country']) : $stmt->bindValue(':Country', NULL);
    (!empty($_POST['Email'])) ? $stmt->bindValue(':Email', $_POST['Email']) : $stmt->bindValue(':Email', NULL);
    (!empty($_POST['SpouseName'])) ? $stmt->bindValue(':SpouseName', $_POST['SpouseName']) : $stmt->bindValue(':SpouseName', NULL);
    (!empty($_POST['SpousePhone'])) ? $stmt->bindValue(':SpousePhone', $_POST['SpousePhone']) : $stmt->bindValue(':SpousePhone', NULL);
    (!empty($_POST['Emergency'])) ? $stmt->bindValue(':Emergency', $_POST['Emergency']) : $stmt->bindValue(':Emergency', NULL);
    (!empty($_POST['EmergencyPhone'])) ? $stmt->bindValue(':EmergencyPhone', $_POST['EmergencyPhone']) : $stmt->bindValue(':EmergencyPhone', NULL);
    (!empty($_POST['AuthorizedPickup'])) ? $stmt->bindValue(':AuthorizedPickup', $_POST['AuthorizedPickup']) : $stmt->bindValue(':AuthorizedPickup', NULL);
    (!empty($_POST['APPhone'])) ? $stmt->bindValue(':APPhone', $_POST['APPhone']) : $stmt->bindValue(':APPhone', NULL);
    (!empty($_POST['ReferredBy'])) ? $stmt->bindValue(':ReferredBy', $_POST['ReferredBy']) : $stmt->bindValue(':ReferredBy', NULL);
    (!empty($_POST['DateCreated'])) ? $stmt->bindValue(':DateCreated', $_POST['DateCreated']) : $stmt->bindValue(':DateCreated', NULL);

    $stmt->execute();
    $id = $database->lastInsertId();

    $stmt = $database->prepare('INSERT INTO Users (ID, Name, Email, Password, Access, Visited) VALUES (:ID, :Name, :Email, :Password, 1, 0)');
    $stmt->bindValue(':ID', $id);
    $stmt->bindValue(':Name', $name);
    (!empty($_POST['Email'])) ? $stmt->bindValue(':Email', $_POST['Email']) : $stmt->bindValue(':Email', NULL);
    $stmt->bindValue(':Password', $password);
    $stmt->execute();

}
else if(!empty($_POST)) {
    echo "<p>Required fields not entered.</p>";
}

if(isset($newpass)) {
    echo '<p>User added!<br />
    ID: ' . $id . '<br />
    Password: ' . $newpass . '</p>
    <a href="newpet.php?id=' . $id . '">Add a pet for this client</a>';
} else { ?>
    <form action="newclient.php" method="post">
        <label for="FirstName">First Name: </label><input type="text" name="FirstName" id="FirstName"><br />
        <label for="LastName">Last Name: </label><input type="text" name="LastName" id="LastName"><br />
        <label for="Phone">Phone: </label><input type="text" name="Phone" id="Phone"><br />
        <label for="Address1">Address Line 1: </label><input type="text" name="Address1" id="Address1"><br />
        <label for="Address2">Address Line 2: </label><input type="text" name="Address2" id="Address2"><br />
        <label for="City">City: </label><input type="text" name="City" id="City"><br />
        <label for="State">State: </label><input type="text" name="State" id="State"><br />
        <label for="Zip">Zip Code: </label><input type="text" name="Zip" id="Zip"><br />
        <label for="Country">Country: </label><input type="text" name="Country" id="Country"><br />
        <label for="Email">Email: </label><input type="text" name="Email" id="Email"><br />
        <label for="SpouseName">Spouse's Name: </label><input type="text" name="SpouseName" id="SpouseName"><br />
        <label for="SpousePhone">Spouse's Phone Number: </label><input type="text" name="SpousePhone" id="SpousePhone"><br />
        <label for="Emergency">Emergency Contact Name: </label><input type="text" name="Emergency" id="Emergency"><br />
        <label for="EmergencyPhone">Emergency Contact Phone: </label><input type="text" name="EmergencyPhone" id="EmergencyPhone"><br />
        <label for="AuthorizedPickup">Authorized Pickup Name: </label><input type="text" name="AuthorizedPickup" id="AuthorizedPickup"><br />
        <label for="APPhone">Authorized Pickup Phone Number: </label><input type="text" name="APPhone" id="APPhone"><br />
        <label for="ReferredBy">Referred By: </label><input type="text" name="ReferredBy" id="ReferredBy"><br />
        <label for="DateCreated">Client Since: </label><input type="text" name="DateCreated" id="DateCreated"><br />
        <input type="submit" value="Submit">
    </form>
<?php } ?>
<script>
$(function() {
    $('#DateCreated').pikaday({
        format: 'MM/DD/YYYY',
        defaultDate: new Date(),
        setDefaultDate: true
    });
});
</script>
</body>
</html>