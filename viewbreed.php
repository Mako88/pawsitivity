<?php
include "include/header.php";

// Only allow Employees and Admins to view a breed.
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
    $stmt = $database->prepare("SELECT * FROM Breeds WHERE ID = :ID");
    $stmt->bindValue(':ID', $id);
    $stmt->execute();
    $breed = $stmt->fetch();
    if(!empty($breed)) {
        if(!empty($_POST['Name']) && isset($_POST['Group']) && !empty($_POST['Size']) && isset($_POST['GroomPrice']) && isset($_POST['BathPrice']) && !empty($_GET['id'])) {

            if(is_array($_POST['Time'])) {
                $stmt = $database->prepare('UPDATE Breeds Set Name=:Name, BreedGroup=:Group, Size=:Size, Time=:Time, GroomPrice=:GroomPrice, BathPrice=:BathPrice');
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
        
        if(!empty($_GET['delete'])) {
            $stmt = $database->prepare("DELETE FROM Breeds WHERE ID = :ID");
            $stmt->bindValue(':ID', $_GET['delete']);
            $stmt->execute();
            echo '<p>Breed deleted!</p>';
            goto finish;
        }
        
        if(empty($_GET['e'])) { 
        
            switch($breed['BreedGroup']) {
                case 0:
                    $group = 'Toy Breeds';
                    break;
                case 1:
                    $group = 'Designer Breeds';
                    break;
                case 2:
                    $group = 'Terriers';
                    break;
                case 3:
                    $group = 'Non-Sporting';
                    break;
                case 4:
                    $group = 'Sporting';
                    break;
                case 5:
                    $group = 'Hound Group';
                    break;
                case 6:
                    $group = 'Herding Group';
                    break;
                case 7:
                    $group = 'Working Group';
                    break;
            }
            echo '</td>';
            echo '<td>';
            switch($breed['Size']) {
                case 'P':
                    $size = 'Petite';
                    break;
                case 'S':
                    $size = 'Small';
                    break;
                case 'M':
                    $size = 'Medium';
                    break;
                case 'L':
                    $size = 'Large';
                    break;
                case 'XL':
                    $size = 'Extra Large';
                    break;
            }
    
            $breed['Time'] = json_decode($breed['Time'], true); ?>
    
            <a href="viewbreed.php?id=<?php echo $breed['ID']; ?>&e=1">Edit Breed</a><br />
            <a href="viewbreed.php?id=<?php echo $breed['ID']; ?>&delete=<?php echo $breed['ID']; ?>" onclick="return confirm('Are you sure you want to delete this breed?')">Delete Breed</a><br />
            <table>
                <tr><td>ID: </td><td><?php echo $breed['ID']; ?></td></tr>
                <tr><td>Name: </td><td><?php echo $breed['Name']; ?></td></tr>
                <tr><td>Group: </td><td><?php echo $group; ?></td></tr>
                <tr><td>Size: </td><td><?php echo $size; ?></td></tr>
                <tr>
                    <td>Time (In Minutes): </td>
                    <td>
                        <strong>Bath Only:</strong><br />
                        Bath Time: <?php echo $breed['Time']['Bath']['BathTime']; ?><br />
                        Groom Time: <?php echo $breed['Time']['Bath']['GroomTime']; ?><br />
                        <strong>Bath &amp; Groom:</strong><br />
                        Bath Time: <?php echo $breed['Time']['Groom']['BathTime']; ?><br />
                        Groom Time: <?php echo $breed['Time']['Groom']['GroomTime']; ?><br />
                    </td>
                </tr>
                <tr><td>Base Bath Price: </td><td><?php echo $breed['BathPrice']; ?></td></tr>
            </table>
            <?php
        }
        else {
        $breed['Time'] = json_decode($breed['Time'], true); ?>
            <h2>Editing Breed <?php echo $breed['ID']; ?></h2>
            <form action="viewbreed.php?id=<?php echo $breed['ID'] ?>" method="post">
                <label for="Name">Breed Name: </label><input type="text" name="Name" id="Name" value="<?php echo $breed['Name'] ?>"><br />
                <label for="Group">Group: </label>
                <select name="Group">
                    <option value="0" <?php echo ($breed['BreedGroup'] == 0 ? 'selected' : ''); ?>>Toy Breeds</option>
                    <option value="1" <?php echo ($breed['BreedGroup'] == 1 ? 'selected' : ''); ?>>Designer Breeds</option>
                    <option value="2" <?php echo ($breed['BreedGroup'] == 2 ? 'selected' : ''); ?>>Terriers</option>
                    <option value="3" <?php echo ($breed['BreedGroup'] == 3 ? 'selected' : ''); ?>>Non-Sporting</option>
                    <option value="4" <?php echo ($breed['BreedGroup'] == 4 ? 'selected' : ''); ?>>Sporting</option>
                    <option value="5" <?php echo ($breed['BreedGroup'] == 5 ? 'selected' : ''); ?>>Hound Group</option>
                    <option value="6" <?php echo ($breed['BreedGroup'] == 6 ? 'selected' : ''); ?>>Herding Group</option>
                    <option value="7" <?php echo ($breed['BreedGroup'] == 7 ? 'selected' : ''); ?>>Working Group</option>
                </select><br />
                <label for="Size">Size: </label>
                <select name="Size">
                    <option value="P" <?php echo ($breed['Size'] == 'P' ? 'selected' : ''); ?>>Petite</option>
                    <option value="S" <?php echo ($breed['Size'] == 'S' ? 'selected' : ''); ?>>Small</option>
                    <option value="M" <?php echo ($breed['Size'] == 'M' ? 'selected' : ''); ?>>Medium</option>
                    <option value="L" <?php echo ($breed['Size'] == 'L' ? 'selected' : ''); ?>>Large</option>
                    <option value="XL" <?php echo ($breed['Size'] == 'XL' ? 'selected' : ''); ?>>Extra-Large</option>
                </select><br />
                <label>Time (In Minutes): </label><br />
                    <label>Bath Only: </label><br />
                        <label for="BathBath">Bathing Time: </label><input id="BathBath" type="text" name="Time[Bath][BathTime]" value="<?php $breed['Time']['Bath']['BathTime'] ?>" /><br />
                        <label for="BathGroom">Grooming Time: </label><input id="BathGroom" type="text" name="Time[Bath][GroomTime]" value="<?php $breed['Time']['Bath']['GroomTime'] ?>" /><br />
                    <label>Bath and Groom: </label><br />
                        <label for="GroomBath">Bathing Time: </label><input id="GroomBath" type="text" name="Time[Groom][BathTime]" value="<?php $breed['Time']['Groom']['BathTime'] ?>" /><br />
                        <label for="GroomGroom">Grooming Time: </label><input id="GroomGroom" type="text" name="Time[Groom][GroomTime]" value="<?php $breed['Time']['Groom']['GroomTime'] ?>" /><br />
                <label for="BathPrice">Base Bath Price: </label><input type="text" name="BathPrice" id="BathPrice" value="<?php echo $breed['BathPrice'] ?>"><br />
                <label for="GroomPrice">Base Grooming Price: </label><input type="text" name="GroomPrice" id="GroomPrice" value="<?php echo $breed['GroomPrice'] ?>"><br />
                <input type="submit" value="Submit">
            </form>
        <?php }
    }
    else {
        echo "<p>I'm sorry, that ID is unrecognized.</p>";
    }
}
else {
    $stmt = $database->query("SELECT * FROM Breeds ORDER BY BreedGroup, Name");
    $breeds = $stmt->fetchAll();
    if(!empty($breeds)) {
        echo '<table><tr><th>Name</th><th>Group</th><th>Size</th><th>Bath-Only Times</th><th>Bath &amp; Groom Times</th><th>Base Bath Price</th><th>Base Groom Price</th></tr>';
        foreach($breeds as $breed) {
            echo '<tr style="cursor: pointer;" onclick="window.document.location=\'viewbreed.php?id=' . $breed['ID'] . '\'">';
            echo '<td>' . $breed['Name'] . '</td>';
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
            $time = json_decode($breed['Time'], true);
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
finish:
?>
    
</body>
</html>