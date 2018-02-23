<?php
if(!defined('DIRECT')) die(); // Don't allow this file to be called directly

?>

<input type="checkbox" id="menubox" />
<nav class="clearfix">
    <label for="menubox">
        <img src="img/menu.png" id="menuicon" />
    </label>
    <ul>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'current' : ''); ?>"><a href="calendar.php">Calendar</a></li>
        <li><a href="#">Add New</a>
            <ul>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'newuser.php' ? 'current' : ''); ?>"><a href="newuser.php">User</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'newclient.php' ? 'current' : ''); ?>"><a href="newclient.php">Client</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'newservice.php' ? 'current' : ''); ?>"><a href="newservice.php">Service</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'newbreed.php' ? 'current' : ''); ?>"><a href="newbreed.php">Breed</a></li>
            </ul>
        </li>
        <li><a href="#">View</a>
            <ul>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'viewclient.php' ? 'current' : ''); ?>"><a href="viewclient.php">Clients</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'viewpet.php' ? 'current' : ''); ?>"><a href="viewpet.php">Pets</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'viewservice.php' ? 'current' : ''); ?>"><a href="viewservice.php">Services</a></li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'viewbreed.php' ? 'current' : ''); ?>"><a href="viewbreed.php">Breeds</a></li>
            </ul>
        </li>
        <?php if($_SESSION['authenticated'] == 5) { ?>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'employeeschedule.php' ? 'current' : ''); ?>"><a href="employeeschedule.php">Schedule Employees</a></li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'current' : ''); ?>"><a href="settings.php">Global Settings</a></li>
        <?php } ?>
    </ul>
    <div id="rightmenu">
        <form action="search.php" method="post" id="menusearch">
            <input type="text" name="search" id="search" placeholder="Search...">
            <input type="submit" value="Go">
        </form>
        <a href="newpass.php">Reset Password</a>
        <?php if($_SESSION['authenticated'] != 0) { echo '<a href="login.php?redirect=' . $redirect . '&logout=1">Logout</a>'; } ?>
    </div>
</nav>