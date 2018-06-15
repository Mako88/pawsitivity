<?php
include "include/header.php";

if(!empty($_POST['id']) && !empty($_POST['note'])) {
    $stmt = $database->prepare("UPDATE Scheduling SET Notes = :Notes WHERE ID = :ID");
    $stmt->bindValue(':Notes', $_POST['note']);
    $stmt->bindValue(':ID', $_POST['id']);
    $stmt->execute();
}

?>