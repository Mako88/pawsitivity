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
<title>View Breed</title>
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
            $stmt = $database->prepare('Update Pets Set Name=:Name, Breed=:Breed, Age=:Age, Weight=:Weight, Notes=:Notes, Info=:Info, DogOfMonth=:DogOfMonth, Time=:Time, PreferredGroomer=:PreferredGroomer, TwoPeople=:TwoPeople WHERE ID=:ID');
            $stmt->bindValue(':Name', $_POST['Name']);
            $stmt->bindValue(':Breed', $_POST['Breed']);
            (!empty($_POST['Age'])) ? $stmt->bindValue(':Age', $_POST['Age']) : $stmt->bindValue(':Age', NULL);
            (!empty($_POST['Weight'])) ? $stmt->bindValue(':Weight', $_POST['Weight']) : $stmt->bindValue(':Weight', NULL);
            (!empty($_POST['Notes'])) ? $stmt->bindValue(':Notes', $_POST['Notes']) : $stmt->bindValue(':Notes', NULL);
            (!empty($_POST['Info'])) ? $stmt->bindValue(':Info', $_POST['Info']) : $stmt->bindValue(':Info', NULL);
            ($dom != false) ? $stmt->bindValue(':DogOfMonth', $dom) : $stmt->bindValue(':DogOfMonth', NULL);
            (is_array($_POST['Time'])) ? $stmt->bindValue(':Time', json_encode($_POST['Time'])) : $stmt->bindValue(':Time', NULL);
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
        $stmt = $database->prepare("SELECT Name FROM Breeds WHERE ID = :ID");
        $stmt->bindValue(':ID', $pet['Breed']);
        $stmt->execute();
        $breed = $stmt->fetch();
        $pet['Time'] = json_decode($pet['Time'], true);
        $stmt = $database->prepare("SELECT * FROM Scheduling WHERE PetID = :ID");
        $stmt->bindValue(':ID', $id);
        $stmt->execute();
        $events = $stmt->fetchAll();
        $stmt = $database->query("SELECT Timezone FROM Globals");
        $temp = $stmt->fetch();
        $timezone = $temp['Timezone'];
        $stmt = $database->prepare("SELECT FirstName, LastName FROM Owners WHERE ID = :ID");
        $stmt->bindValue(':ID', $pet['OwnedBy']);
        $stmt->execute();
        $owner = $stmt->fetch();
        $stmt = $database->prepare("SELECT Name FROM Users WHERE ID = :ID");
        $stmt->bindValue(':ID', $pet['PreferredGroomer']);
        $stmt->execute();
        $groomername = $stmt->fetch();
        
        if(!empty($_GET['delete'])) {
            $stmt = $database->prepare("DELETE FROM Scheduling WHERE ID = :ID");
            $stmt->bindValue(':ID', $_GET['delete']);
            $stmt->execute();
        }
        
        if(empty($_GET['e'])) { ?>
            <a href="viewpet.php?id=<?php echo $pet['ID']; ?>&e=1">Edit Pet</a>
            <?php echo (!empty($pet['Picture'])) ? '<img src="' . $pet['Picture'] . '" />' : ''; ?>
            <table>
                <tr><td>ID</td><td><?php echo $pet['ID']; ?></td></tr>
                <tr><td>Name</td><td><?php echo $pet['Name']; ?></td></tr>
                <tr><td>Breed</td><td><?php echo $breed['Name']; ?></td></tr>
                <tr><td>Age</td><td><?php echo $pet['Age']; ?></td></tr>
                <tr><td>Weight</td><td><?php echo $pet['Weight'] ?></td></tr>
                <tr><td>Vaccines</td><td><?php echo ((!empty($pet['Vaccines'])) ? '<a href="' . $pet['Vaccines'] . '">View</a>' : ''); ?></td></tr>
                <tr><td>Notes</td><td><?php echo $pet['Notes']; ?></td></tr>
                <tr><td>Info</td><td><?php echo $pet['Info']; ?></td></tr>
                <tr><td>Dog of the Month Date</td><td><?php echo date('m/d/Y', $pet['DogOfMonth']); ?></td></tr>
                <tr>
                    <td>Time (In Minutes):</td>
                    <td>
                        <strong>Bath Only:</strong><br />
                        Bath Time: <?php echo $pet['Time']['Bath']['BathTime']; ?><br />
                        Groom Time: <?php echo $pet['Time']['Bath']['GroomTime']; ?><br />
                        <strong>Bath &amp; Groom:</strong><br />
                        Bath Time: <?php echo $pet['Time']['Groom']['BathTime']; ?><br />
                        Groom Time: <?php echo $pet['Time']['Groom']['GroomTime']; ?><br />
                    </td>
                </tr>
                <tr><td>Preferred Groomer</td><td><?php echo ((!empty($groomername['Name'])) ? $groomername['Name'] : 'None'); ?></td></tr>
                <tr><td>Requires Two People</td><td><?php echo (($pet['TwoPeople'] == 1) ? 'yes' : 'no'); ?></td></tr>
                <tr><td>Owned By</td><td><a href="viewclient.php?id=<?php echo $pet['OwnedBy'] ?>"><?php echo $owner['FirstName'] . ' ' . $owner['LastName'] . ' ' . '(' . $pet['OwnedBy'] . ')'; ?></a></td></tr>
            </table>
            <h2>Scheduling:</h2>
            <?php
                if(empty($events)) {
                    echo "<p>This pet doesn't have anything scheduled. :/</p>";
                }
                else {
                    $futureevents = array();
                    $pastevents = array();
                    foreach($events as $event) {
                        if($event['StartTime'] > time()) {
                            array_push($futureevents, $event);
                        }
                        else {
                            array_push($pastevents, $event);
                        }
                    }
                    if(!empty($futureevents)) {
                        echo "<h3>Future Visits:</h3>";
                        echo "<table>";
                        foreach($futureevents as $event) {
                            $date = new DateTime("@" . ($event['StartTime']));
                            $date->setTimezone(new DateTimeZone($timezone));
                            echo '<tr><td>' . $date->format("m/d/Y @ h:i A") . ' <a href="viewpet.php?id=' . $_GET['id'] . '&delete=' . $event['ID'] . '" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a></td></tr>';
                        }
                        echo "</table>";
                    }
                    if(!empty($pastevents)) {
                        echo "<h3>Past Visits:</h3>";
                        echo "<table>";
                        foreach($pastevents as $event) {
                            $date = new DateTime("@" . ($event['StartTime']));
                            $date->setTimezone(new DateTimeZone($timezone));
                            echo '<tr><td>' . $date->format("m/d/Y @ h:i A") . ' <a href="viewpet.php?id=' . $_GET['id'] . '&delete=' . $event['ID'] . '" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a></td></tr>';
                        }
                        echo "</table>";
                    }
                }
        }
        else {
            $stmt = $database->query("SELECT Name, ID FROM Users WHERE Access = 2");
            $groomers = $stmt->fetchAll(); ?>
            <h2>Editing Pet <?php echo $pet['ID']; ?></h2>
            <form action="viewpet.php?id=<?php echo $pet['ID']; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                <label for="Name">Name: </label><input type="text" name="Name" id="Name" value="<?php echo $pet['Name']; ?>"><br />
                <select name="Breed" id="Breed">
                    <optgroup label="Toy Breeds:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 0 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? 'selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Designer Breeds:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 1 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Terriers:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 2 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Non-Sporting:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 3 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Sporting:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 4 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Hound Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 5 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Herding Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 6 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Working Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 7 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['ID']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                </select><br />
                <label for="Age">Age: </label><input type="text" name="Age" id="Age" value="<?php echo $pet['Age']; ?>"><br />
                <label for="Weight">Weight: </label><input type="text" name="Weight" id="Weight" value="<?php echo $pet['Weight']; ?>"><br />
                <label for="Vaccines">Vaccines: </label><input type="file" name="Vaccines" id="Vaccines"><br />
                <label for="Notes">Notes: </label><textarea name="Notes" id="Notes"><?php echo $pet['Notes']; ?></textarea><br />
                <label for="Info">Info: </label><textarea name="Info" id="Info"><?php echo $pet['Info']; ?></textarea><br />
                <label for="Picture">Picture: </label><input type="file" name="Picture" id="Picture"><br />
                <label for="DogOfMonth">Dog of the Month Date (MM/DD/YYYY): </label><input type="text" name="DogOfMonth" id="DogOfMonth" value="<?php echo date('m/d/Y', $pet['DogOfMonth']); ?>"><br />
                <label>Time (In Minutes): </label><br />
                <label>Bath Only: </label><br />
                    <label for="BathBath">Bathing Time: </label><input id="BathBath" type="text" name="Time[Bath][BathTime]" value="<?php echo $pet['Time']['Bath']['BathTime'] ?>" /><br />
                    <label for="BathGroom">Grooming Time: </label><input id="BathGroom" type="text" name="Time[Bath][GroomTime]" value="<?php echo $pet['Time']['Bath']['GroomTime'] ?>" /><br />
                <label>Bath and Groom: </label><br />
                    <label for="GroomBath">Bathing Time: </label><input id="GroomBath" type="text" name="Time[Groom][BathTime]" value="<?php echo $pet['Time']['Groom']['BathTime'] ?>" /><br />
                    <label for="GroomGroom">Grooming Time: </label><input id="GroomGroom" type="text" name="Time[Groom][GroomTime]" value="<?php echo $pet['Time']['Groom']['GroomTime'] ?>" /><br />
                <label for="PreferredGroomer">Preferred Groomer: </label>
                <select id="PreferredGroomer" name="PreferredGroomer">
                    <option value="NULL">Any</option>
                    <?php 
                        foreach($groomers as $groomer) {
                            echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $pet['PreferredGroomer']) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                        }
                    ?>
                </select><br />
                <label for="TwoPeople">Requires Two People: </label><input type="checkbox" name="TwoPeople" id="TwoPeople" value="1" <?php echo ($pet['TwoPeople'] == 1 ? 'checked' : ''); ?>><br />
                <input type="submit" value="Submit">
            </form>
        <?php }
    }
    else {
        echo "<p>I'm sorry, that ID is unrecognized.</p>";
    }
}
else {
    $stmt = $database->query("SELECT * FROM Breeds ORDER BY Group, Name");
    $breeds = $stmt->fetchAll();
    if(!empty($breeds)) {
        echo '<table><th><td>Name</td><td>Group</td><td>Size</td><td>Bath-Only Times</td><td>Bath &amp; Groom Times</td><td>Base Bath Price</td><td>Base Groom Price</td></th>';
        foreach($breeds as $breed) {
            echo '<tr><td>' . $breed['Name'] . '</td>';
            echo '<td>';
            switch($breed['BreedGroup']) {
                case 0:
                    echo 'Toy Breeds';
                    break;
                case 1:
                    echo 'Designer Breeds';
                    break;
                case 2:
                    echo 'Terriers';
                    break;
                case 3:
                    echo 'Non-Sporting';
                    break;
                case 4:
                    echo 'Sporting';
                    break;
                case 5:
                    echo 'Hound Group';
                    break;
                case 6:
                    echo 'Herding Group';
                    break;
                case 7:
                    echo 'Working Group';
                    break;
            }
            echo '</td>';
            echo '<td>';
            switch($breed['Size']) {
                case 'P':
                    echo 'Petite';
                    break;
                case 'S':
                    echo 'Small';
                    break;
                case 'M':
                    echo 'Medium';
                    break;
                case 'L':
                    echo 'Large';
                    break;
                case 'XL':
                    echo 'Extra Large';
                    break;
            }
            echo '</td>';
            $time = json_decode($breeds['Time'], true);
            echo '<td><strong>Bath Time: </strong>' . $time['Bath']['BathTime'] . '<br />';
            echo '<strong>Groom Time: </strong>' . $time['Bath']['GroomTime'] . '</td>';
            echo '<td><strong>Bath Time: </strong>' . $time['Groom']['BathTime'] . '<br />';
            echo '<strong>Groom Time: </strong>' . $time['Groom']['GroomTime'] . '</td>';
            echo '<td>' . $breed['BathPrice'] . '</td>';
            echo '<td>' . $breed['GroomPrice'] . '</td>';
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