<?php
include "include/header.php";

// Only allow logged in users to schedule.
if($_SESSION['authenticated'] < 1) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

// Only allow employees or admins to schedule other people's pets
if(!empty($_GET['id']) && $_SESSION['authenticated'] > 1) {
    $id = $_GET['id'];
}
else {
    $id = $_SESSION['ID'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="js/pikaday.js"></script>
    <script src="js/pikaday.jquery.js"></script>
    <link rel="stylesheet" type="text/css" href="css/pikaday.css" />
    <meta charset="UTF-8">
    <title>Scheduling</title>
</head>
<body>

<?php
    if(!empty($_POST['package'])) {
        if($_POST['package'] == 1 || $_POST['package'] == 2 || $_POST['package'] == 3 || $_POST['package'] == 4) {
            $_SESSION['info']['package'] = $_POST['package'];
        }
        else {
            echo '<p>Your package selection data is corrupted.</p>';
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
                $stmt = $database->prepare("SELECT ID, MaxDogs FROM Users WHERE ID = :ID");
                $stmt->bindValue(':ID', $_POST['groomer']);
                $stmt->execute();
                $groomers = $stmt->fetchAll();
            }
            else {
                $stmt = $database->query("SELECT * FROM Scheduling");
                $events = $stmt->fetchAll();
                $stmt = $database->query("SELECT * FROM Users WHERE Access = 2");
                $groomers = $stmt->fetchAll();
            }
        }
        else {
            $stmt = $database->query("SELECT * FROM Scheduling");
            $events = $stmt->fetchAll();
            $stmt = $database->query("SELECT * FROM Users WHERE Access = 2");
            $groomers = $stmt->fetchAll();
        }
        
        // Calculate time for current pet
        if($_SESSION['info']['package'] == 1 || $_SESSION['info']['package'] == 3) {
            $time = $_SESSION['info']['BathTime'];
        }
        else {
            $time = $_SESSION['info']['BathTime'] + $_SESSION['info']['GroomTime'];
        }
        
        $servicetime = 0;
        
        foreach($_SESSION['info']['services'] as $service) {
            $stmt = $database->prepare("SELECT Time FROM Services WHERE ID = :ID");
            $stmt->bindValue(':ID', $service);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_NUM);
            $servicetime += $result[0];
        }
        
        $time += $servicetime;
?>
    <form action="schedule.php" method="post">
        <label for="datepicker">Please pick a day to schedule your pet: </label>
        <input type="text" id="datepicker" /><br />
        <label for="slot">Please pick a time slot: </label>
        <select id="slot"><option value="NULL">Please select a day</option></select>
    </form>
    <script>
        $(function() {
            
            var events = <?php echo json_encode($events); ?>;
            var time = <?php echo $time; ?>;
            var groomers = <?php echo json_encode($groomers); ?>;
            
            var timeslots = Array();
            
            // Function that given a day and a groomer ID returns an array of available slots for that groomer
            function getavailable(id, today) {
                var numdogs = 0;
                
                var todayminutes = Array();

                // Fill array with minutes spa is open today
                switch(today.getDay()) {
                    // Tuesday and Wednesday (0900 - 1700)
                    case 2:
                    case 3:
                        var i = 540; // 0900 in minutes
                        while(i <= 1020) { // 1700 in minutes
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Thursday and Friday (0800 - 1800)
                    case 4:
                    case 5:
                        var i = 480; // 0900 in minutes
                        while(i <= 1080) { // 1800 in minutes
                            todayminutes.push(i);
                            i++;
                        }
                        break;

                    // Saturday (0900 - 1500)
                    case 6:
                        var i = 540; // 0900 in minutes
                        while(i <= 900) { // 1500 in minutes
                            todayminutes.push(i);
                            i++;
                        }
                        break;
                }

                for(var i = 0; i < events.length; i++) {
                    
                    if(events[i]['GroomerID'] != id) {
                        continue;
                    }

                    var eventstart = new Date(events[i]['StartTime']*1000);


                    if(events[i]['Recurring'] == 1 && (events[i]['EndDate'] != null ? today <= new Date(events[i]['EndDate']*1000) : 1)) {
                        while(eventstart <= today) {
                            eventstart = new Date(eventstart.getTime() + (604800000 * events[i]['RecInterval'])); // Add the number of weeks as an interval
                            if(eventstart.toDateString() === today.toDateString()) {
                                break;
                            }
                        }
                    }

                    if(eventstart.toDateString() === today.toDateString()) {

                        // Disable days when groomer is off
                        if(events[i]['PetID'] == -1) {
                                return true;
                        }

                        // If groomer already has MaxDogs, disable today
                        numdogs++;
                        if(numdogs >= id['MaxDogs']) {
                            return true;
                        }

                        // Remove scheduled events' times from today's minutes array
                        var startminutes = (eventstart.getUTCHours() * 60) + (eventstart.getUTCMinutes());
                        var endminutes = events[i]['TotalTime'] + startminutes;
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
                
                return todayminutes;
                
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
                        slots[step]['start'] = todayminutes[i] - 1;
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
                minDate: new Date(),
                disableDayFn: function(today) {
                    // Disable Sundays and Mondays
                    if(today.getDay() == 0 || today.getDay() == 1) {
                        return true;
                    }
                    
                    // Check availability of each groomer
                    var available = false;
                    for(var i = 0; i < groomers.length; i++) {
                        var minutes = getavailable(groomers[i]['ID'], today);
                        var slots = slotfits(minutes, time);
                        if(slots) {
                            var index = today.getTime();
                            timeslots[index] = Array();
                            timeslots[index][i] = Array();
                            timeslots[index][i]['groomer'] = groomers[i];
                            
                            //////////CHANGE THIS!!!!!!!!!!!!!!//////////////////
                            timeslots[index][i]['groomer']['Seniority'] = Math.random();
                            //////////CHANGE THIS!!!!!!!!!!!!!!//////////////////
                            
                            
                            timeslots[index][i]['slots'] = slots;
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
                    // littleslots[i][index]: An array of timeslots the size of the current event to the nearest 15 minutes, taken from a single open period
                    
                    
                    today = date.getTime();
                    var options = $("#slot");
                    options.empty();
                    var littleslots = Array();
                    var totalslots = Array();
                    var x = Math.ceil(time/15); // The number of 15 minute intervals of a time
                    
                    // Break up the available slots into slots the size of the event.
                    // i = every groomer
                    for(var i = 0; i < timeslots[today].length; i++) {
                        littleslots[i] = Array();
                        var index = -1;
                        // r = every slot for a given groomer
                        for(var r = 0; r < timeslots[today][i]['slots'].length; r++) {
                            for(var k = 0; k < timeslots[today][i]['slots'][r]['length']; k += 15) {
                                // If the current 15 minute start time would make the end time greater than the
                                // slot's end time, don't add it to littleslots (because it's too long)
                                if(!(timeslots[today][i]['slots'][r]['start'] + k + x*15 > timeslots[today][i]['slots'][r]['end'])) {
                                    index++;
                                    littleslots[i][index] = Array();
                                    littleslots[i][index]['start'] = timeslots[today][i]['slots'][r]['start'] + k;
                                    littleslots[i][index]['end'] = littleslots[i][index]['start'] + x*15;
                                }
                            }
                        }
                    }
                    
                    // Add all of the available times from all groomers into totalslots, ignoring duplicates.
                    for(var i = 0; i < littleslots.length; i++) {
                        for(var j = 0; j < littleslots[i].length; j++) {
                            var unique = true;
                            for(var k = 0; k < totalslots.length; k++) {
                                if(totalslots[k].indexOf(littleslots[i][j]['start']) != -1) {
                                    unique = false;
                                }
                            }
                            if(unique) {
                                totalslots.push(littleslots[i][j]);
                            }
                        }
                    }

                    for(var k = 0; k < totalslots.length; k++) {
                        var start = totalslots[k]['start'];
                        var end = totalslots[k]['end'];
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
                        options.append($("<option />").val(totalslots[k]['start'] + "-" + today).text(starthour + ":" + (startmin < 10 ? "0" + startmin : startmin) + " " + s + " - " + endhour + ":" + (endmin < 10 ? "0" + endmin : endmin) + " " + e));
                    }
                }
            });
        });
    </script>
<?php
    }
    else if(!empty($_POST['pet'])) {
        $stmt = $database->prepare("SELECT * FROM Pets WHERE ID = :ID");
        $stmt->bindValue(':ID', $_POST['pet']);
        $stmt->execute();
        $pet = $stmt->fetch();
        $_SESSION['info']['BathTime'] = $pet['BathTime'];
        $_SESSION['info']['GroomTime'] = $pet['GroomTime'];
        if(!empty($pet)) {
            $stmt = $database->query("SELECT * FROM Services");
            $services = $stmt->fetchAll();
            if(!empty($services)) {
                $_SESSION['info']['pet'] = $pet['ID'];
                $stmt = $database->query("SELECT Name, ID FROM Users WHERE Access = 2");
                $groomers = $stmt->fetchAll();
                
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
                echo '<option value="NULL">None</option>';
                foreach($groomers as $groomer) {
                    echo '<option value="' . $groomer['ID'] . '" ' . (($groomer['ID'] == $pet['PreferredGroomer']) ? 'selected' : '' ) . '>' . $groomer['Name'] . '</option>';
                }
                echo '</select><br />';
                echo '<input type="submit" value="Next" />';
                echo '</form>';
            }
            else {
                echo '<p>We\'re sorry, there are no services stored yet.';
            }
        }
        else {
            echo '<p>We\'re sorry, you submitted an invalid pet ID.';
        }
    }
    else {
        $_SESSION['info'] = array();
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
        }
        else {
            echo '<p>We\'re sorry, that client has no pets.</p>';
        }
        echo '</form>';
    }
?>    
</body>
</html>