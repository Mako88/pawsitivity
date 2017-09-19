<?php
include "include/header.php";

// Don't allow clients to create new users.
if($_SESSION['authenticated'] < 2) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Create User</title>
    </head>
<body>
<?php include "include/menu.php"; ?>
<h2>Create User</h2>

<?php

if(!empty($_POST['username']) && !empty($_POST['name']) && !empty($_POST['password']) && !empty($_POST['email']) && !empty($_POST['level'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $database->prepare('INSERT INTO Users (ID, Name, Email, Password, Access, Tier, Visited, Seniority) VALUES (:ID, :Name, :Email, :Password, :Access, :Tier, 1, :Seniority)');
    $stmt->bindValue(':ID', $_POST['username']);
    $stmt->bindValue(':Name', $_POST['name']);
    $stmt->bindValue(':Email', $_POST['email']);
    $stmt->bindValue(':Password', $password);
    $stmt->bindValue(':Access', $_POST['level']);
    $stmt->bindValue(':Tier', $_POST['tier']);
    $stmt->bindValue(':Access', $_POST['level']);
    (is_numeric($_POST['seniority'])) ? $stmt->bindValue(':Seniority', $_POST['seniority']) : $stmt->bindValue(':Seniority', NULL);
    $stmt->execute();
    echo "<p>User added!</p>";
}
else {
?>
    <form method="post" action="newuser.php">
        <label for="username">Username: </label><input type="text" name="username" /><br />
        <label for="name">Name: </label><input type="text" name="name" /><br />
        <label for="email">Email: </label><input type="text" name="email" /><br />
        <label for="password">Password: </label><input type="password" name="password" /><br />
        <label for="level">User Access Level: </label>
        <select name="level">
            <option value="2">Groomer</option>
            <option value="3">Bather</option>
        </select><br />
        <label for="tier">Groomer Tier: </label>
        <select name="tier">
            <option value="0">Gold</option>
            <option value="1">Platinum</option>
            <option value="2">Diamond</option>
        </select><br />
        <label for="seniority">Seniority: </label><input type="seniority" name="seniority" /><br />
        <input type="submit" name="submit" value="Create User">
    </form>

<?php
}
?>
    
</body>
</html>