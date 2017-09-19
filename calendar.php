<?php
    include "include/header.php";

    switch($_SESSION['authenticated']) {
        case 0:
            header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php");
            die();
        break;
            
        case 1:
            //header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/calendar.php");
            //die();
        break;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Index</title>
    <link rel='stylesheet' href='css/fullcalendar.css' />
</head>
<body>

<?php 
    include "include/menu.php";
    
    $stmt = $database->query("SELECT * FROM Scheduling WHERE PetID != -1");
    $events = $stmt->fetchAll();
    
    $stmt = $database->query("SELECT ID, Name FROM Pets");
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    
?>
    
    <div id="calendar"></div>

<?php include "include/footer.php"; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src='js/moment.min.js'></script>
<script src="js/moment-timezone.min.js"></script>
<script src='js/fullcalendar.min.js'></script>
<script>
    $(document).ready(function() {
        
        // Offset of the Salon's timezone from UTC.
        var offset = moment.tz.zone("<?php echo $_SESSION['info']['Timezone']; ?>").offset(moment())*60;

        // Offset of user's local timezone from UTC.
        var localoffset = new Date().getTimezoneOffset();
        
        var events = <?php echo json_encode($events); ?>;
        
        var pets = <?php echo json_encode($pets); ?>;
        
        var objects = Array();
        
        // Create array of event objects for fullCalendar
        for(var i = 0; i < events.length; i++) {
            
            var index = events[i]['PetID'];
            
            var event = {
                id: events[i]['ID'],
                start: (events[i]['StartTime'] - offset) * 1000,
                end: (events[i]['StartTime'] + (events[i]['TotalTime'] * 60) - offset) * 1000,
                title: pets[index][0]['Name']
            };


            objects.push(event);
        }

        $('#calendar').fullCalendar({
            events: objects,
            header: {
                left:   'title',
                center: '',
                right:  'today month listDay prev,next'
            },
            dayClick: function(date, jsEvent, view) {
                if (view.name === "month") {
                    $('#calendar').fullCalendar('gotoDate', date);
                    $('#calendar').fullCalendar('changeView', 'listDay');
                }
            },
            eventClick: function(event, jsEvent, view) {
                if(view.name === "month") {
                    $('#calendar').fullCalendar('gotoDate', event.start);
                    $('#calendar').fullCalendar('changeView', 'listDay');
                }
            }
        });

    });
</script>
</body>
</html>