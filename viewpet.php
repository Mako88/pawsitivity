<?php
include "include/header.php";

// Only allow Employees and Admins to view a pet.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Pet</title>
</head>
<body>

<?php
    
include "include/menu.php";
    
if(!empty($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $database->prepare("SELECT Vaccines, Picture FROM Pets WHERE ID = :ID");
    $stmt->bindValue(':ID', $id);
    $stmt->execute();
    $pet = $stmt->fetch();
    if(!empty($pet)) {
        if(!empty($_POST['Name']) && !empty($_POST['Breed']) && !empty($_GET['id'])) {

            (!empty($_POST['TwoPeople'])) ? $two = 1 : $two = 0;

            (!empty($_POST['DogOfMonth'])) ? $dom = strtotime($_POST['DogOfMonth']) : $dom = false;

            // Create SQL query based on fields recieved
            $stmt = $database->prepare('Update Pets Set Name=:Name, Breed=:Breed, Age=:Age, Weight=:Weight, Notes=:Notes, Info=:Info, DogOfMonth=:DogOfMonth, GroomTime=:GroomTime, BathTime=:BathTime, PreferredGroomer=:PreferredGroomer, TwoPeople=:TwoPeople WHERE ID=:ID');
            $stmt->bindValue(':Name', $_POST['Name']);
            $stmt->bindValue(':Breed', $_POST['Breed']);
            (!empty($_POST['Age'])) ? $stmt->bindValue(':Age', $_POST['Age']) : $stmt->bindValue(':Age', NULL);
            (!empty($_POST['Weight'])) ? $stmt->bindValue(':Weight', $_POST['Weight']) : $stmt->bindValue(':Weight', NULL);
            (!empty($_POST['Notes'])) ? $stmt->bindValue(':Notes', $_POST['Notes']) : $stmt->bindValue(':Notes', NULL);
            (!empty($_POST['Info'])) ? $stmt->bindValue(':Info', $_POST['Info']) : $stmt->bindValue(':Info', NULL);
            ($dom != false) ? $stmt->bindValue(':DogOfMonth', $dom) : $stmt->bindValue(':DogOfMonth', NULL);
            (!empty($_POST['GroomTime'])) ? $stmt->bindValue(':GroomTime', $_POST['GroomTime']) : $stmt->bindValue(':GroomTime', NULL);
            (!empty($_POST['BathTime'])) ? $stmt->bindValue(':BathTime', $_POST['BathTime']) : $stmt->bindValue(':BathTime', NULL);
            $stmt->bindValue(':PreferredGroomer', $_POST['PreferredGroomer']);
            $stmt->bindValue(':TwoPeople', $two);
            $stmt->bindValue(':ID', $_GET['id']);
            $stmt->execute();
            $id = $_GET['id'];

            if(!empty($_FILES['Vaccines']['name']) || !empty($_FILES['Picture']['name'])) {
                $stmt = $database->prepare('UPDATE Pets SET Vaccines=:Vaccines, Picture=:Picture WHERE ID = :ID');

                if(!empty($_FILES['Vaccines']['name'])) {
                    if(!file_exists('petdocs/' . $id)) { mkdir('petdocs/' . $id, 0777, 1); }
                    $uploaddir = 'petdocs/' . $id . '/';
                    $vaccines = $uploaddir . basename($_FILES['Vaccines']['name']);
                    if(move_uploaded_file($_FILES['Vaccines']['tmp_name'], $vaccines)) {
                        echo "Vaccines File Uploaded!<br />";
                        $stmt->bindValue(':Vaccines', $vaccines);
                    }
                    else {
                        echo "Upload failed!";
                        $stmt->bindValue(':Vaccines', $pet['Vaccines']);
                    }
                }
                else {
                    $stmt->bindValue(':Vaccines', $pet['Vaccines']);
                }

                if(!empty($_FILES['Picture']['name'])) {
                    if(!file_exists('petdocs/' . $id)) { mkdir('petdocs/' . $id, 0777, 1); }
                    $uploaddir = 'petdocs/' . $id . '/';
                    $picture = $uploaddir . basename($_FILES['Picture']['name']);
                    if(move_uploaded_file($_FILES['Picture']['tmp_name'], $picture)) {
                        echo "Picture Uploaded!<br />";
                        $stmt->bindValue(':Picture', $picture);
                    }
                    else {
                        echo "Upload failed!";
                        $stmt->bindValue(':Picture', $pet['Picture']);
                    }
                }
                else {
                    $stmt->bindValue(':Picture', $pet['Picture']);
                }

                $stmt->bindValue(':ID', $id);
                $stmt->execute();
            }
        }
        $stmt = $database->prepare("SELECT * FROM Pets WHERE ID = :ID");
        $stmt->bindValue(':ID', $id);
        $stmt->execute();
        $pet = $stmt->fetch();
        $stmt = $database->prepare("SELECT FirstName, LastName FROM Owners WHERE ID = :ID");
        $stmt->bindValue(':ID', $pet['OwnedBy']);
        $stmt->execute();
        $owner = $stmt->fetch();
        $stmt = $database->prepare("SELECT Name FROM Users WHERE ID = :ID");
        $stmt->bindValue(':ID', $pet['PreferredGroomer']);
        $stmt->execute();
        $groomername = $stmt->fetch();
        if(empty($_GET['e'])) {
            echo '<a href="viewpet.php?id=' . $pet['ID'] . '&e=1">Edit Pet</a>';
            echo (!empty($pet['Picture'])) ? '<img src="' . $pet['Picture'] . '" />' : '';
            echo '<table>';
                echo '<tr><td>ID</td><td>' . $pet['ID'] . '</td></tr>';
                echo '<tr><td>Name</td><td>' . $pet['Name'] . '</td></tr>';
                echo '<tr><td>Breed</td><td>' . $pet['Breed'] . '</td></tr>';
                echo '<tr><td>Age</td><td>' . $pet['Age'] . '</td></tr>';
                echo '<tr><td>Weight</td><td>' . $pet['Weight'] . '</td></tr>';
                echo '<tr><td>Vaccines</td><td>' . ((!empty($pet['Vaccines'])) ? '<a href="' . $pet['Vaccines'] . '">View</a>' : '') . '</td></tr>';
                echo '<tr><td>Notes</td><td>' . $pet['Notes'] . '</td></tr>';
                echo '<tr><td>Info</td><td>' . $pet['Info'] . '</td></tr>';
                echo '<tr><td>Dog of the Month Date</td><td>' . date('m/d/Y', $pet['DogOfMonth']) . '</td></tr>';
                echo '<tr><td>Time (In Minutes) to Groom</td><td>' . $pet['GroomTime'] . '</td></tr>';
                echo '<tr><td>Time (In Minutes) to Bathe</td><td>' . $pet['BathTime'] . '</td></tr>';
                echo '<tr><td>Preferred Groomer</td><td>' . ((!empty($groomername['Name'])) ? $groomername['Name'] : 'None') . '</td></tr>';
                echo '<tr><td>Requires Two People</td><td>' . (($pet['TwoPeople']) ? 'yes' : 'no') . '</td></tr>';
                echo '<tr><td>Owned By</td><td><a href="viewclient.php?id=' . $pet['OwnedBy'] . '">' . $owner['FirstName'] . ' ' . $owner['LastName'] . ' ' . '(' . $pet['OwnedBy'] . ')' . '</a></td></tr>';
            echo '</table>';
        }
        else {
            $stmt = $database->query("SELECT Name, ID FROM Users WHERE Access = 2");
            $groomers = $stmt->fetchAll();
            echo '<h2>Editing Pet ' . $pet['ID'] . '</h2>';
            echo '<form action="viewpet.php?id=' . $pet['ID'] . '" method="post" enctype="multipart/form-data">';
                echo '<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />';
                echo '<label for="Name">Name: </label><input type="text" name="Name" id="Name" value="' . $pet['Name'] . '"><br />';
                echo '<label for="Breed">Breed: </label><input type="text" name="Breed" id="Breed" value="' . $pet['Breed'] . '"><br />';
                echo '<label for="Age">Age: </label><input type="text" name="Age" id="Age" value="' . $pet['Age'] . '"><br />';
                echo '<label for="Weight">Weight: </label><input type="text" name="Weight" id="Weight" value="' . $pet['Weight'] . '"><br />';
                echo '<label for="Vaccines">Vaccines: </label><input type="file" name="Vaccines" id="Vaccines"><br />';
                echo '<label for="Notes">Notes: </label><textarea name="Notes" id="Notes">' . $pet['Notes'] . '</textarea><br />';
                echo '<label for="Info">Info: </label><textarea name="Info" id="Info">' . $pet['Info'] . '</textarea><br />';
                echo '<label for="Picture">Picture: </label><input type="file" name="Picture" id="Picture"><br />';
                echo '<label for="DogOfMonth">Dog of the Month Date (MM/DD/YYYY): </label><input type="text" name="DogOfMonth" id="DogOfMonth" value="' . date('m/d/Y', $pet['DogOfMonth']) . '"><br />';
                echo '<label for="GroomTime">Time (In Minutes) to Groom: </label><input type="text" name="GroomTime" id="GroomTime" value="' . $pet['GroomTime'] . '"><br />';
                echo '<label for="BathTime">Time (In Minutes) to Bathe: </label><input type="text" name="BathTime" id="BathTime" value="' . $pet['BathTime'] . '"><br />';
                echo '<label for="PreferredGroomer">Preferred Groomer: </label><select id="PreferredGroomer" name="PreferredGroomer">';
                    echo '<option value="NULL">None</option>';
                    foreach($groomers as $groomer) {
                        echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $pet['PreferredGroomer']) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                    }
                echo '</select><br />';
                echo '<label for="TwoPeople">Requires Two People: </label><input type="checkbox" name="TwoPeople" id="TwoPeople" value="' . $pet['TwoPeople'] . '"><br />';
                echo '<input type="submit" value="Submit">';
            echo '</form>';
        }
    }
    else {
        echo "<p>I'm sorry, that ID is unrecognized.</p>";
    }
}
else {
    $stmt = $database->query("SELECT * FROM Pets");
    $pets = $stmt->fetchAll();
    if(!empty($pets)) {
        echo '<table><th><td>ID</td><td>Name</td><td>Owned By</td></th>';
        foreach($pets as $pet) {
            $stmt = $database->query("SELECT FirstName, LastName FROM Owners WHERE ID = " . $pet['OwnedBy']);
            $owner = $stmt->fetch();
            echo '<tr style="cursor: pointer;" onclick="window.document.location=\'viewpet.php?id=' . $pet['ID'] . '\'"><td>' . $pet['ID'] . '</td><td>' . $pet['Name'] . '</td><td>' . $owner['FirstName'] . ' ' . $owner['LastName'] . ' (' . $pet['OwnedBy'] . ')' . '</td></tr>';
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