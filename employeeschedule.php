<?php
include "include/header.php";

// Only allow Admins to schedule employees.
if($_SESSION['authenticated'] < 5) {
    header("Location: " . $http . $_SERVER['HTTP_HOST'] . "/login.php?redirect=" . $redirect);
    die();
}

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
    
?>

    <form>
        <input type="text" id="week" />
        <table id="schedule" style="display: none">
            <tr id="days"><td>Sunday</td><td>Monday</td><td>Tuesday</td><td>Wednesday</td><td>Thursday</td><td>Friday</td><td>Saturday</td></tr>
        </table>
    </form>
    
<script>

function updateTable() {
    var week = $("#week").val().split(' - ');
    var day = moment(new Date(week[0]));
    $("#days").children('td').each(function(index) {
        $(this).text(day.format('dddd, MM/DD/YYYY'));
        day.add(1, 'days');
    });
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
        updateTable();
        $("#schedule").css("display", "block");
    });
});  
</script>
</body>
</html>