<?php
if(!defined('DIRECT')) die(); // Don't allow this file to be called directly

?>

<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <?php if($_SESSION['authenticated'] > 1) { ?>
        <li><a href="newuser.php">New User</a></li>
        <li><a href="newclient.php">New Client</a></li>
        <li><a href="newservice.php">New Service</a></li>
        <li><a href="newbreed.php">New Breed</a></li>
        <li><a href="viewclient.php">View Clients</a></li>
        <li><a href="viewpet.php">View Pets</a></li>
        <li><a href="viewservice.php">View Services</a></li>
        <li><a href="viewbreed.php">View Breeds</a></li>
        <li><a href="calendar.php">View Calendar</a></li>
        <?php } ?>
        <?php if($_SESSION['authenticated'] == 5) { ?>
        <li><a href="employeeschedule.php">Schedule Employees</a></li>
        <?php } ?>
    </ul>
    <?php if($_SESSION['authenticated'] > 1) { ?>
    <form action="search.php" method="post">
        <input type="text" name="search" id="search" placeholder="Search...">
        <input type="submit" value="Go">
    </form>
    <?php } ?>
    <a href="newpass.php">Reset Password</a>
    <?php if($_SESSION['authenticated'] != 0) { echo '<a href="login.php?redirect=' . $redirect . '&logout=1">Logout</a>'; } ?>
</nav>