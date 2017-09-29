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
    <link rel='stylesheet' href='css/fancybox.min.css' />
    <link rel='stylesheet' href='css/styles.css' />
</head>
<body>

<?php 
    include "include/menu.php";
    
    $stmt = $database->query("SELECT * FROM Pets");
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    
    $stmt = $database->query("SELECT ID, FirstName, LastName, Phone FROM Owners");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    
    $stmt = $database->query("SELECT * FROM Scheduling WHERE PetID != -1");
    $events = $stmt->fetchAll();
    $allevents = array();
    
    foreach($events as $event) {
        $services = array();
        if($event['Recurring'] == 1) {
            for($i = $event['StartTime']; $i < $event['EndDate']; $i += $event['RecInterval']*604800) {                      
                $event['StartTime'] = $i;
                $event['TwoPeople'] = $pets[$event['PetID']][0]['TwoPeople'];
                $event['URL'] = $event['ID'] . "-" . $i;
                if(!empty($event['Services'])) {
                    $event['Services'] = json_decode($event['Services'], true);
                    foreach($event['Services'] as $service) {
                        $stmt = $database->prepare("SELECT Name FROM Services WHERE ID = :ID");
                        $stmt->bindValue(":ID", $service);
                        $stmt->execute();
                        $serv = $stmt->fetch();
                        array_push($services, $serv);
                    }
                    $event['Services'] = json_encode($services);
                }
                array_push($allevents, $event);
            }
        }
        else {
            $event['TwoPeople'] = $pets[$event['PetID']][0]['TwoPeople'];
            $event['URL'] = $event['ID'];
            if(!empty($event['Services'])) {
                $event['Services'] = json_decode($event['Services'], true);
                foreach($event['Services'] as $service) {
                    $stmt = $database->prepare("SELECT Name FROM Services WHERE ID = :ID");
                    $stmt->bindValue(":ID", $service);
                    $stmt->execute();
                    $serv = $stmt->fetch();
                    array_push($services, $serv);
                }
                $event['Services'] = json_encode($services);
            }
            array_push($allevents, $event);
        }
    }
    
?>
    
    <div id="calendar"></div>

<?php include "include/footer.php"; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src='js/moment.min.js'></script>
<script src="js/moment-timezone.min.js"></script>
<script src='js/fullcalendar.min.js'></script>
<script src='js/fancybox.min.js'></script>
<script>
    $(document).ready(function() {
        
        // Offset of the Salon's timezone from UTC.
        var offset = moment.tz.zone("<?php echo $_SESSION['info']['Timezone']; ?>").offset(moment())*60;

        // Offset of user's local timezone from UTC.
        var localoffset = new Date().getTimezoneOffset();
        
        var events = <?php echo json_encode($allevents); ?>;
        
        var pets = <?php echo json_encode($pets); ?>;
        
        var clients = <?php echo json_encode($clients); ?>;
        
        var objects = Array();
        
        // Create array of event objects for fullCalendar
        for(var i = 0; i < events.length; i++) {
            
            var index = events[i]['PetID'];
            var index2 = pets[index][0]['OwnedBy'];
            
            var event = {
                id: events[i]['ID'],
                start: (events[i]['StartTime'] - offset) * 1000,
                end: (events[i]['StartTime'] + (events[i]['TotalTime'] * 60) - offset) * 1000,
                title: pets[index][0]['Name'],
                TwoPeople: events[i]['TwoPeople'],
                warnings: pets[index][0]['Info'],
                notes: pets[index][0]['Notes'],
                services: JSON.parse(events[i]['Services']),
                phone: clients[index2][0]['Phone'],
                url: events[i]['URL']
            };


            objects.push(event);
        }
        
        function switchView(view) {
            
            var start;
            var end;
            
            switch(view) {
                case 'all':
                    for(var i = 0; i < objects.length; i++) {
                        objects[i]['start'] = (events[i]['StartTime'] - offset) * 1000;
                        objects[i]['end'] = (events[i]['StartTime'] + (events[i]['TotalTime'] * 60) - offset) * 1000;
                    }
                    break;
                case 'groom':
                    for(var i = 0; i < events.length; i++) {
                        objects[i]['start'] = (events[i]['StartTime'] - offset + Math.ceil(events[i]['BathTime']/15)*15 * 60) * 1000;
                        objects[i]['end'] = (events[i]['StartTime'] - offset + events[i]['TotalTime'] * 60) * 1000;
                    }
                    break;
                case 'bath':
                    for(var i = 0; i < events.length; i++) {
                        objects[i]['start'] = (events[i]['StartTime'] - offset) * 1000;
                        if(objects[i]['TwoPeople'] == 1) {
                            objects[i]['end'] = (events[i]['StartTime'] - offset + events[i]['TotalTime'] * 60) * 1000;
                        }
                        else {
                            objects[i]['end'] = (events[i]['StartTime'] + (Math.ceil(events[i]['BathTime']/15)*15 * 60) - offset) * 1000;
                        }
                    }
                    break;
            }
            
            $('#calendar').fullCalendar('removeEvents');
            $('#calendar').fullCalendar( 'renderEvents', objects );
        }

        $('#calendar').fullCalendar({
            events: objects,
            customButtons: {
                viewAll: {
                    text: 'All',
                    click: function() {
                        switchView('all')
                    }
                },
                viewGroom: {
                    text: 'Groom',
                    click: function() {
                        switchView('groom')
                    }
                },
                viewBath: {
                    text: 'Bath',
                    click: function() {
                        switchView('bath')
                    }
                }
            },
            header: {
                left:   'title',
                center: '',
                right:  'viewAll viewGroom viewBath today month listDay prev,next'
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
                else if(view.name === "listDay") {
                    $.fancybox.open({
                        src: '#' + event.url,
                        type: 'inline'
                    });
                    return false;
                }
            },
            eventRender: function(event, element) {
                if(jQuery("#calendar").fullCalendar('getView').name === "listDay") {
                    if(event.warnings != null) {
                        element.children().last().append('<span class="warning">' + event.warnings + '</span>');
                    }
                    var services = 'Services:<br />';
                    if(event.services != null) {
                        for(var i = 0; i < event.services.length; i++) {
                            services += event.services[i]['Name'];
                            services += '<br />';
                        }
                    }
                    else {
                        services = 'No Services<br />';
                    }
                    element.children().last().append('<div style="display: none" id="' + event.url + '">Warnings: ' + event.warnings + '<br />Notes: ' + event.notes + '<br />' + ((event.TwoPeople == 1) ? 'Requires two people<br />Phone: ' : '<br />Phone: ') + event.phone + '<br />' + services + '</div>');
                }
            }
        });

    });
</script>
</body>
</html>