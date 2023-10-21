<?php

$con = mysqli_connect('localhost','root','','mydrive_db');     //connecting with mysql database from localhost , with root username and no password and mediahub_db as db name

function is_logged_in()
{
    if(!empty($_SESSION['MY_DRIVE_USER']) && is_array($_SESSION['MY_DRIVE_USER']))
	{
        return true;
    }
    return false;
}

function get_icon($type, $ext = null)
{
	$icons = [
		'image/jpeg' => '<i class="bi bi-card-image class_39"></i>',
		'image/gif' => '<i class="bi bi-image class_39"></i>',
		'image/png' => '<i class="bi bi-image class_39"></i>',
		'image/jpg' => '<i class="bi bi-image class_39"></i>',
		'video/x-matroska' => '<i class="bi bi-film class_31"></i>',
		'video/mp4' => '<i class="bi bi-film class_31"></i>',
		'folder' => '<i class="bi bi-folder class_39"></i>',
        'application/pdf' => '<i class="bi bi-filetype-pdf class_39"></i>',
		'application/octet-stream' => 
		[
			'psd'=>'<i class="bi bi-filetype-psd class_39"></i>',
			'pdf'=>'<i class="bi bi-earmark-pdf-fill class_39"></i>',
			'sql'=>'<i class="bi bi-filetype-sql class_39"></i>',
		],
		'text/plain' => '<i class="bi bi-filetype-txt class_39"></i>',
		'application/vnd.openxmlformats-officedocument.word' => '<i class="bi bi-file-pdf class_31"></i>',
	];

	if($type == 'application/octet-stream')
	{
		return $icons[$type][$ext] ?? '<i class="bi bi-question-square-fill class_39"></i>';
	}
	return $icons[$type] ?? '<i class="bi bi-question-square-fill class_39"></i>';
}

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

function query_row($query)
{
	global $con;
	$result = mysqli_query($con, $query);
	if($result)
	{
		if(!is_bool($result) && mysqli_num_rows($result) > 0)
		{
			/*
			$res = [];
			while ($row = mysqli_fetch_assoc($result)) {
				$res[] = $row;
			}

			return $res[0];
			*/
			return mysqli_fetch_assoc($result);
		}
	}
	return false;
}


function get_date($date)
{
    return date ("d/m/y", strtotime($date));
}

function get_drive_space($user_id){
    $query = "select sum(file_size) as sum from mydrive where user_id='$user_id'";
    $row = query($query);
    if($row){
        return $row[0]['sum']; 
    }
    return 0;
}