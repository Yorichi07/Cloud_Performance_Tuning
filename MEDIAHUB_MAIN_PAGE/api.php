<?php
sleep(1.0);

require __DIR__ . '/functions.php';

session_start();
$info = [];
$info['success'] = false;

if($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_POST['data_type']))
{
    $info['data_type'] = $_POST['data_type'];
    
    if($_POST['data_type'] == "upload_files")
    {
        
        $folder = "uploads/";
        if(!file_exists($folder)){
            mkdir($folder,0777,true);
            file_put_contents($folder.".HTACCESS","Options -Indexes"); //it is for preventing others to access our uploads folder in which all uploaded files are present
        }

        foreach ($_FILES as $key => $file) {

            $destination = $folder.time().$file['name'];  //unique naming of destination folder
            if(file_exists($destination)){
                $destination = $folder.time().rand(0,999).$file['name'];
            }
            move_uploaded_file($file['tmp_name'], $destination);

            //save to database
            $file_type = $file['type'];
            $date_created = date("Y-m-d H:i:s");
            $date_updated = date("Y-m-d H:i:s");
            $file_name = $file['name']; 
            $file_path = $destination;
            $file_size = filesize($destination);
            $user_id = 0;

            $query = "insert into mydrive (file_name, file_size, file_path, user_id, file_type, date_created, date_updated) 
            value ('$file_name', '$file_size', '$file_path', '$user_id', '$file_type', '$date_created', '$date_updated')";
            
            query($query); 

            $info['success'] = true;
        }
    }
    elseif($_POST['data_type'] == "get_files")
    {
        $mode=$_POST['mode'];
        switch ($mode) {
            case 'MY DRIVE':
                $query = "select * from mydrive order by id desc limit 30";
                break;
            case 'FAVOURITES':
                $query = "select * from mydrive where favourite = 1 order by id desc limit 30";
                break;
            case 'RECENT':
                $query = "select * from mydrive order by date_updated desc limit 30";
                break;
            case 'TRASH':
                $query = "select * from mydrive where trash = 1 order by id desc limit 30";
                break;

            default:
                $query = "select * from mydrive order by id desc limit 30";
                break;
        }

        $rows = query($query);
        if($rows)
        {
            foreach ($rows as $key => $row) {
                
                $rows[$key]['icon'] = $icons[$row['file_type']] ?? '<i class="bi bi-question-square-fill class_39"></i>';
                $rows[$key]['date_created'] = get_date($row['date_created']);
                $rows[$key]['date_updated'] = get_date($row['date_updated']);
                $rows[$key]['file_size'] = round($row['file_size'] / (1024*1024) , 1) . "Mb";
                
                if($rows[$key]['file_size'] ==  "0Mb"){
                    $rows[$key]['file_size'] = round($row['file_size'] / (1024) , 1) . "kb";   
                }
            }
            $info['rows'] = $rows;
            $info['success'] = true;
        }
    }
}

echo json_encode($info); //we use json_encode to convert an array to string for data transfer

