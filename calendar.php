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
<script src='js/moment.js'></script>
<script src='js/fullcalendar.min.js'></script>
<script>
    $(document).ready(function() {
        
        var events = <?php echo json_encode($events); ?>;
        
        var pets = <?php echo json_encode($pets); ?>;
        
        var objects = Array();
        
        // Create array of event objects for fullCalendar
        for(var i = 0; i < events.length; i++) {
            
            var index = events[i]['PetID'];
            
            var event = {
                id: events[i]['ID'],
                start: events[i]['StartTime'] * 1000,
                end: (events[i]['StartTime'] + (events[i]['TotalTime'] * 60)) * 1000,
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
            }
        });

    });
</script>
</body>
</html>