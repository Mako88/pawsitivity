<?php
include "include/header.php";

// Only allow logged in users to schedule.
if($_SESSION['authenticated'] < 1) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

$stmt = $database->query("SELECT Timezone, Hours FROM Globals");
$res = $stmt->fetch();
$timezone = $res['Timezone'];
$hours = $res['Hours'];

$_SESSION['Timezone'] = $timezone;
$_SESSION['Hours'] = $hours;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/moment-timezone.min.js"></script>
    <script src="js/pikaday.js"></script>
    <script src="js/pikaday.jquery.js"></script>
    <link rel="stylesheet" type="text/css" href="css/pikaday.css" />
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <meta charset="UTF-8">
    <title>Scheduling</title>
</head>
<body>

<?php
    
    include 'include/menu.php';
    
    if(empty($_POST) && empty($_GET['pet'])) {
        
        $_SESSION['page'] = 'package';
        
        // Only allow employees or admins to schedule other people's pets
        if(!empty($_GET['id']) && $_SESSION['authenticated'] > 1) {
            $id = $_GET['id'];
        }
        else {
            $id = $_SESSION['ID'];
        }

        $stmt = $database->prepare("SELECT * FROM Pets WHERE OwnedBy = :ID");
        $stmt->bindValue(':ID', $id);
        $stmt->execute();
        $pets = $stmt->fetchAll();

        if(!empty($pets)) {
            
            echo '<form action="schedule.php" method="post">';
            echo '<label for="pet">Select which pet you would like to schedule: </label>';
            echo '<select id="pet" name="pet">';
            foreach($pets as $pet) {
                echo '<option value="' . $pet['ID'] . '">' . $pet['Name'] . '</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="Next" />';
            echo '</form>';
            goto finish;
        }
        else {
            echo '<p>We\'re sorry, that client has no pets.</p>';
            goto finish;
        }
    }
    else if((!empty($_GET['pet']) && $_SESSION['authenticated'] > 1) || $_SESSION['page'] == 'package') {
        $_SESSION['page'] = 'date';        
        $petid = (!empty($_GET['pet']) ? $_GET['pet'] : $_POST['pet']);
        $stmt = $database->prepare("SELECT * FROM Pets WHERE ID = :ID");
        $stmt->bindValue(':ID', $petid);
        $stmt->execute();
        $pet = $stmt->fetch();
        if(!empty($pet)) {
            $stmt = $database->prepare("SELECT Size, GroomPrice, BathPrice FROM Breeds WHERE ID = :ID");
            $stmt->bindValue(':ID', $pet['Breed']);
            $stmt->execute();
            $res = $stmt->fetch();
			
			$stmt = $database->query("SELECT Timezone FROM Globals");
			$timezone = $stmt->fetch();
			
            $_SESSION['info'] = array();
            $_SESSION['info']['client'] = $pet['OwnedBy'];
            $_SESSION['info']['Time'] = json_decode($pet['Time'], true);
            $_SESSION['info']['Size'] = $res['Size'];
            $_SESSION['info']['GroomPrice'] = $res['GroomPrice'];
            $_SESSION['info']['BathPrice'] = $res['BathPrice'];
            $_SESSION['info']['previd'] = -1;
            
            $stmt = $database->query("SELECT * FROM Services");
            $services = $stmt->fetchAll();
            if(!empty($services)) {
                $_SESSION['info']['pet'] = $pet['ID'];
                $stmt = $database->query("SELECT Name, ID, Tier FROM Users WHERE Access = 2");
                $groomers = $stmt->fetchAll();
                $stmt = $database->query("SELECT Name, ID, Tier FROM Users WHERE Access = 3");
                $bathers = $stmt->fetchAll();
                
                $stmt = $database->query("SELECT SigUpcharge, SigPrice FROM Globals");
                $globals = $stmt->fetch();
                $package = $prevgroomer = '0';
                $servicelist = Array();
                
                if(!empty($_GET['eventid']) && $_SESSION['authenticated'] > 1) {
                    $stmt = $database->prepare("SELECT * FROM Scheduling WHERE ID = :ID");
                    $stmt->bindValue(':ID', $_GET['eventid']);
                    $stmt->execute();
                    $prevevent = $stmt->fetch();
                    $servicelist = json_decode($prevevent['Services'], true);
                    if(empty($servicelist)) {
                        $servicelist = Array();
                    }
                    $prevgroomer = $prevevent['GroomerID'];
                    $package = $prevevent['Package'];
                    if(!empty($_GET['starttime'])) {
                        $_SESSION['info']['prevstart'] = $_GET['starttime'];
                    }
                    else {
                        $_SESSION['info']['prevstart'] = $prevevent['StartTime'];
                    }
                    
                    $_SESSION['info']['previd'] = $prevevent['ID'];
                }
                
                echo '<form action="schedule.php" method="post">';
                
                echo '<label for="package">Select Package: </label><select id="package" name="package">';
                echo '<option value="1" ' . (intval($package) == 1 ? 'selected' : '') . '>Basic Bath</option>';
                echo '<option value="2" ' . (intval($package) == 2 ? 'selected' : '') . '>Basic Groom</option>';
                echo '</select><br />';
                
                echo '<p>Select which services you would like to schedule: </p>';
                echo '<input type="checkbox" id="signature" name="services[]" value="signature" . ' . (in_array('signature', $servicelist) ? 'checked' : '') . ' />';
                echo '<label style="display: inline-block; margin-bottom: 20px;" for="signature">Signature Package</label>';
                echo '<h3>Signature Options</h3>';
                foreach($services as $service) {
                    if($service['Type'] == 0) {
                        echo '<input class="signature" type="checkbox" name="services[]" id="' . $service['ID'] . '" value="' . $service['ID'] . '" ' . (in_array($service['ID'], $servicelist) ? 'checked' : '') . '/>';
                        echo '<label for="' . $service['ID'] . '">' . $service['Name'] . '</label><br />';
                    }
                }
                echo '<h3>Additional Enhancements</h3>';
                foreach($services as $service) {
                    if($service['Type'] != 0) {
                        echo '<input class="enhancement" type="checkbox" name="services[]" id="' . $service['ID'] . '" value="' . $service['ID'] . '" ' . (in_array($service['ID'], $servicelist) ? 'checked' : '') . '/>';
                        echo '<label for="' . $service['ID'] . '">' . $service['Name'] . '</label><br />';
                    }
                }

                echo '<label for="groomer">Preferred Groomer: </label><select id="groomer" name="groomer">';
                echo '<option value="NULL">Any</option>';
                /*foreach($groomers as $groomer) {
                    if(!empty($prevgroomer)) {
                        echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $prevgroomer) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                    }
                    else {
                        echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $pet['PreferredGroomer']) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                    }
                }*/
                echo '</select><br />';
                echo '<div id="price"></div>';
                echo '<input type="hidden" name="price" id="price2" />';
                echo '<input type="submit" value="Next" />';
                echo '</form>'; ?>
    
                <script>
                    
                var price = 0;
                var groom = <?php echo $_SESSION['info']['GroomPrice']; ?>;
                var bath = <?php echo $_SESSION['info']['BathPrice']; ?>;
                var services = <?php echo json_encode($services); ?>;
                var size = "<?php echo $_SESSION['info']['Size'] ?>";
                var groomers = <?php echo json_encode($groomers); ?>;
                var bathers = <?php echo json_encode($bathers); ?>;
                var prevgroomer = "<?php echo $prevgroomer; ?>";

                
                for(var i = 0; i < services.length; i++) {
                    services[i]['Price'] = JSON.parse(services[i]['Price'], true);
                }
                
                function updatePrice() {
                    
                    // Update groomer list
                    $('#groomer').children('option:not(":first")').remove();
                    for(var i = 0; i < groomers.length; i++) {
                        $('#groomer').append('<option value="' + groomers[i]['ID'] + '"' + (prevgroomer == groomers[i]['ID'] ? ' selected>' : '>') + groomers[i]['Name'] + '</option>');
                    }
                    if($('#package option:selected').val() == 1) {
                        for(var i = 0; i < bathers.length; i++) {
                            $('#groomer').append('<option value="' + bathers[i]['ID'] + '"' + (prevgroomer == bathers[i]['ID'] ? ' selected>' : '>') + bathers[i]['Name'] + '</option>');
                        }
                    }
                    
                    price = 0;
                    selectedservices = Array();
                    var package = parseInt($("#package").val());
                    switch(package) {
                        case 1:
                            price += bath;
                            break;
                        case 2:
                            price += groom;
                            break;
                    }
                    
                    if($('#signature').is(':checked')) {
                        price += 15;
                        var num = $(".signature:checked").length;
                        if(num >= 3) {
                            $(".signature:not(:checked)").each(function() {
                                $(this).attr("disabled", true);
                            });
                        }
                        else {
                            $(".signature").each(function() {
                                $(this).removeAttr("disabled");
                            });
                        }
                    }
                    else {
                        $(".signature").each(function() {
                            $(this).removeAttr("disabled");
                        });
                        
                        $(".signature:checked").each(function() {
                            for(var i = 0; i < services.length; i++) {
                                if($(this).attr("id") == services[i]['ID']) {
                                    selectedservices.push(services[i]['Price'][size]);
                                }
                            }
                        });
                    }
                        
                    $(".enhancement:checked").each(function() {
                        for(var i = 0; i < services.length; i++) {
                            if($(this).attr("id") == services[i]['ID']) {
                                selectedservices.push(services[i]['Price'][size]);
                            }
                        }
                    });

                    for(var i = 0; i < selectedservices.length; i++) {
                        price += Number(selectedservices[i]);
                    }
                    
                    $("#price").text("Price: $" + price);
                    $("#price2").val(price);
                }
                
                $(function() {
                    updatePrice();
                    $("#package").change(function() {
                        updatePrice();
                    });
                    
                    $("input:checkbox").change(function() {
                        updatePrice();
                    });
                });
                    
                
                </script>            
                
            <?php goto finish; }
            else {
                echo '<p>We\'re sorry, there are no services stored yet.</p>';
                goto finish;
            }
        }
        else {
            echo '<p>We\'re sorry, you submitted an invalid pet ID.</p>';
            goto finish;
        }
    }
    else if($_SESSION['page'] == 'date') {
        
        $_SESSION['page'] = 'summary';
        
        if($_POST['package'] == 1 || $_POST['package'] == 2 || $_POST['package'] == 3 || $_POST['package'] == 4) {
            $_SESSION['info']['package'] = $_POST['package'];
        }
        else {
            echo '<p>Your package selection data is corrupted.</p>';
            goto finish;
        }
        
        if(!empty($_POST['services']) && is_array($_POST['services'])) {
            $_SESSION['info']['services'] = $_POST['services'];
        }
        
        if(!empty($_POST['groomer'])) {
            $now = time();
            $stmt = $database->prepare("(SELECT * FROM Scheduling WHERE GroomerID = :ID1 AND Recurring = 0 AND StartTime >= :Time1) UNION (SELECT * FROM Scheduling WHERE GroomerID = :ID2 AND Recurring = 1 AND EndDate >= :Time2)");
            $stmt->bindValue(':ID1', $_POST['groomer']);
            $stmt->bindValue(':Time1', $now);
            $stmt->bindValue(':ID2', $_POST['groomer']);
            $stmt->bindValue(':Time2', $now);
            $stmt->execute();
            $events = $stmt->fetchAll();
            if(!empty($events)) {
                $stmt = $database->prepare("SELECT ID, Seniority, Tier FROM Users WHERE ID = :ID");
                $stmt->bindValue(':ID', $_POST['groomer']);
                $stmt->execute();
                $groomers = $stmt->fetchAll();
            }
            else {
                $stmt = $database->prepare("(SELECT * FROM Scheduling WHERE Recurring = 0 AND StartTime >= :Time1) UNION (SELECT * FROM Scheduling WHERE Recurring = 1 AND EndDate >= :Time2)");
                $stmt->bindValue(':Time1', $now);
                $stmt->bindValue(':Time2', $now);
                $stmt->execute();
                $events = $stmt->fetchAll();
                if($_SESSION['info']['package'] == 1) {
                    $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2 OR Access = 3 ORDER BY Seniority");
                    $groomers = $stmt->fetchAll();
                }
                else {
                    $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2 ORDER BY Seniority");
                    $groomers = $stmt->fetchAll();
                }
            }
        }
        else {
            $stmt = $database->prepare("(SELECT * FROM Scheduling WHERE Recurring = 0 AND StartTime >= :Time1) UNION (SELECT * FROM Scheduling WHERE Recurring = 1 AND EndDate >= :Time2)");
            $stmt->bindValue(':Time1', $now);
            $stmt->bindValue(':Time2', $now);
            $stmt->execute();
            $events = $stmt->fetchAll();
            if($_SESSION['info']['package'] == 1) {
                $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2 OR Access = 3 ORDER BY Seniority");
                $groomers = $stmt->fetchAll();
            }
            else {
                $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2 ORDER BY Seniority");
                $groomers = $stmt->fetchAll();
            }
        }
        
        // Add size of dog to each event
        foreach($events as $key => $event) {
            $stmt = $database->prepare("SELECT Breed FROM Pets WHERE ID = :ID");
            $stmt->bindValue(':ID', $event['PetID']);
            $stmt->execute();
            $breed = $stmt->fetch();
            $stmt = $database->prepare("SELECT Size FROM Breeds WHERE ID = :ID");
            $stmt->bindValue(':ID', $breed['Breed']);
            $stmt->execute();
            $size = $stmt->fetch();
            $events[$key]['Size'] = $size['Size'];
        }
        
        // Calculate time for current pet
        $totaltime = 0;
        
        if($_SESSION['info']['package'] == 2 || $_SESSION['info']['package'] == 4) {
            $groomtime = $_SESSION['info']['Time']['Groom']['GroomTime'];
            $bathtime = $_SESSION['info']['Time']['Groom']['BathTime'];
        }
        else {
            $groomtime = $_SESSION['info']['Time']['Bath']['GroomTime'];
            $bathtime = $_SESSION['info']['Time']['Bath']['BathTime'];
        }
        
        if(!empty($_SESSION['info']['services'])) {
        
            foreach($_SESSION['info']['services'] as $service) {
                $stmt = $database->prepare("SELECT Time, Type FROM Services WHERE ID = :ID");
                $stmt->bindValue(':ID', $service);
                $stmt->execute();
                $result = $stmt->fetch();
                $servicetime = json_decode($result['Time'], true);
                
                switch($result['Type']) {
                    case 1: // Bath
                        $bathtime += $servicetime[$_SESSION['info']['Size']];
                        break;
                    case 2: // Groom
                        $groomtime += $servicetime[$_SESSION['info']['Size']];
                        break;
                }
            }
        }        
        
        $totaltime = ceil(($bathtime + $groomtime)/15)*15;
        
        if(empty($totaltime)) {
            echo "<p>We're sorry, but the total time is zero.</p>";
            goto finish;
        }
        
        // Round total time, but not individual times
        $_SESSION['info']['TotalTime'] = $totaltime;
        $_SESSION['info']['BathTime'] = $bathtime;
        $_SESSION['info']['GroomTime'] = $groomtime;
        
        // Since dogs can be bathed and groomed concurrently, use just the groom time
        // as the slot size
        $slottime = ceil($groomtime/15)*15;
        
        $stmt = $database->query("SELECT Tiers FROM Globals");
        $tiers = $stmt->fetch();
        
        // Set price
        
        $_SESSION['info']['Price'] = $_POST['price'];
        
        $prevstart = "false";
        if(!empty($_SESSION['info']['prevstart'])) {
            $prevstart = $_SESSION['info']['prevstart'];
        }
        
        if($_SESSION['info']['Price'] == 0) {
            echo '<p class="error">WARNING: Price is &#36;0</p>';
        }
        if($_SESSION['info']['BathTime'] == 0) {
            echo '<p class="error">WARNING: Bathing time is &#36;0</p>';
        }
        if($_SESSION['info']['GroomTime'] == 0) {
            echo '<p class="error">WARNING: Grooming time is 0</p>';
        }
?>
    <form action="schedule.php" method="post" id="day">
        <label for="datepicker">Please pick a day to schedule your pet: </label>
        <input type="text" id="datepicker" name="date" /><br />
        <label for="slot">Please pick a time slot: </label>
        <select id="slot" name="slot"><option value="NULL" selected disabled>Please select a day...</option></select><br />
        <input type="checkbox" name="recurring" id="recurring" value="1" /><label for="recurring">Automatically reschedule every </label>
        <input type="text" name="weeks" id="weeks" /><label> week(s) until </label>
        <input type="text" id="datepicker2" name="enddate" /><br />
        <input type="submit" value="Next" />
    </form>
    <script>
        $(function() {
            
            var events = <?php echo json_encode($events); ?>;
            var bathtime = <?php echo ceil($bathtime/15)*15; ?>;
            var totaltime = <?php echo $totaltime; ?>;
            var slottime = <?php echo $slottime; ?>;
            var groomers = <?php echo json_encode($groomers); ?>;
            var tiers = <?php echo $tiers['Tiers']; ?>;
            var size = "<?php echo $_SESSION['info']['Size']; ?>";
            var prevstart = <?php echo $prevstart ?>;
            var previd = <?php echo $_SESSION['info']['previd'] ?>;
            
            // Set open and close times for each day of the week
            var openclose = <?php echo $_SESSION['Hours']; ?>;
                        
            var timeslots = Array();
            var selectedinfo = Array();
            
            // Function that given a day and a groomer ID returns the number
            // of dogs scheduled for that groomer that day.
            function getcount(groomer, today) {
                
                var count = 0;
                var id;
    
                for(var i = 0; i < events.length; i++) {

                    if(events[i]['PetID'] == -1) {
                        continue;
                    }

                    var event = moment(events[i]['StartTime']*1000);
                                        
                    if((!event.isSame(today, "day")) && events[i]['Recurring'] == 1 && (events[i]['EndDate'] != null ? today.isSameOrBefore(moment(events[i]['EndDate']*1000), "day") : 1)) {
                        while(event.isSameOrBefore(today, "day")) {
                            event.add(events[i]['RecInterval'], 'weeks'); // Add the number of weeks as an interval
                            if(event.isSame(today, "day")) {
                                break;
                            }
                        }
                    }

                    if(event.isSame(today, "day")) {
                        id = events[i]['GroomerID'];
                        if(id == groomer) {
                            count++;
                        }
                    }
                }
                
                
                
                return count;
                
            }
            
            // Function that given a day and a groomer ID returns an array
            // of minutes which that groomer has available that day, or
            // false if there are none.
            function getavailable(id, today) {
                var todayminutes = Array();
                var largedogs = 0;

                // Fill array with minutes spa is open today
                switch(today.day()) {
                    // Tuesday and Wednesday
                    case 2:
                    case 3:
                        var i = openclose[2]['open'];
                        while(i <= openclose[2]['close']) {
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Thursday and Friday
                    case 4:
                    case 5:
                        var i = openclose[4]['open'];
                        while(i <= openclose[4]['close']) {
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Saturday (0900 - 1500)
                    case 6:
                        var i = openclose[6]['open'];
                        while(i <= openclose[6]['close']) {
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;
                }
                
                var now = moment.tz("<?php echo $_SESSION['Timezone']; ?>");
                                                
                // Remove time that has already passed
                if(now.format("M/D/Y") == today.format("M/D/Y")) {
                    var currenttime = now.hours() * 60 + now.minutes();
                    var pastminutes = Array();
                    var i = todayminutes[0];
                    while(i < Math.ceil(currenttime/15)*15) {
                        pastminutes.push(i);
                        i++;
                    }
                    todayminutes = todayminutes.filter(function(minute) {
                        return pastminutes.indexOf(minute) === -1;
                    });
                }
                                
                for(var i = 0; i < events.length; i++) {
                    
                    var event = moment.utc(events[i]['StartTime']*1000);
                    
                    if((!event.isSame(today, "day")) && events[i]['Recurring'] == 1 && (events[i]['EndDate'] != null ? today.isSameOrBefore(moment(events[i]['EndDate']*1000), "day") : 1)) {
                        while(event.isSameOrBefore(today, "day")) {
                            event.add(events[i]['RecInterval'], 'weeks'); // Add the number of weeks as an interval
                            if(event.isSame(today, "day")) {
                                break;
                            }
                        }
                    }
                    
                    if(event.isSame(today, "day")) {
                                                
                        if(previd == events[i]['ID'] && prevstart == event.unix()) {
                            continue;
                        }
                        
                        // Remove scheduled events' times from today's minutes array
                        var startminutes = (event.hours() * 60) + (event.minutes());
                        startminutes += Math.ceil(events[i]['BathTime']/15)*15;
                        var endminutes = Math.ceil(events[i]['GroomTime']/15)*15 + startminutes - 1;
                        var eventminutes = Array();
                        while(startminutes <= endminutes) {
                            eventminutes.push(startminutes);
                            startminutes++;
                        }
                        
                        // If we're scheduling a large dog, and the event is a large dog, add the event to the total number of large dogs for this groomer
                        if((size == "L" || size == "XL") && (events[i]['Size'] == "L" || events[i]['Size'] == "XL") && events[i]['GroomerID'] == id) {
                            largedogs++;
                        }
                        
                        
                        
                        // If the event is this groomer's, remove it from the available time
                        if(events[i]['GroomerID'] == id) {
                            todayminutes = todayminutes.filter(function(minute) {
                                return eventminutes.indexOf(minute) === -1;
                            });
                        }
                    }
                }
                
                if(largedogs >= 2) {
                    return false;
                }
                
                if(todayminutes.length) {
                    return todayminutes;
                }
                else {
                    return false;
                }
            }
            
            // Function that given an array of available minutes, and a time,
            // will return a numbered 2D array of slots where the time fits,
            // or false if there is none. Each slot has indexes 'start',
            // 'end', and 'length'
            function slotfits(todayminutes, time) {
                if(time == 0) {
                    return false;
                }
                var slots = Array();
                var step = 0;
                slots[step] = Array();
                slots[step]['start'] = todayminutes[0];
                slots[step]['length'] = 0;
                for(var i = 1; i < todayminutes.length; i++) {
                    slots[step]['length'] += 1;
                    if(todayminutes[i] != todayminutes[i-1] + 1) {
                        slots[step]['end'] = todayminutes[i-1] + 1;
                        step += 1;
                        slots[step] = Array();
                        slots[step]['start'] = todayminutes[i];
                        slots[step]['length'] = 1;
                    }
                    
                    if(i == todayminutes.length - 1) {
                        slots[step]['end'] = todayminutes[i];
                    }
                }
                
                var index = Array();

                for(var i = 0; i < slots.length; i++) {
                    if(slots[i]['length'] < time) {
                        index.push(i);
                    }
                }
                // Remove in reverse order so it doesn't mess up the indices
                for(var i = index.length - 1; i >= 0; i--) {
                    slots.splice(index[i], 1);
                }

                if(slots.length > 0) {
                    return slots;
                }
                else {
                    return false;
                }
            }
            
            // Given an array of slots with start and end times, this splits them up into
            // slots the length of the groom time, beginning every 15 minutes.
            // If, because of the timing of the big slot, there are no little slots, return false.
            function splitslots(bigslots, time, today) {
                var littleslots = Array();
                var dayindex = today.day();
                var now = moment.tz("<?php echo $_SESSION['Timezone']; ?>");
                var currenttime = now.hours() * 60 + now.minutes();
                var index = -1;
            
                
                // i = Every big slot
                for(var i = 0; i < bigslots.length; i++) {
                    // j = The beginning of every little slot (in 15 minute increments)
                    for(var j = 0; j < bigslots[i]['length']; j += 15) {
                        // If the current 15 minute start time would make the end time greater than the
                        // slot's end time, don't add it to littleslots (because it's too long)
                        if(bigslots[i]['start'] + j + time > bigslots[i]['end']) {
                           break;
                        }
                        // If the current 15 minute start time would push the bathing time
                        // before opening, don't add it (but check the next start time)
                        if(bigslots[i]['start'] + j - bathtime < openclose[dayindex]['open']) {
                            continue;
                        }
                        // If the current 15 minute start time would push the bathing time
                        // before the current time (on the current day), don't add it
                        if(now.format("M/D/Y") == today.format("M/D/Y") && bigslots[i]['start'] + j - bathtime < currenttime) {
                            continue;
                        }
                        // If the current 15 minute start time would push the end time past
                        // closing, don't add it.
                        if(bigslots[i]['start'] + j + time > openclose[dayindex]['close']) {
                            break;
                        }
                        
                        index++;
                        littleslots[index] = Array();
                        littleslots[index]['start'] = bigslots[i]['start'] + j - bathtime;
                        // Add 30 minutes to the pickup time
                        littleslots[index]['end'] = littleslots[index]['start'] + time + bathtime + 30;
                        // If adding 30 minutes pushed us past closing time, set pickup time to closing time.
                        if(littleslots[index]['end'] > openclose[dayindex]['close']) {
                            littleslots[index]['end'] = openclose[dayindex]['close'];
                        }
                    }
                }
                
                if(littleslots.length > 0) {
                    return littleslots;
                }
                else {
                    return false;
                }
            }
            
            function disableDay(today) {
                today = moment(today);
                today.add(today.utcOffset(), 'minutes');
                today.utc();
                var dayindex = today.day();
                var allslots = Array();
                    
                // Disable Sundays and Mondays
                if(dayindex == 0 || dayindex == 1) {
                    return true;
                }

                var index = today.unix();
                timeslots[index] = Array();
                timeslots[index]['slots'] = Array();
                timeslots[index]['groomers'] = Array();
                
                for(var i = 0; i < groomers.length; i++) {
                    allslots[i] = Array();
                    allslots[i]['groomer'] = groomers[i]['ID'];
                    allslots[i]['count'] = getcount(groomers[i]['ID'], today);
                    allslots[i]['seniority'] = groomers[i]['Seniority'];
                    allslots[i]['slots'] = Array();
                    var minutes = getavailable(groomers[i]['ID'], today);
                    if(!minutes) {
                        continue;
                    }

                    // Correct slottime for each groomer's tier. If doing so makes it 0 or less, make it 15.
                    var temp = groomers[i]['Tier'];
                    var groomerslottime = slottime + parseInt(tiers[temp][size]);
                    if(slottime > 0 && groomerslottime <= 0) {
                        groomerslottime = 15;
                    }
                    if(slottime == 0) {
                        groomerslottime = 0;
                    }
                    var slots = slotfits(minutes, groomerslottime);
                    if(slots) {
                        
                        var littleslots = splitslots(slots, groomerslottime, today);
                        if(!littleslots) {
                            continue;
                        }
                        
                        allslots[i]['slots'] = littleslots.slice(0);
                    }
                }
                                
                // Remove groomers with no available time
                for(var i = allslots.length - 1; i >= 0; i--) { 
                    if(!allslots[i]['slots'].length) {
                        allslots.splice(i, 1);
                        continue;
                    }
                }
                
                var sortedslots = Array();
                
                // Remove slots from allslots in order and put them into sortedslots
                while(allslots.length > 0) {
                    var selected = 0;
                    var curcount = allslots[selected]['count'];
                    for(var i = 0; i < allslots.length; i++) {
                        if(allslots[i]['count'] > curcount) {
                            selected = i;
                            curcount = allslots[i]['count'];
                        }
                        else if(allslots[i]['count'] == curcount) {
                            if(allslots[i]['Seniority'] > allslots[selected]['Seniority']) {
                                selected = i;
                                curcount = allslots[i]['count'];
                            }
                        }
                    }
                    sortedslots.push(allslots[selected]);
                    allslots.splice(selected, 1);
                }
                
                if(!sortedslots.length) {
                    return true;
                }
                                
                for(var i = 0; i < sortedslots[0]['slots'].length; i++) {
                    timeslots[index]['slots'].push(sortedslots[0]['slots'][i]);
                    timeslots[index]['groomers'].push(sortedslots[0]['groomer']);
                }
                
                for(var i = 1; i < sortedslots.length; i++) {
                    for(var j = 0; j < sortedslots[i]['slots'].length; j++) {
                        reset:
                        for(var k = 0; k < timeslots[index]['slots'].length; k++) {
                            if(k == timeslots[index]['slots'].length - 1) {
                                if(sortedslots[i]['slots'][j]['start'] > timeslots[index]['slots'][k]['start']) {
                                    timeslots[index]['slots'].push(sortedslots[i]['slots'][j]);
                                    timeslots[index]['groomers'].push(sortedslots[i]['groomer']);
                                    break reset;
                                }
                                else if(sortedslots[i]['slots'][j]['start'] == timeslots[index]['slots'][k]['start']) {
                                    timeslots[index]['slots'].splice(k, 1, sortedslots[i]['slots'][j]);
                                    timeslots[index]['groomers'].splice(k, 1, sortedslots[i]['groomer']);
                                    break reset;
                                }
                                else {
                                    timeslots[index]['slots'].splice(k, 0, sortedslots[i]['slots'][j]);
                                    timeslots[index]['groomers'].splice(k, 0, sortedslots[i]['groomer']);
                                    break reset;
                                }
                            }
                            if(sortedslots[i]['slots'][j]['start'] > timeslots[index]['slots'][k]['start']) {
                                continue reset;
                            }
                            else if(sortedslots[i]['slots'][j]['start'] == timeslots[index]['slots'][k]['start']) {
                                timeslots[index]['slots'].splice(k, 1, sortedslots[i]['slots'][j]);
                                timeslots[index]['groomers'].splice(k, 1, sortedslots[i]['groomer']);
                                break reset;
                            }
                            else {
                                timeslots[index]['slots'].splice(k, 0, sortedslots[i]['slots'][j]);
                                timeslots[index]['groomers'].splice(k, 0, sortedslots[i]['groomer']);
                                break reset;
                            }
                        }
                    }
                }
                
                
                
                if(timeslots[index]['slots'].length > 0) {
                    return false;
                }
                else {
                    return true;
                }
            }
            

            var picker = new Pikaday({
                field: document.getElementById('datepicker'),
                format: 'MM/DD/YYYY',
                minDate: new Date(),
                disableDayFn: function(today) {
                    return disableDay(today);
                },
                onSelect: function(date) {
                    
                    date = moment(date);
                    date.add(date.utcOffset(), 'minutes');
                    date.utc();
                    
                    var options = $("#slot");
                    options.empty();
                    
                    today = date.unix();
                    
                
                    for(var i = 0; i < timeslots[today]['slots'].length; i++) {
                        var start = timeslots[today]['slots'][i]['start'];
                        var end = timeslots[today]['slots'][i]['end'];
                        var startmin = start % 60;
                        var starthour = (start - startmin) / 60;
                        var endmin = end % 60;
                        var endhour = (end - endmin) / 60;
                        var s = "AM";
                        var e = "AM";
                        if(starthour >= 12) {
                            starthour = starthour - 12;
                            s = "PM";
                        }
                        if(starthour == 0) {
                            starthour = 12;
                        }
                        if(endhour >= 12) {
                            endhour = endhour - 12;
                            e = "PM";
                        }
                        if(endhour == 0) {
                            endhour = 12;
                        }
                        
                        var groomer = timeslots[today]['groomers'][i];
                        
                        
                        var timestamp = today + (start*60);
                        options.append($("<option />").val(groomer + "-" + timestamp + "-" + starthour + ":" + (startmin < 10 ? "0" + startmin : startmin) + " " + s + "-" + endhour + ":" + (endmin < 10 ? "0" + endmin : endmin) + " " + e).prop('selected', (timestamp == prevstart ? true : false)).text(starthour + ":" + (startmin < 10 ? "0" + startmin : startmin) + " " + s + " - " + endhour + ":" + (endmin < 10 ? "0" + endmin : endmin) + " " + e));
                    }
                }
            });
            
            var picker2 = new Pikaday({
                field: document.getElementById('datepicker2'),
                format: 'MM/DD/YYYY'
            });
            
            $('#datepicker').change(function() {
                var mindate = new Date($('#datepicker').val());
                picker2.setMinDate(mindate);
                picker2.gotoDate(mindate);
            });
            
            $('#weeks').change(function() {
                $('#recurring').prop('checked', true);
            });
            
            $('#datepicker2').change(function() {
                $('#recurring').prop('checked', true);
            });
            
            if(prevstart != false) {
                var prevDate = moment.unix(prevstart).startOf('day');
                disableDay(new Date(prevDate.format()));
                picker.setMinDate(false);
                picker.setMoment(prevDate);
            }
        });
    </script>
<?php
    }
    else if($_SESSION['page'] == 'summary') {
        
        $_SESSION['page'] = 'submit';
        
        if(empty($_POST['date'])) {
            echo "<p>We're sorry, but there was no date entered.</p>";
            goto finish;
        }
        else {
            $slotinfo = explode("-", $_POST['slot']);
            if(!is_numeric($slotinfo[1])) {
                echo "<p>We're sorry, but the timestamp could not be verified.</p>";
                goto finish;
            }
            else {
                $stmt = $database->prepare("SELECT Name FROM Users WHERE ID = :ID");
                $stmt->bindValue(':ID', $slotinfo[0]);
                $stmt->execute();
                $groomername = $stmt->fetch();
                if(empty($groomername)) {
                    echo "<p>We're sorry, but the groomer ID could not be found.</p>";
                    goto finish;
                }
                else {
                    
                    $_SESSION['info']['timestamp'] = $slotinfo[1];
                    $_SESSION['info']['groomer'] = $slotinfo[0];
                    
                    $stmt = $database->prepare("SELECT Name, Vaccines2, Age FROM Pets WHERE ID = :ID");
                    $stmt->bindValue(':ID', $_SESSION['info']['pet']);
                    $stmt->execute();
                    $res = $stmt->fetch();
                    $petname = $res['Name'];
                    
                    $vaccines = json_decode($res['Vaccines2'], true);
                    $rabies = false;
                    $distemper = false;
                    $parvo = false;
                    $recvaccine = false;
                
                    $age = date("Y") - intval($res['Age']);
                    

                    if($age < 3) {
                        if($_SESSION['info']['timestamp'] > strtotime("tomorrow", strtotime($vaccines['Distemper']))) {
                            $distemper = true;
                        }
                        if($_SESSION['info']['timestamp'] > strtotime("tomorrow", strtotime($vaccines['Parvo']))) {
                            $parvo = true;
                        }
                    }
                    if($_SESSION['info']['timestamp'] > strtotime("tomorrow", strtotime($vaccines['Rabies']))) {
                        $rabies = true;
                    }
                    
                    if(!empty($_POST['recurring']) && $_POST['recurring'] != 1) {
                        echo "<p>We're sorry, but the checkbox couldn't be verified.</p>";
                        goto finish;
                    }
                    
                    $notrecurring = false;
                    if(!empty($_POST['recurring'])) {
                        
                        $_SESSION['info']['Recurring'] = $_POST['recurring'];
                        
                        if(!is_numeric($_POST['weeks']) || $_POST['weeks'] < 1) {
                            echo "<p>We're sorry but the number of weeks must be a positive number.</p>";
                            goto finish;
                        }
                        
                        $_SESSION['info']['RecInterval'] = $_POST['weeks'];
                        
                        // If enddate was not set, set it to one year from the start date
                        if(!empty($_POST['enddate'])) {
                            $enddate = DateTime::createFromFormat('!m/d/Y', $_POST['enddate']);
                            if($enddate === false) {
                                echo "<p>The end date was incorrectly formatted.</p>";
                                goto finish;
                            }
                        }
                        else {
                            $oneyearend = $_SESSION['info']['timestamp'] + 31536000;
                            $enddate = DateTime::createFromFormat('U', $oneyearend);
                        }
                        
                        
                        
                        
                        
                        // Set the enddate to the end of the day (local) of the last instance
                        // In the for loop, compare the current instance to the END of the enddate day
                        for($i = $_SESSION['info']['timestamp']; $i < strtotime("tomorrow", $enddate->getTimestamp()) - 1; $i += $_SESSION['info']['RecInterval']*604800) {
                            $lastinstance = $i;
                        }
                        $_SESSION['info']['EndDate'] = strtotime("tomorrow", $lastinstance) - 1;
                        
                        $stmt = $database->prepare("SELECT * FROM Scheduling WHERE PetID != -1 AND GroomerID = :ID");
                        $stmt->bindValue(":ID", $_SESSION['info']['groomer']);
                        $stmt->execute();
                        $events = $stmt->fetchAll();
                        
                        // Check if making this recurring will conflict with anything. $i is the timestamp of each reccurance
                        for($i = $_SESSION['info']['timestamp'] + $_SESSION['info']['RecInterval']*604800; $i < $_SESSION['info']['EndDate']; $i += $_SESSION['info']['RecInterval']*604800) {
                            foreach($events as $event) {
                                if($event['Recurring'] != 1) {
                                    // Check if the current recurrance is between the start and end of each non-recurring event
                                    if(($i >= $event['StartTime'] && $i < $event['StartTime'] + $event['GroomTime'] * 60) || ($i + $_SESSION['info']['GroomTime'] * 60 > $event['StartTime'] && $i + $_SESSION['info']['GroomTime'] * 60 <= $event['StartTime'] + $event['GroomTime'] * 60)) {
                                        $finalevent = $i - $_SESSION['info']['RecInterval']*604800; // Make the last instance the one before it conflicted
                                        break 2;
                                    }
                                }
                                else {
                                    // For recurring events, check every recurrence of what we're scheduling with every recurrence of each scheduled event
                                    for($k = $event['StartTime']; $k < $event['EndDate']; $k += $event['RecInterval']*604800) {
                                        
                                        // Check if the current recurrance of our event ($i) overlaps with the current recurrance of the stored event ($k)
                                        if(($i >= $k && $i < $k + $event['GroomTime'] * 60) || ($i + $_SESSION['info']['GroomTime'] * 60 > $k && $i + $_SESSION['info']['GroomTime'] * 60 <= $k + $event['GroomTime'] * 60)) {
                                            $finalevent = $i - $_SESSION['info']['RecInterval']*604800; // Make the last instance the one before it conflicted
                                            break 3;
                                        }
                                    }
                                }
                            }
                        }

                        if(isset($finalevent)) {
                            if($finalevent <= $_SESSION['info']['timestamp']) {
                                $notrecurring = true;
                                goto notrec;
                            }
                            $_SESSION['info']['EndDate'] = strtotime("tomorrow", $finalevent) - 1;
                        }
                        
                        for($i = $_SESSION['info']['timestamp']; $i < $_SESSION['info']['EndDate']; $i += $_SESSION['info']['RecInterval']*604800) {
                            if($age < 3) {
                                if($i > strtotime("tomorrow", strtotime($vaccines['Distemper']))) {
                                    $distemper = true;
                                    $recvaccine = true;
                                }
                                if($i > strtotime("tomorrow", strtotime($vaccines['Parvo']))) {
                                    $parvo = true;
                                    $recvaccine = true;
                                }
                            }
                            if($i > strtotime("tomorrow", strtotime($vaccines['Rabies']))) {
                                $rabies = true;
                                $recvaccine = true;
                            }
                        }
                        
                    }
                    else {
                        notrec:
                        $_SESSION['info']['Recurring'] = $_SESSION['info']['RecInterval'] = $_SESSION['info']['EndDate'] = 0;
                    }
                    
                    if($rabies) {
                        echo '<p class="error">WARNING: The Rabies vaccine expires on ' . $vaccines['Rabies'] . ' which is before ';
                        if($recvaccine) {
                            echo 'the last scheduled appointment.</p>';
                        }
                        else {
                            echo 'the scheduled appointment.</p>';
                        }
                    }
                    if($parvo) {
                        echo '<p class="error">WARNING: The Parvo vaccine expires on ' . $vaccines['Parvo'] . ' which is before ';
                        if($recvaccine) {
                            echo 'the last scheduled appointment.</p>';
                        }
                        else {
                            echo 'the scheduled appointment.</p>';
                        }
                    }
                    if($distemper) {
                        echo '<p class="error">WARNING: The Distemper vaccine expires on ' . $vaccines['Distemper'] . ' which is before ';
                        if($recvaccine) {
                            echo 'the last scheduled appointment.</p>';
                        }
                        else {
                            echo 'the scheduled appointment.</p>';
                        }
                    }
                    
                    $groomername = $groomername['Name'];

                    switch($_SESSION['info']['package']) {
                        case 1:
                            $package = "Basic Bath";
                            break;
                        case 2:
                            $package = "Basic Spa";
                            break;
                        case 3:
                            $package = "Signature Bath";
                            break;
                        case 4:
                            $package = "Signature Spa";
                            break;
                    }
                    
                    if(!empty($_SESSION['info']['services'])) {
                        $services = array();

                        foreach($_SESSION['info']['services'] as $service) {
                            $stmt = $database->prepare("SELECT Name FROM Services WHERE ID = :ID");
                            $stmt->bindValue(':ID', $service);
                            $stmt->execute();
                            $temp = $stmt->fetch();
                            $services[] = $temp['Name'];
                        }
                    }

                    $start = $slotinfo[2];
                    $end = $slotinfo[3];

                    echo "<h1>Summary</h1>";

                    echo "<table><tr>";
                    echo "<td>Pet: </td><td>" . $petname . "</td></tr><tr>";
                    echo "<td>Package: </td><td>" . $package . "</td></tr><tr>";
                    echo "<td>Services: </td><td>";
                    if(!empty($_SESSION['info']['services'])) {
                        echo "<ul>";
                        foreach($services as $service) {
                            echo "<li>" . $service . "</li>";
                        }
                        echo "</ul>";
                    }
                    else {
                        echo "None";
                    }
                    echo "</ul></td></tr><tr>";
                    echo "<td>Price: </td><td>$" . $_SESSION['info']['Price'] . "</td></tr><tr>";
                    echo "<td>Groomer: </td><td>" . $groomername . "</td></tr><tr>";
                    echo "<td>Date: </td><td>" . $_POST['date'] . "</td></tr><tr>";
                    echo "<td>Dropoff Time: </td><td>" . $start . "</td></tr><tr>";
                    echo "<td>Pickup Time: </td><td>" . $end . "</td></tr></table>";
                    
                    echo "<br />";
                    
                    
                    if(!empty($_POST['recurring']) && !$notrecurring) {
                        $enddate = new DateTime('@'.$_SESSION['info']['EndDate']);
                        echo "<h3>Automatic Rescheduling:</h3>";
                        echo "<p>Your pet is automatically scheduled at this time every " . $_SESSION['info']['RecInterval'] . " week(s). Your final appointment will be on " . $enddate->format("l, m/d/Y");
                    }
                    if(isset($finalevent) && !$notrecurring) {
                        echo '<p class="error">NOTE: The ending date is different from what you set due to a conflict.<br />';
                        echo 'You can manually re-schedule for dates after ' . $enddate->format("m/d/Y") . '</p>';
                    }
                    if($notrecurring) {
                        echo '<p class="error">NOTE: The first recurrance had a conflict, so this appointment will not be stored as a recurring appointment.</p>';
                    }
                    
                    echo '<form action="schedule.php" method="post" id="confirm">';
                    echo '<input type="hidden" name="confirm" value="1" />';
                    echo '<button type="submit" form="confirm">Confirm</button>';
                    echo '</form>';
                    echo '<form id="cancel" action="schedule.php" method="get">';
                    echo '<input type="hidden" name="id" value="' . $_SESSION['info']['client'] . '" />';
                    echo '<button type="submit" form="cancel">Cancel</button>';
                    echo '</form>';
                    goto finish;
                }
            }
        }
    }
    else if($_SESSION['page'] == 'submit' && intval($_POST['confirm']) == 1) {
        if($_SESSION['info']['previd'] != -1) {
            $stmt = $database->prepare("SELECT * FROM Scheduling WHERE ID = :ID");
            $stmt->bindValue(':ID', $_SESSION['info']['previd']);
            $stmt->execute();
            $prevevent = $stmt->fetch();
            if($prevevent['Recurring'] == 1) {
                $difference = abs($_SESSION['info']['prevstart'] - $prevevent['StartTime']);
                // It's not the initial event
                if($difference > 0) {
                    // It's the last instance
                    if($_SESSION['info']['prevstart'] + $prevevent['RecInterval']*604800 > $prevevent['EndDate']) {
                        $enddate = $prevevent['EndDate'] - $prevevent['RecInterval']*604800;
                        $stmt = $database->prepare("UPDATE Scheduling SET EndDate = :EndDate WHERE ID = :ID");
                        $stmt->bindValue(':EndDate', $enddate);
                        $stmt->bindValue(':ID', $prevevent['ID']);
                        $stmt->execute();
                    }
                    // It's not the last instance
                    else {
                        $totalevents = 0;
                        for($i = $prevevent['StartTime']; $i < $prevevent['EndDate']; $i += $prevevent['RecInterval']*604800) {
                            $totalevents++;
                        }
                        $prevevents = $difference / ($prevevent['RecInterval']*604800);
                        $newtotal = $totalevents - $prevevents;

                        $enddate = $prevevent['EndDate'] - $newtotal*$prevevent['RecInterval']*604800;
                        $stmt = $database->prepare("UPDATE Scheduling SET EndDate = :EndDate WHERE ID = :ID");
                        $stmt->bindValue(':EndDate', $enddate);
                        $stmt->bindValue(':ID', $prevevent['ID']);
                        $stmt->execute();

                        $starttime = $_SESSION['info']['prevstart'] + $prevevent['RecInterval']*604800;
                        $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring, RecInterval, EndDate, Package, Services, Price) VALUES (:PetID, :StartTime, :GroomTime, :BathTime, :TotalTime, :GroomerID, :Recurring, :RecInterval, :EndDate, :Package, :Services, :Price)');
                        $stmt->bindValue(':PetID', $prevevent['PetID']);
                        $stmt->bindValue(':StartTime', $starttime);
                        $stmt->bindValue(':GroomTime', $prevevent['GroomTime']);
                        $stmt->bindValue(':BathTime', $prevevent['BathTime']);
                        $stmt->bindValue(':TotalTime', $prevevent['TotalTime']);
                        $stmt->bindValue(':GroomerID', $prevevent['GroomerID']);
                        $stmt->bindValue(':Recurring', $prevevent['Recurring']);
                        $stmt->bindValue(':RecInterval', $prevevent['RecInterval']);
                        $stmt->bindValue(':EndDate', $prevevent['EndDate']);
                        $stmt->bindValue(':Package', $prevevent['Package']);
                        $stmt->bindValue(':Price', $prevevent['Price']);
                        $stmt->bindValue(':Services', $prevevent['Services']);
                        $stmt->execute();


                    }
                }
                // It's the inital event
                else {
                    $starttime = $_SESSION['info']['prevstart'] + $prevevent['RecInterval']*604800;
                    $stmt = $database->prepare("UPDATE Scheduling SET StartTime = :StartTime WHERE ID = :ID");
                    $stmt->bindValue(':StartTime', $starttime);
                    $stmt->bindValue(':ID', $prevevent['ID']);
                    $stmt->execute();
                }
            }
            else {
                $stmt = $database->prepare("DELETE FROM Scheduling WHERE ID = :ID");
                $stmt->bindValue(':ID', $prevevent['ID']);
                $stmt->execute(); 
            }
        }
        
        $_SESSION['page'] = null;
        $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring, RecInterval, EndDate, Package, Services, Price) VALUES (:PetID, :StartTime, :GroomTime, :BathTime, :TotalTime, :GroomerID, :Recurring, :RecInterval, :EndDate, :Package, :Services, :Price)');
        $stmt->bindValue(':PetID', $_SESSION['info']['pet']);
        $stmt->bindValue(':StartTime', $_SESSION['info']['timestamp']);
        $stmt->bindValue(':GroomTime', $_SESSION['info']['GroomTime']);
        $stmt->bindValue(':BathTime', $_SESSION['info']['BathTime']);
        $stmt->bindValue(':TotalTime', $_SESSION['info']['TotalTime']);
        $stmt->bindValue(':GroomerID', $_SESSION['info']['groomer']);
        $stmt->bindValue(':Recurring', $_SESSION['info']['Recurring']);
        $stmt->bindValue(':RecInterval', $_SESSION['info']['RecInterval']);
        $stmt->bindValue(':EndDate', $_SESSION['info']['EndDate']);
        $stmt->bindValue(':Package', $_SESSION['info']['package']);
        $stmt->bindValue(':Price', $_SESSION['info']['Price']);
        (!empty($_SESSION['info']['services']) ? $stmt->bindValue(':Services', json_encode($_SESSION['info']['services'])) : $stmt->bindValue(':Services', NULL));
        $stmt->execute();
        
        echo "<p>Your pet has been scheduled. Thanks!</p>";
        goto finish;
    }
    
    finish:
?>    
</body>
</html>