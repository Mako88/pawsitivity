<?php
include "include/header.php";

// Only allow Admins to schedule employees.
if($_SESSION['authenticated'] < 5) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

$stmt = $database->query("SELECT Timezone, Hours FROM Globals");
$res = $stmt->fetch();
$timezone = $res['Timezone'];
$hours = $res['Hours'];
$hours = json_decode($hours, true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Schedule Employees</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/moment-timezone.min.js"></script>
<script src="js/pikaday.js"></script>
<script src="js/pikaday.jquery.js"></script>
<link rel="stylesheet" type="text/css" href="css/pikaday.css" />
</head>
<body>

<?php
    
include "include/menu.php";
    
    $stmt = $database->query("SELECT ID, Name FROM Users WHERE Access = 2");
    $employees = $stmt->fetchAll();
        
    if(!empty($_POST['schedule']) && is_numeric($_POST['timestamp'])) {
        if(is_array($_POST['schedule'])) {
            foreach($employees as $employee) {
                for($i = 0; $i < 7; $i++) {
                    $stmt = $database->prepare("DELETE FROM Scheduling WHERE GroomerID = :Groomer AND PetID = -1 AND (StartTime >= :Time1 AND StartTime <= :Time2)");
                    $stmt->bindValue(':Groomer', $employee['ID']);
                    $stmt->bindValue(':Time1', intval($_POST['timestamp']) + ($i * 86400));
                    $stmt->bindValue(':Time2', intval($_POST['timestamp']) + ($i * 86400) + 86399);
                    $stmt->execute();
                    if($hours[$i]['open'] == "closed" || $hours[$i]['close'] == "closed") {
                        continue;
                    }
                    if($_POST['schedule'][$employee['ID']][$i]['open'] == "off" || $_POST['schedule'][$employee['ID']][$i]['close'] == "off") {
                        $timestamp = intval($_POST['timestamp']) + ($i * 86400) + ($hours[$i]['open']*60);
                        $length = $hours[$i]['close'] - $hours[$i]['open'];
                        $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring) VALUES (-1, :StartTime, :GroomTime, 0, :TotalTime, :GroomerID, 0)');
                        $stmt->bindValue(':StartTime', $timestamp);
                        $stmt->bindValue(':GroomTime', $length);
                        $stmt->bindValue(':TotalTime', $length);
                        $stmt->bindValue(':GroomerID', $employee['ID']);
                        $stmt->execute();
                    }
                    else {
                        if($_POST['schedule'][$employee['ID']][$i]['open'] - $hours[$i]['open'] != 0) {
                            $timestamp = intval($_POST['timestamp']) + ($i * 86400) + ($hours[$i]['open']*60);
                            $length = $_POST['schedule'][$employee['ID']][$i]['open'] - $hours[$i]['open'];
                            $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring) VALUES (-1, :StartTime, :GroomTime, 0, :TotalTime, :GroomerID, 0)');
                            $stmt->bindValue(':StartTime', $timestamp);
                            $stmt->bindValue(':GroomTime', $length);
                            $stmt->bindValue(':TotalTime', $length);
                            $stmt->bindValue(':GroomerID', $employee['ID']);
                            $stmt->execute();
                        }
                        if($hours[$i]['close'] - $_POST['schedule'][$employee['ID']][$i]['close'] != 0) {
                            $timestamp = intval($_POST['timestamp']) + ($i * 86400) + ($_POST['schedule'][$employee['ID']][$i]['close']*60);
                            $length = $hours[$i]['close'] - $_POST['schedule'][$employee['ID']][$i]['close'];
                            $stmt = $database->prepare('INSERT INTO Scheduling (PetID, StartTime, GroomTime, BathTime, TotalTime, GroomerID, Recurring) VALUES (-1, :StartTime, :GroomTime, 0, :TotalTime, :GroomerID, 0)');
                            $stmt->bindValue(':StartTime', $timestamp);
                            $stmt->bindValue(':GroomTime', $length);
                            $stmt->bindValue(':TotalTime', $length);
                            $stmt->bindValue(':GroomerID', $employee['ID']);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
        else {
            echo '<p>The schedule information was corrupted</p>';
        }
    }
    
    $stmt = $database->query("SELECT * FROM Scheduling WHERE PetID = -1");
    $events = $stmt->fetchAll();
    
?>

    <form action="employeeschedule.php" method="post">
        <input type="text" id="week" />
        <table id="schedule" style="display: none">
            <tr id="days"><td>Groomer</td><td>Sunday</td><td>Monday</td><td>Tuesday</td><td>Wednesday</td><td>Thursday</td><td>Friday</td><td>Saturday</td></tr>
            <?php
                foreach($employees as $employee) {
                    echo '<tr id=' . $employee['ID'] . '><td>' . $employee['Name'] . '</td>';
                    for($i = 0; $i < 7; $i++) {
                        echo '<td class="' . $i . '">';
                        echo '<select class="open" name="schedule[' . $employee['ID'] . '][' . $i . '][open]">';
                        echo '<option value="NULL" selected disabled></option></select>';
                        echo '<select class="close" name="schedule[' . $employee['ID'] . '][' . $i . '][close]">';
                        echo '<option value="NULL" selected disabled></option></select>';
                        echo '</td>';
                    }
                    echo '</tr>';
                }
            ?>
        </table>
        <input type="hidden" id="timestamp" name="timestamp" />
        <input type="submit" value="Save" />
    </form>
    
<script>
    
// Set open and close times for each day of the week
var openclose = <?php echo json_encode($hours); ?>;
var timezone = "<?php echo $timezone; ?>";
    
var timeslots = Array();
for(var i = 0; i < 7; i++) {
    timeslots[i] = Array();
    timeslots[i]['open'] = Array();
    for(var j = openclose[i]['open']; j <= openclose[i]['close'] - 15; j += 15) {
        timeslots[i]['open'].push(j);
    }
    timeslots[i]['close'] = Array();
    for(var j = openclose[i]['open'] + 15; j <= openclose[i]['close']; j += 15) {
        timeslots[i]['close'].push(j);
    }
}

moment.tz.setDefault("America/New_York");
    
var events = <?php echo json_encode($events); ?>;
var employees = <?php echo json_encode($employees); ?>;

function updateTable() {
    var week = $("#week").val().split(' - ');
    var date = week[0].split('/');
    date = date[2] + '-' + date[0] + '-' + date[1] + " 00:00:00";
    var day = moment.tz(date, timezone);
    $("#timestamp").val(day.unix());
    $("#days").children('td').each(function(index) {
        if(index == 0) {
            return;
        }
        $(this).text(day.format('dddd, MM/DD/YYYY'));
        day.add(1, 'days');
    });
    
    day = moment.tz(date, "UTC");
    
    for(var i = 0; i < employees.length; i++) {
        $("#" + employees[i]['ID']).children('td').each(function(index) {
            if(index == 0) {
                return;
            }
            $(this).children(".open").empty();
            $(this).children(".open").append($("<option />").val("off").text("Off"));
            for(var j = 0; j < timeslots[index-1]['open'].length; j++) {
                var time = timeslots[index-1]['open'][j];
                var selected = " ";
                
                for(var k = 0; k < events.length; k++) {
                    if(events[k]['GroomerID'] != employees[i]['ID']) {
                        continue;
                    }
                    if(day.unix() + moment.tz.zone(timezone).offset(moment())*60 + ((index-1) * 86400) + time*60 == events[k]['StartTime'] + events[k]['TotalTime']*60) {
                        selected = " selected ";
                    }
                }
                
                var min = time % 60;
                var hour = (time - min) / 60;
                var s = "AM";
                if(hour >= 12) {
                    hour = hour - 12;
                    s = "PM";
                }
                if(hour == 0) {
                    hour = 12;
                }
                $(this).children(".open").append($("<option" + selected + "/>").val(time).text(hour + ":" + (min < 10 ? "0" + min : min) + " " + s));
            }
            $(this).children(".close").empty();
            $(this).children(".close").append($("<option />").val("off").text("Off"));
            for(var j = 0; j < timeslots[index-1]['close'].length; j++) {
                var time = timeslots[index-1]['close'][j];
                var selected = " ";
                
                for(var k = 0; k < events.length; k++) {
                    if(events[k]['GroomerID'] != employees[i]['ID']) {
                        continue;
                    }
                    if(day.unix() + moment.tz.zone(timezone).offset(moment())*60 + ((index-1) * 86400) + time*60 == events[k]['StartTime']) {
                        selected = " selected ";
                    }
                }
                var min = time % 60;
                var hour = (time - min) / 60;
                var s = "AM";
                if(hour >= 12) {
                    hour = hour - 12;
                    s = "PM";
                }
                if(hour == 0) {
                    hour = 12;
                }
                $(this).children(".close").append($("<option" + selected + "/>").val(timeslots[index-1]['close'][j]).text(hour + ":" + (min < 10 ? "0" + min : min) + " " + s));
            }
        });
    }
}
    
$(function() {
    $("#week").pikaday({
        pickWholeWeek: true,
        onSelect: function (date) {
            var sundayDate = date.getDate() - date.getDay();
            var sunday = new Date(date.setDate(sundayDate));
            var saturday = new Date(date.setDate(sundayDate + 6));
            $("#week").val(sunday.toLocaleDateString() + ' - ' + saturday.toLocaleDateString());
            updateTable();
        }
    });
    
    $("#week").change(function() {
        $("#schedule").css("display", "block");
    });
});  
</script>
</body>
</html>