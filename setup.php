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
$stmt = $database->query("SHOW TABLES LIKE 'Services'");
$exists = $stmt->fetch();

if($exists) {
    echo "<p>Setup has already been run!</p>";
    die();
}
    
function createDatabase($sql) {
    
    global $database;
    
    $result = $database->exec($sql);
    
    if($result == false) {
        $err = $database->errorInfo();
        if ($err[0] === '00000' || $err[0] === '01000') {
            return true;
        }
    }
    else {
        return false;
    }
}

// If we've submitted the form, create the database and the Admin user.
if(!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['email'])) {
    
    echo "<p>Creating Database...<br />";
    
    $sql = array();
    
    $sql[0] = "
        CREATE TABLE IF NOT EXISTS Pets(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Name TEXT NOT NULL,
            Breed INT(2) NOT NULL,
            Age TEXT,
            Weight TEXT,
            Coloring TEXT,
            Vet TEXT,
            Vaccines TEXT,
            Vaccines2 TEXT,
            ReleaseForm TEXT,
            Notes TEXT,
            Info TEXT,
            Picture TEXT,
            DogOfMonth TEXT,
            Time TEXT,
            TwoPeople INT(1),
            PreferredGroomer VARCHAR(255),
            Status INT(1),
            OwnedBy INT(11),
            FULLTEXT (Name)
    ); ALTER TABLE Pets AUTO_INCREMENT=230;";
    $sql[1] = "
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
            DateCreated TEXT,
            FULLTEXT (FirstName, LastName)
    ); ALTER TABLE Owners AUTO_INCREMENT=120;";
    $sql[2] = "
        CREATE TABLE IF NOT EXISTS Scheduling(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            PetID INT(11),
            StartTime INT(11) UNSIGNED,
            GroomTime INT(11),
            BathTime INT(11),
            TotalTime INT(11),
            GroomerID VARCHAR(255),
            Recurring INT(1),
            RecInterval INT(2),
            EndDate INT(11) UNSIGNED,
            Package INT(1),
            Services TEXT,
            Price DECIMAL(5, 2),
            Notes TEXT
    )";
    $sql[3] = "
        CREATE TABLE IF NOT EXISTS Users(
            ID VARCHAR(255) PRIMARY KEY,
            Name TEXT,
            Email TEXT,
            Password TEXT,
            Access INT(1),
            Tier INT(2),
            Missed INT(1),
            Visited INT(1),
            Seniority INT(1)

    )";
    $sql[4] = "
        CREATE TABLE IF NOT EXISTS Breeds(
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Name TEXT,
            BreedGroup INT(1),
            Size VARCHAR(2),
            Time TEXT,
            GroomPrice INT(11),
            BathPrice INT(11)
    )";
    $sql[5] = "
        CREATE TABLE IF NOT EXISTS Globals(
            OneRow enum('only') NOT NULL UNIQUE DEFAULT 'only',
            TimeZone TEXT,
            EventsAge INT(2),
            SigUpcharge INT(2),
            SigPrice DECIMAL(5,2),
            Tiers TEXT
    )";
    $sql[6] = "
        CREATE TABLE IF NOT EXISTS Services(
            ID INT(2) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Name TEXT,
            Description TEXT,
            Type INT(1),
            Time TEXT,
            Price TEXT
    )";
    
    foreach($sql as $s) {
        if(!createDatabase($s)) {
            echo "<p>An error occured: " . print_r($database->errorInfo(), true);
            die();
        }
    }

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $database->prepare('INSERT INTO Users (ID, Email, Password, Access, Visited) VALUES (:ID, :Email, :Password, 5, 1)');
    $stmt->bindValue(':ID', $_POST['username']);
    $stmt->bindValue(':Email', $_POST['email']);
    $stmt->bindValue(':Password', $password);
    $stmt->execute();
    
    $stmt = $database->prepare('REPLACE INTO Globals (Timezone) VALUES (:Timezone)');
    $stmt->bindValue(':Timezone', $_POST['timezone']);
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
        <label for="timezone">Timezone: </label>
        <select name="timezone" >
            <option disabled selected style='display:none;'>Time Zone...</option>

            <option value="America/Puerto_Rico">Puerto Rico (Atlantic)</option>
            <option value="America/New_York">New York (Eastern)</option>
            <option value="America/Chicago">Chicago (Central)</option>
            <option value="America/Denver">Denver (Mountain)</option>
            <option value="America/Phoenix">Phoenix (MST)</option>
            <option value="America/Los_Angeles">Los Angeles (Pacific)</option>
            <option value="America/Anchorage">Anchorage (Alaska)</option>
            <option value="Pacific/Honolulu">Honolulu (Hawaii)</option>

        </select><br />
        <input type="submit" name="submit" value="Create User">
    </form>
</body>
</html>

<?php
}
?>