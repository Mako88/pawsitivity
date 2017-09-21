<?php
include "include/header.php";

// Only allow Employees and Admins to view a client.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Client</title>
</head>
<body>
<?php

    include "include/menu.php";
    
if(!empty($_GET['id'])) {
    
    if(!empty($_POST['FirstName']) && !empty($_POST['LastName']) && !empty($_POST['Phone'])) {
        $stmt = $database->prepare('UPDATE Owners SET FirstName=:FirstName, LastName=:LastName, Phone=:Phone, Address1=:Address1, Address2=:Address2, City=:City, State=:State, Zip=:Zip, Country=:Country, Email=:Email, SpouseName=:SpouseName, SpousePhone=:SpousePhone, Emergency=:Emergency, EmergencyPhone=:EmergencyPhone, AuthorizedPickup=:AuthorizedPickup, APPhone=:APPhone, ReferredBy=:ReferredBy WHERE ID=:ID');
        $stmt->bindValue(':ID', $_GET['id']);
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
        $stmt->execute();
    }
    
    $stmt = $database->prepare("SELECT * FROM Owners WHERE ID = :ID");
    $stmt->bindValue(':ID', $_GET['id']);
    $stmt->execute();
    $client = $stmt->fetch();
    $stmt = $database->prepare("SELECT * FROM Pets WHERE OwnedBy = :ID");
    $stmt->bindValue(':ID', $client['ID']);
    $stmt->execute();
    $pets = $stmt->fetchAll();
    if(!empty($client)) {
        if(empty($_GET['e'])) {
            echo '<a href="viewclient.php?id=' . $client['ID'] . '&e=1">Edit Client</a><br />';
            echo '<a href="newpass.php?id=' . $client['ID'] . '">Reset Client Password</a><br />';
            echo '<a href="schedule.php?id=' . $client['ID'] . '">Schedule Client</a>';
            echo '<table>';
            echo '<tr><td>ID</td><td>' . $client['ID'] . '</td></tr>';
            echo '<tr><td>First Name</td><td>' . $client['FirstName'] . '</td></tr>';
            echo '<tr><td>Last Name</td><td>' . $client['LastName'] . '</td></tr>';
            echo '<tr><td>Phone Number</td><td>' . $client['Phone'] . '</td></tr>';
            echo '<tr><td>Address Line 1</td><td>' . $client['Address1'] . '</td></tr>';
            echo '<tr><td>Address Line 2</td><td>' . $client['Address2'] . '</td></tr>';
            echo '<tr><td>City</td><td>' . $client['City'] . '</td></tr>';
            echo '<tr><td>State</td><td>' . $client['State'] . '</td></tr>';
            echo '<tr><td>Zip Code</td><td>' . $client['Zip'] . '</td></tr>';
            echo '<tr><td>Country</td><td>' . $client['Country'] . '</td></tr>';
            echo '<tr><td>Email</td><td>' . $client['Email'] . '</td></tr>';
            echo '<tr><td>Spouse\'s Name</td><td>' . $client['SpouseName'] . '</td></tr>';
            echo '<tr><td>Spouse\'s Phone Number</td><td>' . $client['SpousePhone'] . '</td></tr>';
            echo '<tr><td>Emergency Contact Name</td><td>' . $client['Emergency'] . '</td></tr>';
            echo '<tr><td>Emergency Contact Phone Number</td><td>' . $client['EmergencyPhone'] . '</td></tr>';
            echo '<tr><td>Authorized Pickup</td><td>' . $client['AuthorizedPickup'] . '</td></tr>';
            echo '<tr><td>Authorized Pickup Phone Number</td><td>' . $client['APPhone'] . '</td></tr>';
            echo '<tr><td>Referred By</td><td>' . $client['ReferredBy'] . '</td></tr>';
            echo '</table>';
            echo '<h2>Pets:</h2>';
            if(!empty($pets)) {
                echo '<table><th><td>ID</td><td>Name</td><td>Breed</td></th>';
                foreach($pets as $pet) {
                    $stmt = $database->prepare("SELECT Name FROM Breeds WHERE ID = :ID");
                    $stmt->bindValue(':ID', $pet['Breed']);
                    $stmt->execute();
                    $breed = $stmt->fetch();
                    echo '<tr style="cursor: pointer;" onclick="window.document.location=\'viewpet.php?id=' . $pet['ID'] . '\'"><td>' . $pet['ID'] . '</td><td>' . $pet['Name'] . '</td><td>' . $breed['Name'] . '</td></tr>';
                }
                echo '</table>';
            }
            else {
                echo "<p>No pets added yet :/</p>";
            }
            echo '<a href="newpet.php?id=' . $client['ID'] . '">Add a pet</a>';
        }
        else {
            echo '<h2>Editing Client ' . $client['ID'] . '</h2>';
            echo '<form action="viewclient.php?id=' . $client['ID'] . '" method="post">';
            echo '<label for="FirstName">First Name: </label><input type="text" name="FirstName" id="FirstName" value="' . $client['FirstName'] . '"><br />';
            echo '<label for="LastName">Last Name: </label><input type="text" name="LastName" id="LastName" value="' . $client['LastName'] . '"><br />';
            echo '<label for="Phone">Phone: </label><input type="text" name="Phone" id="Phone" value="' . $client['Phone'] . '"><br />';
            echo '<label for="Address1">Address Line 1: </label><input type="text" name="Address1" id="Address1" value="' . $client['Address1'] . '"><br />';
            echo '<label for="Address2">Address Line 2: </label><input type="text" name="Address2" id="Address2" value="' . $client['Address2'] . '"><br />';
            echo '<label for="City">City: </label><input type="text" name="City" id="City" value="' . $client['City'] . '"><br />';
            echo '<label for="State">State: </label><input type="text" name="State" id="State" value="' . $client['State'] . '"><br />';
            echo '<label for="Zip">Zip Code: </label><input type="text" name="Zip" id="Zip" value="' . $client['Zip'] . '"><br />';
            echo '<label for="Country">Country: </label><input type="text" name="Country" id="Country" value="' . $client['Country'] . '"><br />';
            echo '<label for="Email">Email: </label><input type="text" name="Email" id="Email" value="' . $client['Email'] . '"><br />';
            echo '<label for="SpouseName">Spouse\'s Name: </label><input type="text" name="SpouseName" id="SpouseName" value="' . $client['SpouseName'] . '"><br />';
            echo '<label for="SpousePhone">Spouse\'s Phone Number: </label><input type="text" name="SpousePhone" id="SpousePhone" value="' . $client['SpousePhone'] . '"><br />';
            echo '<label for="Emergency">Emergency Contact Name: </label><input type="text" name="Emergency" id="Emergency" value="' . $client['Emergency'] . '"><br />';
            echo '<label for="EmergencyPhone">Emergency Contact Phone: </label><input type="text" name="EmergencyPhone" id="EmergencyPhone" value="' . $client['EmergencyPhone'] . '"><br />';
            echo '<label for="AuthorizedPickup">Authorized Pickup Name: </label><input type="text" name="AuthorizedPickup" id="AuthorizedPickup" value="' . $client['AuthorizedPickup'] . '"><br />';
            echo '<label for="APPhone">Authorized Pickup Phone Number: </label><input type="text" name="APPhone" id="APPhone" value="' . $client['APPhone'] . '"><br />';
            echo '<label for="ReferredBy">Referred By: </label><input type="text" name="ReferredBy" id="ReferredBy" value="' . $client['ReferredBy'] . '"><br />';
            echo '<input type="submit" value="Submit">';
            echo '</form>';
        }
    }
    else {
        echo "<p>I'm sorry, that ID is unrecognized.</p>";
    }
}
else {
    $stmt = $database->query("SELECT * FROM Owners");
    $clients = $stmt->fetchAll();
    if(!empty($clients)) {
        echo '<table><tr><th>ID</th><th>First Name</th><th>Last Name</th></tr>';
        foreach($clients as $client) {
            echo '<tr style="cursor: pointer;" onclick="window.document.location=\'viewclient.php?id=' . $client['ID'] . '\'"><td>' . $client['ID'] . '</td><td>' . $client['FirstName'] . '</td><td>' . $client['LastName'] . '</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo "<p>I'm Sorry, no results! :/</p>";
    }
}

?>
    
</body>
</html>