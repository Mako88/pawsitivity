<?php
include "include/header.php";

// Only allow Employees and Admins to create new pets.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Pet</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<body>

<?php
include "include/menu.php";

if(empty($_GET['id'])) {
    echo "<p>No ID given. Can't add pet.</p>";
}    
else if(!empty($_POST)) {
    if(!empty($_POST['Name']) && !empty($_POST['Breed']) && !empty($_GET['id']) && !empty($_POST['Time'])) {
        
        if(!is_array($_POST['Time'])) {
            echo "<p>Time data corrupted.</p>";
            goto finish;
        }
        
        (!empty($_POST['TwoPeople'])) ? $two = 1 : $two = 0;
        
        (!empty($_POST['DogOfMonth'])) ? $dom = strtotime($_POST['DogOfMonth']) : $dom = false;
        
        // Create SQL query based on fields recieved
        $stmt = $database->prepare('INSERT INTO Pets (Name, Breed, Age, Weight, Notes, Info, DogOfMonth, Time, TwoPeople, PreferredGroomer, OwnedBy) VALUES (:Name, :Breed, :Age, :Weight, :Notes, :Info, :DogOfMonth, :Time, :TwoPeople, :PreferredGroomer, :OwnedBy)');
        $stmt->bindValue(':Name', $_POST['Name']);
        $stmt->bindValue(':Breed', $_POST['Breed']);
        (!empty($_POST['Age'])) ? $stmt->bindValue(':Age', $_POST['Age']) : $stmt->bindValue(':Age', NULL);
        (!empty($_POST['Weight'])) ? $stmt->bindValue(':Weight', $_POST['Weight']) : $stmt->bindValue(':Weight', NULL);
        (!empty($_POST['Notes'])) ? $stmt->bindValue(':Notes', $_POST['Notes']) : $stmt->bindValue(':Notes', NULL);
        (!empty($_POST['Info'])) ? $stmt->bindValue(':Info', $_POST['Info']) : $stmt->bindValue(':Info', NULL);
        ($dom != false) ? $stmt->bindValue(':DogOfMonth', $dom) : $stmt->bindValue(':DogOfMonth', NULL);
        (is_array($_POST['Time'])) ? $stmt->bindValue(':Time', json_encode($_POST['Time'])) : $stmt->bindValue(':Time', NULL);
        $stmt->bindValue(':TwoPeople', $two);
        $stmt->bindValue(':PreferredGroomer', $_POST['PreferredGroomer']);
        $stmt->bindValue(':OwnedBy', $_GET['id']);
        $stmt->execute();
        $id = $database->lastInsertId();
        
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
                    echo "Upload failed!";
                    $stmt->bindValue(':Picture', NULL);
                }
            }
            else {
                $stmt->bindValue(':Picture', NULL);
            }
            
            $stmt->bindValue(':ID', $id);
            $stmt->execute();
        
            
        }
        $url = $http . $_SERVER['HTTP_HOST'] . "/viewpet.php?id=" . $id;
        echo "<script type='text/javascript'>document.location.href='{$url}';</script>";
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
    }
    else {
        echo "<p>Required fields not entered.</p>";
        goto finish;
    }
}
else {
    
    $stmt = $database->query("SELECT ID, Time FROM Breeds");
    $prices = $stmt->fetchAll();
?>

<script>
$(function() {
    var prices = <?php echo json_encode($prices); ?>;
    
    $('#Breed').change(function() {
        var id = $(this).val();
        
        for(var i = 0; i < prices.length; i++) {
            if(prices[i]['ID'] == id) {
                var time = JSON.parse(prices[i]['Time']);
                $("#BathBath").val(time['Bath']['BathTime']);
                $("#BathGroom").val(time['Bath']['GroomTime']);
                $("#GroomBath").val(time['Groom']['BathTime']);
                $("#GroomGroom").val(time['Groom']['GroomTime']);
            }
        }
    });
});
</script>
    
<form action="newpet.php?id=<?php echo $_GET['id']; ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
    <label for="Name">Name: </label><input type="text" name="Name" id="Name"><br />
    <label for="Breed">Breed: </label>
    <select name="Breed" id="Breed">
        <option value="NULL" selected disabled>Select One...</option>
        <optgroup label="Toy Breeds:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 0 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Designer Breeds:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 1 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Terriers:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 2 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Non-Sporting:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 3 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Sporting:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 4 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Hound Group:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 5 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Herding Group:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 6 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
        <optgroup label="Working Group:">
            <?php
                $stmt = $database->query("SELECT ID, Name FROM Breeds WHERE BreedGroup = 7 ORDER BY Name");
                foreach($stmt->fetchAll() as $breed) {
                    echo '<option value="' . $breed['ID'] . '">' . $breed['Name'] . '</option>';
                }
            ?>
        </optgroup>
    </select><br />
    <label for="Age">Age: </label><input type="text" name="Age" id="Age"><br />
    <label for="Weight">Weight: </label><input type="text" name="Weight" id="Weight"><br />
    <label for="Vaccines">Vaccines: </label><input type="file" name="Vaccines" id="Vaccines"><br />
    <label for="Notes">Notes: </label><textarea name="Notes" id="Notes"></textarea><br />
    <label for="Info">Info: </label><textarea name="Info" id="Info"></textarea><br />
    <label for="Picture">Picture: </label><input type="file" name="Picture" id="Picture"><br />
    <label for="DogOfMonth">Dog of the Month Date (MM/DD/YYYY): </label><input type="text" name="DogOfMonth" id="DogOfMonth"><br />
    <label>Time (In Minutes): </label><br />
        <label>Bath Only: </label><br />
            <label for="BathBath">Bathing Time: </label><input id="BathBath" type="text" name="Time[Bath][BathTime]" /><br />
            <label for="BathGroom">Grooming Time: </label><input id="BathGroom" type="text" name="Time[Bath][GroomTime]" /><br />
        <label>Bath and Groom: </label><br />
            <label for="GroomBath">Bathing Time: </label><input id="GroomBath" type="text" name="Time[Groom][BathTime]" /><br />
            <label for="GroomGroom">Grooming Time: </label><input id="GroomGroom" type="text" name="Time[Groom][GroomTime]" /><br />
    <label for="PreferredGroomer">Preferred Groomer: </label>
    <select name="PreferredGroomer">
        <option value="NULL">Any</option>
        <?php
            $stmt = $database->query("SELECT ID, Name FROM Users WHERE Access = 2");
            foreach($stmt->fetchAll() as $groomer) {
                echo '<option value="' . $groomer['ID'] . '">' . $groomer['Name'] . '</option>';
            }
        ?>
    </select><br />
    <label for="TwoPeople">Requires Two People: </label><input type="checkbox" name="TwoPeople" id="TwoPeople"><br />
    <input type="submit" value="Submit">
</form>

<?php
}
finish:
?>
    
</body>
</html>