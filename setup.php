<?php
include "include/header.php";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Initial Setup</title>
    </head>
<body>
    <h2>Initial Setup</h2>
<?php
// Check that setup hasn't already been run
$stmt = $database->query("SHOW TABLES LIKE 'Users'");
$exists = $stmt->fetch();

if($exists) {
    echo "<p>Setup has already been run!</p>";
    die();
}

// If we've submitted the form, create the database and the Admin user.
if(!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['email'])) {
    echo "<p>Creating Database...<br />";
    
    $sql = "
        CREATE TABLE IF NOT EXISTS Pets(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Name TEXT NOT NULL,
            Breed TEXT NOT NULL,
            Age TEXT,
            Weight TEXT,
            Vaccines TEXT,
            Notes TEXT,
            Info TEXT,
            Picture TEXT,
            DogOfMonth INT(32),
            GroomTime INT(32),
            BathTime INT(32),
            TwoPeople INT(1),
            PreferredGroomer VARCHAR(255),
            Status INT(1),
            OwnedBy INT(11),
            FULLTEXT (Name)
    ); ALTER TABLE Pets AUTO_INCREMENT=230;";
    $sql1 = "
        CREATE TABLE IF NOT EXISTS Owners(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            FirstName TEXT,
            LastName TEXT,
            Phone TEXT,
            Address1 TEXT,
            Address2 TEXT,
            City TEXT,
            State TEXT,
            Zip TEXT,
            Country TEXT,
            Email TEXT,
            SpouseName TEXT,
            SpousePhone TEXT,
            Emergency TEXT,
            EmergencyPhone TEXT,
            AuthorizedPickup TEXT,
            APPhone TEXT,
            ReferredBy TEXT,
            FULLTEXT (FirstName, LastName)
    ); ALTER TABLE Owners AUTO_INCREMENT=120;";
    $sql2 = "
        CREATE TABLE IF NOT EXISTS Scheduling(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            PetID INT(11),
            StartTime INT(11) UNSIGNED,
            TotalTime INT(11),
            GroomerID VARCHAR(255),
            Recurring INT(1),
            RecInterval INT(2),
            EndDate INT(11) UNSIGNED,
            Package INT(1),
            Services JSON
    )";
    $sql3 = "
        CREATE TABLE IF NOT EXISTS Users(
            ID VARCHAR(255) PRIMARY KEY,
            Name TEXT,
            Email TEXT,
            Password TEXT,
            Access INT(1),
            MaxDogs INT(2),
            Missed Int(1),
            Visited INT(1),
            Seniority INT(1)

    )";
    $sql4 = "
        CREATE TABLE IF NOT EXISTS Services(
            ID INT(2) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Name TEXT,
            Price INT(2),
            Time INT(2),
            Signature INT(1)

    )";
    $database->exec($sql);
    $database->exec($sql1);
    $database->exec($sql2);
    $database->exec($sql3);
    $database->exec($sql4);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $database->prepare('INSERT INTO Users (ID, Email, Password, Access, Visited) VALUES (:ID, :Email, :Password, 3, 1)');
    $stmt->bindValue(':ID', $_POST['username']);
    $stmt->bindValue(':Email', $_POST['email']);
    $stmt->bindValue(':Password', $password);
    $stmt->execute();
    
    echo 'All Done! Please <a href="login.php">login</a> with the account you just created.</p>';
}
// if we haven't already submitted the form, display it.
else {
    ?>

    <form method="post" action="setup.php">
        <label for="username">Username: </label><input type="text" name="username" /><br />
        <label for="email">Email: </label><input type="text" name="email" /><br />
        <label for="password">Password: </label><input type="password" name="password" /><br />
        <input type="submit" name="submit" value="Create User">
    </form>
</body>
</html>

<?php
}
?>