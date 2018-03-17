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

$stmt = $database->query("SELECT Timezone FROM Globals");
$timezone = $stmt->fetch();

$_SESSION['Timezone'] = $timezone['Timezone'];
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
    
    $stmt = $database->query("SELECT * FROM Scheduling WHERE PetID != -1 ORDER BY ID");
    $events = $stmt->fetchAll();
    
    foreach($events as $key => $event) {
        $event['TwoPeople'] = $pets[$event['PetID']][0]['TwoPeople'];
        $events[$key]['URL'] = $event['ID'];
        if(!empty($event['Services'])) {
            $services = array();
            $event['Services'] = json_decode($event['Services'], true);
            foreach($event['Services'] as $service) {
                $stmt = $database->prepare("SELECT ID, Name, Type FROM Services WHERE ID = :ID");
                $stmt->bindValue(":ID", $service);
                $stmt->execute();
                $serv = $stmt->fetch();
                array_push($services, $serv);
            }
            $events[$key]['Services'] = json_encode($services);
        }
        $stmt = $database->prepare("SELECT Name FROM Users WHERE ID = :ID");
        $stmt->bindValue(":ID", $event['GroomerID']);
        $stmt->execute();
        $groomer = $stmt->fetch();
        $events[$key]['Groomer'] = $groomer['Name'];
    }
    
?>
    
    <div id="calendar"></div>

<?php include "include/footer.php"; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/menu.js"></script>
<script src='js/moment.min.js'></script>
<script src="js/moment-timezone.min.js"></script>
<script src='js/fullcalendar.min.js'></script>
<script src='js/fancybox.min.js'></script>
<script>
    $(document).ready(function() {
        
        function nl2br(str) {       
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ '<br />' +'$2');
        }
        
        var oldevents = <?php echo json_encode($events); ?>;
        
        var pets = <?php echo json_encode($pets); ?>;
        
        var clients = <?php echo json_encode($clients); ?>;
        
        var objects = Array();
                
        events = Array();
                
        // Split recurring events
        for(var i = 0; i < oldevents.length; i++) {
            if(oldevents[i]['Recurring'] == 1) {
                var firststart = moment.unix(oldevents[i]['StartTime']).utc();
                for(var j = oldevents[i]['StartTime']; j < oldevents[i]['EndDate']; j += oldevents[i]['RecInterval']*604800) {
                    var temp = oldevents[i];
                    temp['StartTime'] = firststart.unix();
                    temp['URL'] = temp['URL'] + "-" + firststart.unix();
                    // Make sure we're doing a deep push instead of just the reference
                    events.push(JSON.parse(JSON.stringify(temp)));
                    firststart.add(oldevents[i]['RecInterval'], "weeks");
                }
            }
            else {
                events.push(oldevents[i]);
            }
        }
                        
        // Create array of event objects for fullCalendar
        for(var i = 0; i < events.length; i++) {
            
            var index = events[i]['PetID'];
            var index2 = pets[index][0]['OwnedBy'];
                        
            var event = {
                id: events[i]['ID'],
                start: events[i]['StartTime'] * 1000,
                end: (events[i]['StartTime'] + (events[i]['TotalTime'] * 60)) * 1000,
                title: pets[index][0]['Name'] + ' - ' + events[i]['Groomer'],
                TwoPeople: events[i]['TwoPeople'],
                warnings: pets[index][0]['Info'],
                notes: pets[index][0]['Notes'],
                services: JSON.parse(events[i]['Services']),
                phone: clients[index2][0]['Phone'],
                url: events[i]['URL'],
                view: 'all',
                owner: clients[index2][0]['FirstName'] + ' ' + clients[index2][0]['LastName'],
                petID: events[i]['PetID'],
                ownerID: pets[index][0]['OwnedBy'],
                package: events[i]['Package'],
                groomer: events[i]['GroomerID'],
                groomerName: events[i]['Groomer'],
                starttime: events[i]['StartTime'],
                recurring: events[i]['Recurring'],
                recinterval: events[i]['RecInterval'],
                enddate: events[i]['EndDate'],
                price: events[i]['Price']
            };
            
            


            objects.push(event);
        }
                
        function switchView(view) {
            
            var start;
            var end;
            
            switch(view) {
                case 'all':
                    for(var i = 0; i < objects.length; i++) {            
                        objects[i].start = objects[i].starttime * 1000;
                        objects[i].end = (objects[i].starttime + (events[i]['TotalTime'] * 60)) * 1000;
                        objects[i].view = 'all';
                    }
                    break;
                case 'groom':
                    for(var i = 0; i < objects.length; i++) {
                        objects[i].start = (objects[i].starttime + Math.ceil(events[i]['BathTime']/15)*15 * 60) * 1000;
                        objects[i].end = (objects[i].starttime + events[i]['TotalTime'] * 60) * 1000;
                        objects[i].view = 'groom';
                    }
                    break;
                case 'bath':
                    for(var i = 0; i < events.length; i++) {
                        objects[i].start = objects[i].starttime * 1000;
                        if(objects[i].TwoPeople == 1) {
                            objects[i].end = (objects[i].starttime + events[i]['TotalTime'] * 60) * 1000;
                        }
                        else {
                            objects[i].end = (objects[i].starttime + Math.ceil(events[i]['BathTime']/15)*15 * 60) * 1000;
                        }
                        objects[i].view = 'bath';
                    }
                    break;
            }
            
            $('#calendar').fullCalendar('removeEventSource', objects);
            $('#calendar').fullCalendar( 'addEventSource', objects);
        }

        $('#calendar').fullCalendar({
            events: objects,
            eventLimit: true,
            timezone: "UTC",
            showNonCurrentDates: false,
            lazyFetching: false,
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
                    return false;
                }
                else if(view.name === "listDay") {
                    if($(jsEvent.target).attr('type') == 'submit') {
                        var form = $(jsEvent.target).parents('form:first');
                        form.submit();
                        return false;
                    }
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
                    if(event.view != "bath") {
                        element.children().last().append('\
                            <form action="schedule.php" method="get" class="edit">\
                            <input type="hidden" value="' + event.petID + '" name="pet" />\
                            <input type="hidden" value="' + event.starttime + '" name="starttime" />\
                            <input type="hidden" value="' + event.id + '" name="eventid" />\
                            <input type="submit" value="Edit" />\
                            </form>\
                        ');
                    }
                    var services = 'No Services<br />';
                    var bathservices = '';
                    var groomservices = '';
                    var sigservices = '';
                    if(event.services != null) {
                        services = '';
                        for(var i = 0; i < event.services.length; i++) {
                            switch(event.services[i]['Type']) {
                                case 0: // Signature
                                    sigservices += event.services[i]['Name'];
                                    sigservices += '<br />';
                                    break;
                                case 1: // Bath
                                    bathservices += event.services[i]['Name'];
                                    bathservices += '<br />';
                                    break;
                                case 2: // Groom
                                    groomservices += event.services[i]['Name'];
                                    groomservices += '<br />';
                                    break;
                            }
                        }
                        switch(event.view) {
                            case 'all':
                                if(sigservices != '') {
                                    services += 'Signature Services:<br />';
                                    services += sigservices;
                                }
                                if(bathservices != '') {
                                    services += 'Bath Services:<br />';
                                    services += bathservices;
                                }
                                if(groomservices != '') {
                                    services += 'Groom Services:<br />';
                                    services += groomservices;
                                }
                                break;
                            case 'groom':
                                if(sigservices != '') {
                                    services += 'Signature Services:<br />';
                                    services += sigservices;
                                }
                                if(groomservices != '') {
                                    services += 'Groom Services:<br />';
                                    services += groomservices;
                                }
                                break;
                            case 'bath':
                                if(sigservices != '') {
                                    services += 'Signature Services:<br />';
                                    services += sigservices;
                                }
                                if(bathservices != '') {
                                    services += 'Bath Services:<br />';
                                    services += bathservices;
                                }
                                break;
                        }
                    }
                    
                    if(event.view == "bath") {
                        element.children().last().append('<br />' + services);
                    }
                    
                    var links = event.title.split(" - ");
                    links[0] = '<a href="viewpet.php?id=' + event.petID + '">' + links[0] + '</a>';
                    links[1] = '<a href="viewclient.php?id=' + event.ownerID + '">' + event.owner + '</a>';
                    
                    event.nameLink = links[0];
                    event.ownerLink = links[1];
                    
                    element.children().last().append('<div style="display: none" id="' + event.url + '">Pet Name: ' + event.nameLink + '<br />Groomer: ' + event.groomerName + '<br />Warnings: ' + nl2br(event.warnings) + '<br />Notes: ' + nl2br(event.notes) + '<br />Price: ' + '&#36;' + event.price + '<br />' + ((event.TwoPeople == 1) ? 'Requires two people<br />' : '<br />') + services + 'Owner: ' + event.ownerLink + '<br />Phone: ' + event.phone + '</div>');
                }
            }
        });

    });
</script>
</body>
</html>