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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/moment-timezone.min.js"></script>
<script src="js/pikaday.js"></script>
<script src="js/pikaday.jquery.js"></script>
<link rel="stylesheet" type="text/css" href="css/pikaday.css" />
</head>
<body>

<?php
    
include "include/menu.php";
    
if(!empty($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $database->prepare("SELECT Vaccines, Picture, ReleaseForm FROM Pets WHERE ID = :ID");
    $stmt->bindValue(':ID', $id);
    $stmt->execute();
    $pet = $stmt->fetch();
    if(!empty($pet)) {
        if(!empty($_POST['Name']) && !empty($_POST['Breed']) && !empty($_GET['id'])) {

            (!empty($_POST['TwoPeople'])) ? $two = 1 : $two = 0;

            (!empty($_POST['DogOfMonth'])) ? $dom = strtotime($_POST['DogOfMonth']) : $dom = false;
            
            $age = NULL;
        
            // Convert age to a year
            if(!empty($_POST['Age'])) {
                $age = intval(date("Y")) - $_POST['Age'];
            }
            
            // Create SQL query based on fields recieved
            $stmt = $database->prepare('Update Pets Set Name=:Name, Breed=:Breed, Age=:Age, Weight=:Weight, Vaccines2=:Vaccines2, Notes=:Notes, Info=:Info, DogOfMonth=:DogOfMonth, Time=:Time, PreferredGroomer=:PreferredGroomer, TwoPeople=:TwoPeople WHERE ID=:ID');
            $stmt->bindValue(':Name', $_POST['Name']);
            $stmt->bindValue(':Breed', $_POST['Breed']);
            $stmt->bindValue(':Age', $age);
            (!empty($_POST['Weight'])) ? $stmt->bindValue(':Weight', $_POST['Weight']) : $stmt->bindValue(':Weight', NULL);
            (is_array($_POST['Vaccines2'])) ? $stmt->bindValue(':Vaccines2', json_encode($_POST['Vaccines2'])) : $stmt->bindValue(':Vaccines2', NULL);
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
                $stmt = $database->prepare('UPDATE Pets SET Vaccines=:Vaccines, Picture=:Picture, ReleaseForm=:Release WHERE ID = :ID');

                if(!empty($_FILES['Vaccines']['name'])) {
                    if(!file_exists('petdocs/' . $id)) { mkdir('petdocs/' . $id, 0777, 1); }
                    $uploaddir = 'petdocs/' . $id . '/';
                    $vaccines = $uploaddir . basename($_FILES['Vaccines']['name']);
                    if(move_uploaded_file($_FILES['Vaccines']['tmp_name'], $vaccines)) {
                        echo "Vaccines File Uploaded!<br />";
                        $stmt->bindValue(':Vaccines', $vaccines);
                    }
                    else {
                        echo "Vaccine File Upload failed!";
                        $stmt->bindValue(':Vaccines', NULL);
                    }
                }
                else {
                    $stmt->bindValue(':Vaccines', NULL);
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
                        echo "Picture Upload failed!";
                        $stmt->bindValue(':Picture', NULL);
                    }
                }
                else {
                    $stmt->bindValue(':Picture', NULL);
                }

                if(!empty($_FILES['Release']['name'])) {
                    if(!file_exists('petdocs/' . $id)) { mkdir('petdocs/' . $id, 0777, 1); }
                    $uploaddir = 'petdocs/' . $id . '/';
                    $release = $uploaddir . basename($_FILES['Release']['name']);
                    if(move_uploaded_file($_FILES['Release']['tmp_name'], $release)) {
                        echo "Release Form Uploaded!<br />";
                        $stmt->bindValue(':Release', $release);
                    }
                    else {
                        echo "Relase Form Upload failed!";
                        $stmt->bindValue(':Release', NULL);
                    }
                }
                else {
                    $stmt->bindValue(':Release', NULL);
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
        $pet['Vaccines2'] = json_decode($pet['Vaccines2'], true);
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
        
        if(!empty($_GET['delschedule'])) {
            $stmt = $database->prepare("DELETE FROM Scheduling WHERE ID = :ID");
            $stmt->bindValue(':ID', $_GET['delschedule']);
            $stmt->execute();
        }
        
        $stmt = $database->prepare("SELECT * FROM Scheduling WHERE PetID = :ID");
        $stmt->bindValue(':ID', $id);
        $stmt->execute();
        $events = $stmt->fetchAll();
        
        if(!empty($_GET['delpet'])) {
            $stmt = $database->prepare("DELETE FROM Pets WHERE ID = :ID");
            $stmt->bindValue(':ID', $_GET['delpet']);
            $stmt->execute();
            $stmt = $database->prepare("DELETE FROM Scheduling WHERE PetID = :ID");
            $stmt->bindValue(':ID', $_GET['delpet']);
            $stmt->execute();
            echo '<p>Pet deleted!</p>';
            goto finish;
        }
        
        if(empty($_GET['e'])) { ?>
            <a href="schedule.php?pet=<?php echo $pet['ID']; ?>">Schedule Pet</a>
            <a href="viewpet.php?id=<?php echo $pet['ID']; ?>&e=1">Edit Pet</a>
            <a href="viewpet.php?id=<?php echo $pet['ID']; ?>&delpet=<?php echo $pet['ID']; ?>" onclick="return confirm('Are you sure you want to delete this pet?')">Delete Pet</a><br />
            <?php echo (!empty($pet['Picture'])) ? '<img src="' . $pet['Picture'] . '" />' : ''; ?>
            <table>
                <tr><td>ID:</td><td><?php echo $pet['ID']; ?></td></tr>
                <tr><td>Name:</td><td><?php echo $pet['Name']; ?></td></tr>
                <tr><td>Breed:</td><td><a href="viewbreed.php?id=<?php echo $pet['Breed']; ?>"><?php echo $breed['Name']; ?></a></td></tr>
                <tr><td>Age:</td><td><?php echo date("Y") - intval($pet['Age']); ?></td></tr>
                <tr><td>Weight:</td><td><?php echo $pet['Weight'] ?></td></tr>
                <tr><td>Vaccines:</td><td><?php echo ((!empty($pet['Vaccines'])) ? '<a href="' . $pet['Vaccines'] . '">View</a>' : 'None'); ?></td></tr>
                <tr><td>Vaccine Dates:</td><td><?php echo "Rabies: " . $pet['Vaccines2']['Rabies'] . "<br />" . "Distemper: " . $pet['Vaccines2']['Distemper'] . "<br />" . "Parvo: " . $pet['Vaccines2']['Parvo']; ?></td></tr>                
                <tr><td>Release Form:</td><td><?php echo ((!empty($pet['Release'])) ? '<a href="' . $pet['Release'] . '">View</a>' : 'None'); ?></td></tr>
                <tr><td>Notes:</td><td><?php echo $pet['Notes']; ?></td></tr>
                <tr><td>Warnings:</td><td><?php echo $pet['Info']; ?></td></tr>
                <tr><td>Dog of the Month Date:</td><td><?php echo date('m/d/Y', $pet['DogOfMonth']); ?></td></tr>
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
                <tr><td>Preferred Groomer:</td><td><?php echo ((!empty($groomername['Name'])) ? $groomername['Name'] : 'Any'); ?></td></tr>
                <tr><td>Requires Two People:</td><td><?php echo (($pet['TwoPeople'] == 1) ? 'yes' : 'no'); ?></td></tr>
                <tr><td>Owned By:</td><td><a href="viewclient.php?id=<?php echo $pet['OwnedBy'] ?>"><?php echo $owner['FirstName'] . ' ' . $owner['LastName'] . ' ' . '(' . $pet['OwnedBy'] . ')'; ?></a></td></tr>
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
                            echo '<tr><td>' . $date->format("m/d/Y @ h:i A") . ' <a href="viewpet.php?id=' . $_GET['id'] . '&delschedule=' . $event['ID'] . '" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a></td></tr>';
                        }
                        echo "</table>";
                    }
                    if(!empty($pastevents)) {
                        echo "<h3>Past Visits:</h3>";
                        echo "<table>";
                        foreach($pastevents as $event) {
                            $date = new DateTime("@" . ($event['StartTime']));
                            $date->setTimezone(new DateTimeZone($timezone));
                            echo '<tr><td>' . $date->format("m/d/Y @ h:i A") . ' <a href="viewpet.php?id=' . $_GET['id'] . '&delschedule=' . $event['ID'] . '" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a></td></tr>';
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
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? 'selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Designer Breeds:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 1 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Terriers:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 2 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Non-Sporting:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 3 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Sporting:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 4 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Hound Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 5 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Herding Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 6 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                    <optgroup label="Working Group:">
                        <?php
                            $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 7 ORDER BY Name");
                            foreach($stmt->fetchAll() as $breed) {
                                echo '<option value="' . $breed['ID'] . '"' . (($breed['ID'] == $pet['Breed']) ? ' selected' : '') . '>' . $breed['Name'] . '</option>';
                            }
                        ?>
                    </optgroup>
                </select><br />
                <label for="Age">Age: </label><input type="text" name="Age" id="Age" value="<?php echo date("Y") - intval($pet['Age']); ?>"><br />
                <label for="Weight">Weight: </label><input type="text" name="Weight" id="Weight" value="<?php echo $pet['Weight']; ?>"><br />
                <label for="Vaccines">Vaccines: </label><input type="file" name="Vaccines" id="Vaccines"><br />
                <label>Vaccine Dates: </label><br />
                    <label for="Rabies">Rabies: </label><input id="Rabies" type="text" name="Vaccines2[Rabies]" value="<?php echo $pet['Vaccines2']['Rabies']; ?>" /><br />
                    <label for="Distemper">Distemper: </label><input id="Distemper" type="text" name="Vaccines2[Distemper]" value="<?php echo $pet['Vaccines2']['Distemper']; ?>" /><br />
                    <label for="Parvo">Parvo: </label><input id="Parvo" type="text" name="Vaccines2[Parvo]" value="<?php echo $pet['Vaccines2']['Parvo']; ?>" /><br />
                <label for="Release">Release Form: </label><input type="file" name="Release" id="Release"><br />
                <label for="Notes">Notes: </label><textarea name="Notes" id="Notes"><?php echo $pet['Notes']; ?></textarea><br />
                <label for="Info">Warnings: </label><textarea name="Info" id="Info"><?php echo $pet['Info']; ?></textarea><br />
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
    $stmt = $database->query("SELECT * FROM Pets ORDER BY Name");
    $pets = $stmt->fetchAll();
    if(!empty($pets)) {
        echo '<table><tr><th>ID</th><th>Name</th><th>Owned By</th></tr>';
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
finish:
?>
<script>
$(function() {
    $('#Rabies').pikaday({
        format: 'MM/DD/YYYY'
    });
    $('#Distemper').pikaday({
        format: 'MM/DD/YYYY'
    });
    $('#Parvo').pikaday({
        format: 'MM/DD/YYYY'
    });
});
</script>
</body>
</html>