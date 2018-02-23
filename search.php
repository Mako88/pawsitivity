<?php
include "include/header.php";

// Only allow Employees and Admins to search.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

function find_key_value($array, $key, $val)
{
    foreach ($array as $item)
    {
        if (is_array($item) && find_key_value($item, $key, $val)) return true;

        if (isset($item[$key]) && $item[$key] == $val) return true;
    }

    return false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search</title>
<link rel='stylesheet' href='css/styles.css' />
</head>
<body>
<?php include "include/menu.php"; ?>
    
<form class="infoform search" action="search.php" method="post">
    <input type="text" name="search" id="bigsearch" placeholder="Search...">
    <input type="submit" value="Go">
</form>

<?php
    
if(!empty($_POST['search'])) {
    
    $clientids = array();
    
    echo '<h2>Clients</h2>';
    $stmt = $database->prepare("SELECT ID, FirstName, LastName, MATCH (FirstName, LastName) AGAINST (:Search1 IN BOOLEAN MODE) as score FROM Owners WHERE ( MATCH (FirstName, LastName) AGAINST (:Search2 IN BOOLEAN MODE) ) OR ID = :Search3 ORDER BY score DESC ");
    $stmt->bindValue(':Search1', $_POST['search']);
    $stmt->bindValue(':Search2', $_POST['search']);
    $stmt->bindValue(':Search3', $_POST['search']);
    $stmt->execute();
    $clients = $stmt->fetchAll();
    if(!empty($clients)) {
        echo '<table class="shortlist shorttable"><tr><th>ID</th><th>First Name</th><th>Last Name</th></tr>';
        foreach($clients as $client) {
            if(!in_array($client['ID'], $clientids)) { array_push($clientids, $client['ID']); }
            echo '<tr class="listline" onclick="window.document.location=\'viewclient.php?id=' . $client['ID'] . '\'"><td>' . $client['ID'] . '</td><td>' . $client['FirstName'] . '</td><td>' . $client['LastName'] . '</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo "<p>I'm Sorry, no results! :/</p>";
    }
    
    echo '<h2>Pets</h2>';
    $stmt = $database->prepare("SELECT ID, Name, Breed, OwnedBy, MATCH (Name) AGAINST (:Search1 IN BOOLEAN MODE) as score FROM Pets WHERE ( MATCH (Name) AGAINST (:Search2 IN BOOLEAN MODE) ) OR ID = :Search3 ORDER BY score DESC ");
    $stmt->bindValue(':Search1', $_POST['search']);
    $stmt->bindValue(':Search2', $_POST['search']);
    $stmt->bindValue(':Search3', $_POST['search']);
    $stmt->execute();
    $pets = $stmt->fetchAll();
    foreach($clientids as $clientid) {
        $stmt = $database->query("SELECT ID, Name, Breed, OwnedBy FROM Pets WHERE OwnedBy = " . $clientid);
        foreach($stmt->fetchAll() as $pet) {
            if(!find_key_value($pets, "ID", $pet['ID'])) { array_push($pets, $pet); }
        }
    }
    if(!empty($pets)) {
        echo '<table class="shortlist shorttable"><tr><th>ID</th><th>Name</th><th>Owned By</th></tr>';
        foreach($pets as $pet) {
            $stmt = $database->query("SELECT FirstName, LastName FROM Owners WHERE ID = " . $pet['OwnedBy']);
            $owner = $stmt->fetch();
            echo '<tr class="listline"><td><a href="viewpet.php?id=' . $pet['ID'] . '">' . $pet['ID'] . '</td><td><a href="viewpet.php?id=' . $pet['ID'] . '">' . $pet['Name'] . '</a></td><td><a href="viewclient.php?id=' . $pet['OwnedBy'] . '">' . $owner['FirstName'] . ' ' . $owner['LastName'] . ' (' . $pet['OwnedBy'] . ')' . '</a></td></tr>';
        }
        echo '</table>';
    }
    else {
        echo "<p>I'm Sorry, no results! :/</p>";
    }
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/menu.js"></script>
</body>
</html>