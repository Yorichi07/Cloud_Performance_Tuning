<?php

session_start();
require __DIR__ . '/functions.php';

if(!is_logged_in()){
    echo "Please log in to download files";
    die;
}

$id = (int)$_GET['id'] ?? null;
$user_id = (int)$_SESSION['MY_DRIVE_USER']['id'];

$query = "select * from mydrive where user_id='$user_id' && id='$id' limit 1";
$row = query($query);

if($row)
{
    $row=$row[0];
    $file_path = $row['file_path'];
    $file_name = $row['file_name'];

    header('Content-Disposition: attachment; filename="'.$file_name.'"');
    readfile($file_path);
    exit();
}
else{
    echo "Sorry, that file was not found";
}