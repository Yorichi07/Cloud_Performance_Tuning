<?php

$con = mysqli_connect('localhost','root','','mediahub_db');

$icons = [

    'image/gif' => '<i class="bi bi-filetype-gif class_39"></i>',
];

function query($query){

    global $con;

    $result = mysqli_query($con, $query);
    if($result){
        if(!is_bool($result) && mysqli_num_rows($result)>0){
            $res = [];
            while($row = mysqli_fetch_assoc($result)){
                $res[] = $row;
            }
            return $res;    
        }
    }
    return false;
}

function get_date($date)
{
    return date ("d/m/y", strtotime($date));
}