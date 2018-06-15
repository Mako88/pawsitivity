<?php
include "include/header.php";

// Only allow Employees and Admins to view services.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Service</title>
<link rel='stylesheet' href='css/styles.css' />
</head>
<body>

<?php
    
include "include/menu.php";
    
if(!empty($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $database->prepare("SELECT * FROM Services WHERE ID = :ID");
    $stmt->bindValue(':ID', $id);
    $stmt->execute();
    $service = $stmt->fetch();
    if(!empty($service)) {
        if(!empty($_POST['name']) && isset($_POST['type']) && !empty($_POST['time']) && !empty($_POST['price'])) {
            if(is_array($_POST['time']) && is_array($_POST['price'])) {
                $stmt = $database->prepare('UPDATE Services SET Name=:Name, Description=:Description, Type=:Type, Time=:Time, Price=:Price WHERE ID = :ID');
                $stmt->bindValue(':Name', $_POST['name']);
                $stmt->bindValue(':Description', $_POST['description']);
                $stmt->bindValue(':Type', $_POST['type']);
                $stmt->bindValue(':Time', json_encode($_POST['time'], JSON_NUMERIC_CHECK));
                $stmt->bindValue(':Price', json_encode($_POST['price'], JSON_NUMERIC_CHECK));
                $stmt->bindValue(':ID', $id);
                $stmt->execute();
                
                $stmt = $database->prepare("SELECT * FROM Services WHERE ID = :ID");
                $stmt->bindValue(':ID', $id);
                $stmt->execute();
                $service = $stmt->fetch();
            }
            else {
                echo "<p>Could not add service. The time and/or price information was corrupted.</p>";
                goto finish;
            }

        }
        
        if(!empty($_GET['delete'])) {
            $stmt = $database->prepare("DELETE FROM Services WHERE ID = :ID");
            $stmt->bindValue(':ID', $_GET['delete']);
            $stmt->execute();
            echo '<p>Service deleted!</p>';
            goto finish;
        }
        
        if(empty($_GET['e'])) {
        
            switch($service['Type']) {
                case 0:
                    $type = 'Signature Service';
                    break;
                case 1:
                    $type = 'Bath Service';
                    break;
                case 2:
                    $type = 'Groom Service';
                    break;
            }
    
            $service['Time'] = json_decode($service['Time'], true);
            $service['Price'] = json_decode($service['Price'], true); ?>
            <div class="editbox">
                <a class="buttonlink" href="viewservice.php?id=<?php echo $service['ID']; ?>&e=1">Edit Service</a>
                <a class="buttonlink" href="viewservice.php?id=<?php echo $service['ID']; ?>&delete=<?php echo $service['ID']; ?>" onclick="return confirm('Are you sure you want to delete this service?')">Delete Service</a><br />
            </div>
            <table class="infotable">
                <tr><td>ID: </td><td><?php echo $service['ID']; ?></td></tr>
                <tr><td>Name: </td><td><?php echo $service['Name']; ?></td></tr>
                <tr><td>Description: </td><td><?php echo $service['Description']; ?></td></tr>
                <tr><td>Type: </td><td><?php echo $type; ?></td></tr>
                <tr>
                    <td>Time (In Minutes): </td>
                    <td>
                        Petite: <?php echo $service['Time']['P']; ?><br />
                        Small: <?php echo $service['Time']['S']; ?><br />
                        Medium: <?php echo $service['Time']['M']; ?><br />
                        Large: <?php echo $service['Time']['L']; ?><br />
                        Extra-Large: <?php echo $service['Time']['XL']; ?><br />
                    </td>
                </tr>
                <tr>
                    <td>Price: </td>
                    <td>
                        Petite: <?php echo '$' . $service['Price']['P']; ?><br />
                        Small: <?php echo '$' . $service['Price']['S']; ?><br />
                        Medium: <?php echo '$' . $service['Price']['M']; ?><br />
                        Large: <?php echo '$' . $service['Price']['L']; ?><br />
                        Extra-Large: <?php echo '$' . $service['Price']['XL']; ?><br />
                    </td>
                </tr>
            </table>
            <?php
        }
        else {
            $service['Time'] = json_decode($service['Time'], true);
            $service['Price'] = json_decode($service['Price'], true); ?>
            <h2>Editing Service <?php echo $service['ID']; ?></h2>
            <form class="infoform" action="viewservice.php?id=<?php echo $service['ID'] ?>" method="post">
                <label for="name">Name: </label><input id="name" type="text" name="name" value="<?php echo $service['Name']; ?>" /><br />
                <label for="description">Description: </label><textarea id="price" name="description"><?php echo $service['Description']; ?></textarea><br />
                <label for="type">Type: </label>
                <select name="type" id="type">
                    <option value="0" <?php echo ($service['Type'] == 0 ? 'selected' : ''); ?>>Signature Service</option>
                    <option value="1" <?php echo ($service['Type'] == 1 ? 'selected' : ''); ?>>Bathing Service</option>
                    <option value="2" <?php echo ($service['Type'] == 2 ? 'selected' : ''); ?>>Grooming Service</option>
                </select><br />
                <h3>Petite Dogs: </h3>
                    <label class="offset" for="pt">Time: </label><input id="pt" type="text" name="time[P]" value="<?php echo $service['Time']['P']; ?>" /><br />
                    <label class="offset" for="pp">Price: </label><input id="pp" type="text" name="price[P]" value="<?php echo $service['Price']['P']; ?>" />
                <h3>Small Dogs: </h3>
                    <label class="offset" for="st">Time: </label><input id="st" type="text" name="time[S]" value="<?php echo $service['Time']['S']; ?>" /><br />
                    <label class="offset" for="sp">Price: </label><input id="sp" type="text" name="price[S]" value="<?php echo $service['Price']['S']; ?>" />
                <h3>Medium Dogs: </h3>
                    <label class="offset" for="mt">Time: </label><input id="mt" type="text" name="time[M]" value="<?php echo $service['Time']['M']; ?>" /><br />
                    <label class="offset" for="mp">Price: </label><input id="mp" type="text" name="price[M]" value="<?php echo $service['Price']['M']; ?>" />
                <h3>Large Dogs: </h3>
                    <label class="offset" for="lt">Time: </label><input id="lt" type="text" name="time[L]" value="<?php echo $service['Time']['L']; ?>" /><br />
                    <label class="offset" for="lp">Price: </label><input id="lp" type="text" name="price[L]" value="<?php echo $service['Price']['L']; ?>" />
                <h3>Extra Large Dogs: </h3>
                    <label class="offset" for="xt">Time: </label><input id="xt" type="text" name="time[XL]" value="<?php echo $service['Time']['XL']; ?>" /><br />
                    <label class="offset" for="xp">Price: </label><input id="xp" type="text" name="price[XL]" value="<?php echo $service['Price']['XL']; ?>" /><br />

                <input type="submit" name="submit" value="Update Service">
            </form>
        <?php }
    }
    else {
        echo "<p>I'm sorry, that ID is unrecognized.</p>";
        goto finish;
    }
}
else {
    $stmt = $database->query("SELECT * FROM Services ORDER BY Type, Name");
    $services = $stmt->fetchAll();
    if(!empty($services)) {
        echo '<table class="longlist"><tr><th>Name</th><th>Type</th><th>Description</th></tr>';
        foreach($services as $service) {
            echo '<tr style="cursor: pointer;" onclick="window.document.location=\'viewservice.php?id=' . $service['ID'] . '\'">';
            echo '<td>' . $service['Name'] . '</td>';
            echo '<td>';
            switch($service['Type']) {
                case 0:
                    $type = 'Signature Service';
                    break;
                case 1:
                    $type = 'Bath Service';
                    break;
                case 2:
                    $type = 'Groom Service';
                    break;
            }
            echo $type;
            echo '</td>';
            echo '<td>' . substr($service['Description'], 0, 50) . '...</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo "<p>I'm Sorry, no results! :/</p>";
    }
}
finish:
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/menu.js"></script>
</body>
</html>