<?php
include "include/header.php";

// Only allow logged in users to schedule.
if($_SESSION['authenticated'] < 1) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

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
    <meta charset="UTF-8">
    <title>Scheduling</title>
</head>
<body>

<?php
    
    include 'include/menu.php';
    
    if(!empty($_POST['confirm'])) {
        $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring, RecInterval, EndDate, Package, Services) VALUES (:PetID, :StartTime, :GroomTime, :BathTime, :TotalTime, :GroomerID, :Recurring, :RecInterval, :EndDate, :Package, :Services)');
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
        (!empty($_SESSION['info']['services']) ? $stmt->bindValue(':Services', json_encode($_SESSION['info']['services'])) : $stmt->bindValue(':Services', NULL));
        $stmt->execute();
        
        echo "<p>Your pet has been scheduled. Thanks!</p>";
    }
    else if(!empty($_POST['slot'])) {
        if(empty($_POST['date'])) {
            echo "<p>We're sorry, but there was no date entered.</p>";
        }
        else {
            $slotinfo = explode("-", $_POST['slot']);
            if(!is_numeric($slotinfo[1])) {
                echo "<p>We're sorry, but the timestamp could not be verified.</p>";
            }
            else {
                $stmt = $database->prepare("SELECT Name FROM Users WHERE ID = :ID");
                $stmt->bindValue(':ID', $slotinfo[0]);
                $stmt->execute();
                $groomername = $stmt->fetch();
                if(empty($groomername)) {
                    echo "<p>We're sorry, but the groomer ID could not be found.</p>";
                }
                else {
                    
                    $_SESSION['info']['timestamp'] = $slotinfo[1];
                    $_SESSION['info']['groomer'] = $slotinfo[0];
                    
                    if(!empty($_POST['recurring']) && $_POST['recurring'] != 1) {
                        echo "<p>We're sorry, but the checkbox couldn't be verified.</p>";
                        goto finish;
                    }
                    
                    if(!empty($_POST['recurring'])) {
                        
                        $_SESSION['info']['Recurring'] = $_POST['recurring'];
                        
                        if(!is_numeric($_POST['weeks']) || $_POST['weeks'] < 1) {
                            echo "<p>We're sorry but the number of weeks must be a positive number.</p>";
                            goto finish;
                        }
                        
                        $_SESSION['info']['RecInterval'] = $_POST['weeks'];
                        
                        $enddate = DateTime::createFromFormat('!m/d/Y', $_POST['enddate']);
                        if($enddate === false) {
                            echo "<p>The end date was incorrectly formatted.</p>";
                            goto finish;
                        }
                        
                        
                        
                        // Set the enddate to the end of the day (local) of the last instance
                        // In the for loop, compare the current instance to the END of the enddate day
                        for($i = $_SESSION['info']['timestamp']; $i < strtotime("tomorrow", $enddate->getTimestamp()) - 1; $i += $_SESSION['info']['RecInterval']*604800) {
                            $lastinstance = $i;
                        }
                        $_SESSION['info']['EndDate'] = strtotime("tomorrow", $lastinstance) - 1;
                        
                        $stmt = $database->query("SELECT * FROM Scheduling WHERE PetID > 0");
                        $events = $stmt->fetchAll();

                        // Check if making this recurring will conflict with anything. $i is the timestamp of each reccurance
                        for($i = $_SESSION['info']['timestamp']; $i < $_SESSION['info']['EndDate']; $i += $_SESSION['info']['RecInterval']*604800) {
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
                            $_SESSION['info']['EndDate'] = strtotime("tomorrow", $finalevent) - 1;
                        }
                    }
                    else {
                        $_SESSION['info']['Recurring'] = $_SESSION['info']['RecInterval'] = $_SESSION['info']['EndDate'] = 0;
                    }
                    
                    $groomername = $groomername['Name'];

                    $stmt = $database->prepare("SELECT Name FROM Pets WHERE ID = :ID");
                    $stmt->bindValue(':ID', $_SESSION['info']['pet']);
                    $stmt->execute();
                    $petname = $stmt->fetch();
                    $petname = $petname['Name'];

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
                    echo "<td>Groomer: </td><td>" . $groomername . "</td></tr><tr>";
                    echo "<td>Date: </td><td>" . $_POST['date'] . "</td></tr><tr>";
                    echo "<td>Dropoff Time: </td><td>" . $start . "</td></tr><tr>";
                    echo "<td>Pickup Time: </td><td>" . $end . "</td></tr></table>";
                    
                    echo "<br />";
                    
                    
                    if(!empty($_POST['recurring'])) {
                        $enddate = new DateTime('@'.$_SESSION['info']['EndDate']);
                        echo "<h3>Automatic Rescheduling:</h3>";
                        echo "<p>Your pet is automatically scheduled at this time every " . $_SESSION['info']['RecInterval'] . " week(s). Your final appointment will be on " . $enddate->format("l, m/d/Y");
                    }
                    if(isset($finalevent)) {
                        echo '<p style="color: red">NOTE: The ending date is different from what you set due to a conflict.<br />';
                        echo 'You can manually re-schedule for dates after ' . $enddate->format("m/d/Y") . '</p>';
                    }
                    
                    echo '<form action="schedule.php" method="post" id="confirm">';
                    echo '<input type="hidden" name="confirm" value="1" />';
                    echo '<button type="submit" form="confirm">Confirm</button>';
                    echo '</form>';
                    echo '<form id="cancel" action="schedule.php" method="get">';
                    echo '<input type="hidden" name="id" value="' . $_SESSION['info']['client'] . '" />';
                    echo '<button type="submit" form="cancel">Cancel</button>';
                    echo '</form>';
                }
            }
        }
    }
    
    else if(!empty($_POST['package'])) {
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
            $stmt = $database->prepare("SELECT * FROM Scheduling WHERE GroomerID = :ID");
            $stmt->bindValue(':ID', $_POST['groomer']);
            $stmt->execute();
            $events = $stmt->fetchAll();
            if(!empty($events)) {
                $stmt = $database->prepare("SELECT ID, Seniority, Tier FROM Users WHERE ID = :ID");
                $stmt->bindValue(':ID', $_POST['groomer']);
                $stmt->execute();
                $groomers = $stmt->fetchAll();
            }
            else {
                $stmt = $database->query("SELECT * FROM Scheduling");
                $events = $stmt->fetchAll();
                $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2");
                $groomers = $stmt->fetchAll();
            }
        }
        else {
            $stmt = $database->query("SELECT * FROM Scheduling");
            $events = $stmt->fetchAll();
            $stmt = $database->query("SELECT ID, Seniority, Tier FROM Users WHERE Access = 2");
            $groomers = $stmt->fetchAll();
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
            var totaltime = <?php echo $totaltime; ?>;
            var slottime = <?php echo $slottime; ?>;
            var groomers = <?php echo json_encode($groomers); ?>;
            var tiers = <?php echo $tiers['Tiers']; ?>;
            var size = "<?php echo $_SESSION['info']['Size']; ?>";
            
            var timeslots = Array();
            var selectedinfo = Array();
            
            // Offset of the Salon's timezone from UTC.
            var offset = moment.tz.zone("<?php echo $_SESSION['info']['Timezone']; ?>").offset(moment())*60;
            
            // Offset of user's local timezone from UTC.
            var localoffset = new Date().getTimezoneOffset();
            
            // Function that given a day and array of groomers
            // returns the ID of the groomer with the fewest scheduled
            // dogs that day. If two groomers tie, it will return the most senior.
            function pickgroomer(today, groomers) {
                
                var id;
                
                today = moment(today);
    
                for(var i = 0; i < events.length; i++) {

                    if(events[i]['PetID'] == -1) {
                        continue;
                    }

                    var event = moment((events[i]['StartTime'] - (offset - localoffset*60)) * 1000);
                    if(events[i]['Recurring'] == 1 && (events[i]['EndDate'] != null ? today.isSameOrBefore(moment((events[i]['EndDate'] - (offset - localoffset*60)) * 1000), "day") : 1)) {
                        while(event.isSameOrBefore(today, "day")) {
                            event.add(events[i]['RecInterval'], 'weeks'); // Add the number of weeks as an interval
                            if(event.isSame(today, "day")) {
                                break;
                            }
                        }
                    }

                    if(event.isSame(today, "day")) {
                        id = events[i]['GroomerID'];
                        for(var j = 0; j < groomers.length; j++) {
                            if(!groomers[j].hasOwnProperty('count')) {
                                groomers[j]['count'] = 0;
                            }
                            if(groomers[j]['ID'] == id) {
                                groomers[j]['count']++;
                            }
                        }
                    }
                }
                
                groomer = groomers[0];
                
                if(groomers.length > 1) {
                    for(var i = 1; i < groomers.length; i++) {
                        if(groomers[i]['count'] < groomer['count']) {
                            groomer = groomers[i];
                        }
                        else if(groomers[i]['count'] == groomer['count']) {
                            if(groomers[i]['Seniority'] < groomer['Seniority']) {
                                groomer = groomers[i];
                            }
                        }
                    }
                }
                
                return groomer['ID'];
                
            }
            
            // Function that given a day and a groomer ID returns an array
            // of minutes which that groomer has available that day, or
            // false if there are none.
            function getavailable(id, today) {
                var todayminutes = Array();

                // Fill array with minutes spa is open today
                switch(today.getDay()) {
                    // Tuesday and Wednesday (0900 - 1700)
                    case 2:
                    case 3:
                        var i = 540; // 0900 in minutes
                        while(i <= 1020) { // 1700 in minutes
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Thursday and Friday (0800 - 1800)
                    case 4:
                    case 5:
                        var i = 480; // 0900 in minutes
                        while(i <= 1080) { // 1800 in minutes
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Saturday (0900 - 1500)
                    case 6:
                        var i = 540; // 0900 in minutes
                        while(i <= 900) { // 1500 in minutes
                            if(i == 780) {
                                i = 810; // Add a break from 1:00 - 1:30
                            }
                            todayminutes.push(i);
                            i++;
                        }
                        break;
                }

                for(var i = 0; i < events.length; i++) {
                    
                    if(events[i]['GroomerID'] != id) {
                        continue;
                    }
                    
                    // Creating a date from a UTC timestamp, returns a local date. Subtract the offset
                    // to counteract this.
                    var eventstart = new Date((events[i]['StartTime'] - (offset - localoffset*60)) * 1000);


                    if(events[i]['Recurring'] == 1 && (events[i]['EndDate'] != null ? today <= new Date((events[i]['EndDate'] - (offset - localoffset*60)) * 1000) : 1)) {
                        while(eventstart <= today) {
                            eventstart = new Date(eventstart.getTime() + (604800000 * events[i]['RecInterval'])); // Add the number of weeks as an interval
                            if(eventstart.toDateString() === today.toDateString()) {
                                break;
                            }
                        }
                    }

                    if(eventstart.toDateString() === today.toDateString()) {
                        // Remove scheduled events' times from today's minutes array
                        var startminutes = (eventstart.getHours() * 60) + (eventstart.getMinutes());
                        var endminutes = Math.ceil(events[i]['GroomTime']/15)*15 + startminutes - 1;
                        var eventminutes = Array();
                        while(startminutes <= endminutes) {
                            eventminutes.push(startminutes);
                            startminutes++;
                        }
                        todayminutes = todayminutes.filter(function(minute) {
                            return eventminutes.indexOf(minute) === -1;
                        });
                    }
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

                for(var i = 0; i < slots.length; i++) {
                    if(slots[i]['length'] < time) {
                        var index = slots.indexOf(i);
                        slots.splice(index, 1);
                    }
                }
                if(slots.length > 0) {
                    return slots;
                }
                else {
                    return false;
                }
            }
            

            $('#datepicker').pikaday({
                format: 'MM/DD/YYYY',
                minDate: new Date(),
                disableDayFn: function(today) {
                    
                    // Disable Sundays and Mondays
                    if(today.getDay() == 0 || today.getDay() == 1) {
                        return true;
                    }
                    
                    // Check availability of each groomer
                    var available = false;
                    
                    var index = today.getTime();
                    timeslots[index] = Array();
                    
                    for(var i = 0; i < groomers.length; i++) {
                        var minutes = getavailable(groomers[i]['ID'], today);
                        if(!minutes) {
                            continue;
                        }
                        
                        // Correct slottime for each groomer's tier
                        var temp = groomers[i]['Tier'];
                        var groomerslottime = slottime + tiers[temp][size];
                        var slots = slotfits(minutes, groomerslottime);
                        if(slots) {
                            
                            timeslots[index].push(Array());
                            var last = timeslots[index].length - 1;
                            timeslots[index][last]['groomer'] = groomers[i];
                            timeslots[index][last]['slots'] = slots;
                            available = true;
                        }
                    }
                    if(!available) {
                        return true;
                    }
                },
                onSelect: function(date) {
                    
                    // timeslots[today]: An array of groomers available today
                    // timeslots[today][i]: One of today's groomers (inside a for loop). This has
                        // an array at ['groomer'] with all the groomer's info, and an array
                        // at ['slots'] with the available slots for that groomer
                    // littleslots[index]: An array of timeslots the size of the current event to the nearest 15 minutes, taken from a single open period
                    
                    today = date.getTime();
                    var availablegroomers = Array();
                    
                    for(var i = 0; i < timeslots[today].length; i++) {
                        availablegroomers.push(timeslots[today][i]['groomer']);
                    }
                    
                    var groomer = pickgroomer(date, availablegroomers);
                    
                    var options = $("#slot");
                    options.empty();
                    var littleslots = Array();
                    var x = Math.ceil(totaltime/15); // The number of 15 minute intervals in the slot being scheduled
                    
                    // Break up the available slots into slots the size of the event.
                    // i = every groomer
                    for(var i = 0; i < timeslots[today].length; i++) {
                        
                        if(timeslots[today][i]['groomer']['ID'] != groomer) {
                            continue;
                        }
                        var index = -1;
                        // j = every slot for the chosen groomer
                        for(var j = 0; j < timeslots[today][i]['slots'].length; j++) {
                            for(var k = 0; k < timeslots[today][i]['slots'][j]['length']; k += 15) {
                                // If the current 15 minute start time would make the end time greater than the
                                // slot's end time, don't add it to littleslots (because it's too long)
                                // We're also adding an additional 30 minutes to the end of each slot for pickup
                                if(!(timeslots[today][i]['slots'][j]['start'] + k + x*15 + 30 > timeslots[today][i]['slots'][j]['end'])) {
                                    index++;
                                    littleslots[index] = Array();
                                    littleslots[index]['start'] = timeslots[today][i]['slots'][j]['start'] + k;
                                    littleslots[index]['end'] = littleslots[index]['start'] + x*15 + 30;
                                }
                            }
                        }
                    }
                    
                    for(var i = 0; i < littleslots.length; i++) {
                        var start = littleslots[i]['start'];
                        var end = littleslots[i]['end'];
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
                        
                        
                        var timestamp = (start*60) + (today/1000) + (offset - localoffset*60);
                        options.append($("<option />").val(groomer + "-" + timestamp + "-" + starthour + ":" + (startmin < 10 ? "0" + startmin : startmin) + " " + s + "-" + endhour + ":" + (endmin < 10 ? "0" + endmin : endmin) + " " + e).text(starthour + ":" + (startmin < 10 ? "0" + startmin : startmin) + " " + s + " - " + endhour + ":" + (endmin < 10 ? "0" + endmin : endmin) + " " + e));
                    }
                }
            });
            
            $('#datepicker2').pikaday({
                format: 'MM/DD/YYYY',
                minDate: new Date()
            });
        });
    </script>
<?php
    }
    else if(!empty($_POST['pet']) || (!empty($_GET['pet']) && $_SESSION['authenticated'] > 1)) {
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

            $_SESSION['info']['Time'] = json_decode($pet['Time'], true);
            $_SESSION['info']['Size'] = $res['Size'];
            $_SESSION['info']['GroomPrice'] = $res['GroomPrice'];
            $_SESSION['info']['BathPrice'] = $res['BathPrice'];
            
            $stmt = $database->query("SELECT * FROM Services");
            $services = $stmt->fetchAll();
            if(!empty($services)) {
                $_SESSION['info']['pet'] = $pet['ID'];
                $stmt = $database->query("SELECT Name, ID, Tier FROM Users WHERE Access = 2");
                $groomers = $stmt->fetchAll();
                
                $stmt = $database->query("SELECT SigUpcharge, SigPrice FROM Globals");
                $globals = $stmt->fetch();
                
                echo '<form action="schedule.php" method="post">';
                
                echo '<label for="package">Select Package: </label><select id="package" name="package">';
                echo '<option value="1">Basic Bath</option>';
                echo '<option value="2">Basic Spa</option>';
                echo '<option value="3">Signature Bath</option>';
                echo '<option value="4">Signature Spa</option>';
                echo '</select><br />';
                
                echo '<p>Select which services you would like to schedule: </p>';
                foreach($services as $service) {
                    echo '<input type="checkbox" name="services[]" id="' . $service['ID'] . '" value="' . $service['ID'] . '" />';
                    echo '<label for="' . $service['ID'] . '">' . $service['Name'] . '</label><br />';
                }

                echo '<label for="groomer">Preferred Groomer: </label><select id="groomer" name="groomer">';
                echo '<option value="NULL">Any</option>';
                foreach($groomers as $groomer) {
                    echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $pet['PreferredGroomer']) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                }
                echo '</select><br />';
                echo '<input type="submit" value="Next" />';
                echo '</form>';
            }
            else {
                echo '<p>We\'re sorry, there are no services stored yet.</p>';
            }
        }
        else {
            echo '<p>We\'re sorry, you submitted an invalid pet ID.</p>';
        }
    }
    else {
        
        // Only allow employees or admins to schedule other people's pets
        if(!empty($_GET['id']) && $_SESSION['authenticated'] > 1) {
            $id = $_GET['id'];
        }
        else {
            $id = $_SESSION['ID'];
        }

        $_SESSION['info'] = array();
        $stmt = $database->prepare("SELECT * FROM Pets WHERE OwnedBy = :ID");
        $stmt->bindValue(':ID', $id);
        $stmt->execute();
        $pets = $stmt->fetchAll();
        
        $stmt = $database->query("SELECT Timezone FROM Globals");
        $timezone = $stmt->fetch();
        $_SESSION['info']['Timezone'] = $timezone['Timezone'];

        if(!empty($pets)) {
            
            $_SESSION['info']['client'] = $id;
            
            echo '<form action="schedule.php" method="post">';
            echo '<label for="pet">Select which pet you would like to schedule: </label>';
            echo '<select id="pet" name="pet">';
            foreach($pets as $pet) {
                echo '<option value="' . $pet['ID'] . '">' . $pet['Name'] . '</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="Next" />';
            echo '</form>';
        }
        else {
            echo '<p>We\'re sorry, that client has no pets.</p>';
        }
    }
    
    finish:
?>    
</body>
</html>