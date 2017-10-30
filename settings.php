<?php
include "include/header.php";

// Only allow admin to edit global settings
if($_SESSION['authenticated'] != 5) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Global Settings</title>
    </head>
<body>
<?php include "include/menu.php"; ?>
<h2>Global Settings</h2>

<?php
    
    $stmt = $database->query('SELECT * FROM Globals');
    $settings = $stmt->fetch();
    if(!empty($settings)) {
        $set = true;
        $tiers = json_decode($settings['Tiers'], true);
        $hours = json_decode($settings['Hours'], true);

    }
    else {
        $set = false;
    }

if(!empty($_POST['timezone']) && !empty($_POST['eventsage']) && !empty($_POST['sigupcharge']) && !empty($_POST['sigprice']) && !empty($_POST['tiers']) && !empty($_POST['hours'])) {
    if(is_array($_POST['tiers']) && is_array($_POST['hours'])) {
        $stmt = $database->prepare('REPLACE INTO Globals (Timezone, EventsAge, SigUpcharge, SigPrice, Tiers, Hours) VALUES (:Timezone, :EventsAge, :SigUpcharge, :SigPrice, :Tiers, :Hours)');
        $stmt->bindValue(':Timezone', $_POST['timezone']);
        $stmt->bindValue(':EventsAge', $_POST['eventsage']);
        $stmt->bindValue(':SigUpcharge', $_POST['sigupcharge']);
        $stmt->bindValue(':SigPrice', $_POST['sigprice']);
        $stmt->bindValue(':Tiers', json_encode($_POST['tiers']));
        $stmt->bindValue(':Hours', json_encode($_POST['hours'], JSON_NUMERIC_CHECK));
        $stmt->execute();
        echo "<p>Global Settings Set!</p>";
    }
    else {
        echo "<p>Could not set settings. The tiers or the hours information was corrupted.</p>";
    }
}
else {
?>
    <form method="post" action="settings.php">
        <label for="timezone">Timezone: </label>
        <select name="timezone" >
            <option disabled selected style='display:none;'>Time Zone...</option>

            <option value="America/Puerto_Rico" <?php if($set && $settings['TimeZone'] == "America/Puerto_Rico") echo("selected"); ?>>Puerto Rico (Atlantic)</option>
            <option value="America/New_York" <?php if($set && $settings['TimeZone'] == "America/New_York") echo("selected"); ?>>New York (Eastern)</option>
            <option value="America/Chicago" <?php if($set && $settings['TimeZone'] == "America/Chicago") echo("selected"); ?>>Chicago (Central)</option>
            <option value="America/Denver" <?php if($set && $settings['TimeZone'] == "America/Denver") echo("selected"); ?>>Denver (Mountain)</option>
            <option value="America/Phoenix" <?php if($set && $settings['TimeZone'] == "America/Phoenix") echo("selected"); ?>>Phoenix (MST)</option>
            <option value="America/Los_Angeles" <?php if($set && $settings['TimeZone'] == "America/Los_Angeles") echo("selected"); ?>>Los Angeles (Pacific)</option>
            <option value="America/Anchorage" <?php if($set && $settings['TimeZone'] == "America/Anchorage") echo("selected"); ?>>Anchorage (Alaska)</option>
            <option value="Pacific/Honolulu" <?php if($set && $settings['TimeZone'] == "America/Honolulu") echo("selected"); ?>>Honolulu (Hawaii)</option>

        </select><br />
        <label for="eventsage">How Long to Keep Scheduled Events (In Months): </label><input type="text" name="eventsage" value="<?php if($set) echo $settings['EventsAge']; ?>" /><br />
        <label for="sigupcharge">Percentage Upcharge for Signature Bath or Spa: </label><input type="text" name="sigupcharge" value="<?php if($set) echo $settings['SigUpcharge']; ?>" />%<br />
        <label for="sigprice">Signature Services Price: </label><input type="text" name="sigprice" value="<?php if($set) echo $settings['SigPrice']; ?>" /><br />
        <label>Time Difference Per Tier (In Minutes): </label><br />
            <label>Gold Tier: </label><br />
                <label for="gp">Petite Dogs: </label><input id="gp" type="text" name="tiers[0][P]" value="<?php if($set) echo $tiers[0]['P']; ?>" /><br />
                <label for="gs">Small Dogs: </label><input id="gs" type="text" name="tiers[0][S]" value="<?php if($set) echo $tiers[0]['S']; ?>" /><br />
                <label for="gm">Medium Dogs: </label><input id="gm" type="text" name="tiers[0][M]" value="<?php if($set) echo $tiers[0]['M']; ?>" /><br />
                <label for="gl">Large Dogs: </label><input id="gl" type="text" name="tiers[0][L]" value="<?php if($set) echo $tiers[0]['L']; ?>" /><br />
                <label for="gx">Extra Large Dogs: </label><input id="gx" type="text" name="tiers[0][XL]" value="<?php if($set) echo $tiers[0]['XL']; ?>" /><br />
            <label>Platinum Tier: </label><br />
                <label for="pp">Petite Dogs: </label><input id="pp" type="text" name="tiers[1][P]" value="<?php if($set) echo $tiers[1]['P']; ?>" /><br />
                <label for="ps">Small Dogs: </label><input id="ps" type="text" name="tiers[1][S]" value="<?php if($set) echo $tiers[1]['S']; ?>" /><br />
                <label for="pm">Medium Dogs: </label><input id="pm" type="text" name="tiers[1][M]" value="<?php if($set) echo $tiers[1]['M']; ?>" /><br />
                <label for="pl">Large Dogs: </label><input id="pl" type="text" name="tiers[1][L]" value="<?php if($set) echo $tiers[1]['L']; ?>" /><br />
                <label for="px">Extra Large Dogs: </label><input id="px" type="text" name="tiers[1][XL]" value="<?php if($set) echo $tiers[1]['XL']; ?>" /><br />
            <label>Diamond Tier: </label><br />
                <label for="dp">Petite Dogs: </label><input id="dp" type="text" name="tiers[2][P]" value="<?php if($set) echo $tiers[2]['P']; ?>" /><br />
                <label for="ds">Small Dogs: </label><input id="ds" type="text" name="tiers[2][S]" value="<?php if($set) echo $tiers[2]['S']; ?>" /><br />
                <label for="dm">Medium Dogs: </label><input id="dm" type="text" name="tiers[2][M]" value="<?php if($set) echo $tiers[2]['M']; ?>" /><br />
                <label for="dl">Large Dogs: </label><input id="dl" type="text" name="tiers[2][L]" value="<?php if($set) echo $tiers[2]['L']; ?>" /><br />
                <label for="dx">Extra Large Dogs: </label><input id="dx" type="text" name="tiers[2][XL]" value="<?php if($set) echo $tiers[2]['XL']; ?>" /><br />
        <label>Store Hours:</label>
        <table>
            <tr><td>Sunday</td><td>Monday</td><td>Tuesday</td><td>Wednesday</td><td>Thursday</td><td>Friday</td><td>Saturday</td></tr>
            <tr>
            <?php
                for($i = 0; $i < 7; $i++) {
                    echo '<td>';
                    echo 'Open: <select name="hours[' . $i . '][open]"><option value="closed">Closed</option>';
                    for($j = 0; $j < 1440; $j += 15) {
                        $time = $j;
                        $min = $time % 60;
                        $hour = ($time - $min) / 60;
                        $s = "AM";
                        if($hour >= 12) {
                            $hour = $hour - 12;
                            $s = "PM";
                        }
                        if($hour == 0) {
                            $hour = 12;
                        }
                        echo '<option' . ($hours[$i]['open'] === $j ? " selected " : " ") . 'value="' . $j . '">' . $hour . ":" . ($min < 10 ? '0' . $min : $min) . $s . '</option>';
                    }
                    echo '</select><br />';
                    echo 'Close: <select name="hours[' . $i . '][close]"><option value="closed">Closed</option>';
                    for($j = 0; $j < 1440; $j += 15) {
                        $time = $j;
                        $min = $time % 60;
                        $hour = ($time - $min) / 60;
                        $s = "AM";
                        if($hour >= 12) {
                            $hour = $hour - 12;
                            $s = "PM";
                        }
                        if($hour == 0) {
                            $hour = 12;
                        }
                        echo '<option' . ($hours[$i]['close'] === $j ? " selected " : " ") . 'value="' . $j . '">' . $hour . ":" . ($min < 10 ? '0' . $min : $min) . $s . '</option>';
                    }
                    echo '</select>';
                    echo '</td>';
                }
            ?>
            </tr>
        </table>
        <input type="submit" name="submit" value="Save Settings">
    </form>

<?php
}
?>
    
</body>
</html>