<?php
sleep(0.9);

session_start();
require __DIR__ . '/functions.php';		

$info = [];    
$info['success'] = false;
$info['LOGGED_IN'] = is_logged_in();
$info['data_type'] = $_POST['data_type'] ?? '';    //POST is used to collect form data after submitting an HTML form with method='post' 

//if not logged in, don't show any files on screen 
if(!$info['LOGGED_IN'] && ($info['data_type'] != 'user_signup' && $info['data_type'] != 'user_login')){		
    echo json_encode($info);
    die;
}


if ($info['LOGGED_IN']) {
    // Check if 'MY_DRIVE_USER' exists in the session
    if (isset($_SESSION['MY_DRIVE_USER'])) {
        $info['username'] = $_SESSION['MY_DRIVE_USER']['username'] ?? 'User';
        $info['drive_occupied'] = get_drive_space($_SESSION['MY_DRIVE_USER']['id']);
    } else {
        // Handle the case where 'MY_DRIVE_USER' is not set in the session
        // You might want to set a default value for username and drive_occupied here
        $info['username'] = 'User';
        $info['drive_occupied'] = 0; // Set an appropriate default value
    }
} else {
    // Handle the case where the user is not logged in
    // You might want to set default values for 'username' and 'drive_occupied' here as well
    $info['username'] = 'User';
    $info['drive_occupied'] = 0; // Set an appropriate default value
}

$info['drive_total'] = 5;   //in GBs
$info['breadcrumbs'] = [];	//sub-folders 

//if request method is post and post data is present
if($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_POST['data_type']))
{
	if($_POST['data_type'] == "user_login")           //user_login
    {
		//save to database
		$email = addslashes($_POST['email']);
		$password = addslashes($_POST['password']);
		
		//validate data
		$errors = [];
		$row = query("select * from users where email = '$email' limit 1");		//limit 1 denotes it will run only one time
		if(!empty($row))		//if $row is not empty means query has return a result
		{
			$row = $row[0];
			if(password_verify($password, $row['password'])){		//using password_verify inbuilt function of php 
				$info['success'] = true;
				$_SESSION['MY_DRIVE_USER'] = $row;		//storing current user in MY_DRIVE_USER, Session is used to store info in var to be used in multiple pages
			}
		}
		$info['errors']['email'] = "Wrong email or password";
    }

	else if($_POST['data_type'] == "user_logout")		//logout
    {
        if(isset($_SESSION['MY_DRIVE_USER'])){		//if MY_DRIVE_USER has a value 
            unset($_SESSION['MY_DRIVE_USER']);		//then remove it 
        }
        $info['success'] = true;    
    }

    else if($_POST['data_type'] == "user_signup")		//signup
    {
        //save to database
        $email = addslashes($_POST['email']);      		//addslashes adds backslash to predefined characters
        $username = addslashes($_POST['username']);
        $password = addslashes($_POST['password']);
        $retype_password = addslashes($_POST['retype_password']);
        $date_created = date("Y-m-d H:i:s");
        $date_updated = date("Y-m-d H:i:s");

        //validate data
        $errors = [];
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){     //filer_var is predefined function, and also FILTER_VALIDATE_EMAIL is a pre-defined function to check if email address is acceptable or not
            $errors['email']="Invalid email address";
        }
        
        if(query("select id from users where email = '$email' limit 1")){   
            $errors['email']="That email address already exists";
        }

        if(empty($username) || !preg_match("/^[0-9a-zA-Z ]+$/",$username)){ 
            $errors['username']="Invalid username";
        }
        
        if (empty($password)){ 
            $errors['password']="A password is required";
        }
        
        if(strlen($password)<8){ 
            $errors['password']="Password must be 8 characters long";
        }

        if($password != $retype_password){ 
            $errors['password']="Password do not match";
        }

        if(empty($errors)){			//no errors then proceed
            $password= password_hash($password,PASSWORD_DEFAULT);		//encrypting password by using hash methods
            $query = "insert into users (username,email,password,date_created, date_updated) value ('$username', '$email', '$password', '$date_created', '$date_updated')";
            query($query); 		//to run a query
            $info['success'] = true;
        }
        $info['errors'] = $errors;
    }

    else if($_POST['data_type'] == "upload_files")		//to upload files on mediahub
    {
        $folder = "uploads/";			//a new folder where all files will be stored after uploading on Mediahub
        if(!file_exists($folder)){		
            mkdir($folder,0777,true);
            file_put_contents($folder.".HTACCESS","Options -Indexes"); //it is for preventing others to access our uploads folder in which all uploaded files are present
        }

        foreach ($_FILES as $key => $file)
		{
            $destination = $folder.time().$file['name'];  //unique naming of destination file by inserting time at the moment of storing
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
            $user_id = $_SESSION['MY_DRIVE_USER']['id'] ?? 0;  //else 0
            $folder_id = $_POST['folder_id'] ?? 0;

            $query = "insert into mydrive (file_name, file_size, file_path, user_id, file_type, date_created, date_updated,folder_id) 
            value ('$file_name', '$file_size', '$file_path', '$user_id', '$file_type', '$date_created', '$date_updated', '$folder_id')";
            
            query($query); 
            $info['success'] = true;
        }
    }

    else if($_POST['data_type'] == "new_folder")	//creating new_folder
	{

		//save to database
		$name = addslashes($_POST['name']);
		$date_created = date("Y-m-d H:i:s");
		$user_id = $_SESSION['MY_DRIVE_USER']['id'] ?? 0;
        $parent = $_POST['folder_id'] ?? 0;			//defining parent folder using folder_id from database

		$query = "insert into folders (name, user_id, date_created, parent) values ('$name', '$user_id', '$date_created', '$parent')";
		query($query);

		$info['success'] = true;
	}

    else if($_POST['data_type'] == "add_to_favorites")		//add to favorite feature
	{
		//check if item is already favorited
		$id = addslashes($_POST['id'] ?? 0);
 		$user_id = $_SESSION['MY_DRIVE_USER']['id'];

 		$query = "select * from mydrive where user_id = '$user_id' && id = '$id' limit 1"; 
 		$row = query($query);

 		if($row)
 		{
 			$row = $row[0];
 			$favorite = !$row['favorite'];		
			$query = "update mydrive set favorite = '$favorite' where user_id = '$user_id' && id = '$id' limit 1";
			query($query);
 		}
		$info['success'] = true;
	}

    else if($_POST['data_type'] == "delete_row")		//delete a row
	{
		//delete from database
		$id = addslashes($_POST['id']);
		$file_type = addslashes($_POST['file_type']);
		$user_id = $_SESSION['MY_DRIVE_USER']['id'];

		$actually_deleted = false;			//setting actually deleted to false

		if($file_type == 'folder')
		{
			$sql = "select * from folders where id = '$id' limit 1";
			$row = query_row($sql);

			if($row['trash'] == 1)
			{
				$query = "delete from folders where id = '$id' && user_id = '$user_id' limit 1";
				$actually_deleted = true;
			}else{
				$query = "update folders set trash = 1 where id = '$id' && user_id = '$user_id' limit 1";
			}

			if($actually_deleted)  //only enters when true
			{
				//delete all files and folders from folder
				$folder_id = $row['id'];
				$sql = "delete from mydrive where folder_id = '$folder_id' && user_id = '$user_id' limit 1";
				query($sql);
			}

		}
		else		//if file_type is not folder
		{
			$sql = "select * from mydrive where id = '$id' limit 1";
			$row = query_row($sql);
			if($row)
			{
				if($row['trash'] == 1)  //means file is in trash
				{
					$query = "delete from mydrive where id = '$id' && user_id = '$user_id' limit 1";
					$actually_deleted = true;
				}else{				//if not in trash than set trash to 1 in db which will put it in trash
					$query = "update mydrive set trash = 1 where id = '$id' && user_id = '$user_id' limit 1";
				}
			}

			if($actually_deleted && file_exists($row['file_path']))
			{
				//delete actual file
				unlink($row['file_path']);
			}
		}
		query($query);
		$info['success'] = true;
	}

    else if($_POST['data_type'] == "restore_row")			//for restoring deleted files from trash to MyFiles
	{
		//restore from database
		$id = addslashes($_POST['id']);
		$file_type = addslashes($_POST['file_type']);
		$user_id = $_SESSION['MY_DRIVE_USER']['id'];

		if($file_type == 'folder')
		{
			$query = "update folders set trash = 0 where id = '$id' && user_id = '$user_id' limit 1";
		}
        else
        {
			$query = "update mydrive set trash = 0 where id = '$id' && user_id = '$user_id' limit 1";
		}
		query($query);
		$info['success'] = true;
	}

    else if($_POST['data_type'] == "get_files")		//get files used multiple times like for each folder get specific files
    {
        $user_id =  $_SESSION['MY_DRIVE_USER']['id'] ?? null;
        $mode=$_POST['mode'];
        $folder_id = $_POST['folder_id'] ?? 0;

        //get folder breadcrumbs
		$has_parent = true;
		$num = 0;
		$myfolder_id = $folder_id;
		while($has_parent && $num < 100)		//having a parent folder and its id<100
		{   
			$query = "select * from folders where id = '$myfolder_id' limit 1";
			$row = query($query);
			if($row){
				$info['breadcrumbs'][] = $row[0];
				if($row[0]['parent'] == 0){
					$has_parent = false;
				}else{
					$myfolder_id = $row[0]['parent'];
				}
			}
			$num++;
		}

        switch ($mode) {		//different modes to get files of different fields
            case 'MY DRIVE':
                $query_folder = "select * from folders where trash=0 && user_id = '$user_id' && parent = '$folder_id' order by id desc limit 30";
                $query = "select * from mydrive where trash=0 && user_id = '$user_id' && folder_id= '$folder_id' order by id desc limit 30";
                break;
            case 'favoriteS':
                $query = "select * from mydrive where trash=0 && favorite = 1 && user_id='$user_id' order by id desc limit 30";
                break;
            case 'RECENT':
                $query = "select * from mydrive where trash=0 && user_id='$user_id' order by date_updated desc limit 30";
                break;
            case 'TRASH':
                $query_folder = "select * from folders where trash=1 && user_id = '$user_id' && parent = '$folder_id' order by id desc limit 30";
                $query = "select * from mydrive where trash = 1 && user_id='$user_id' order by id desc limit 30";
                break;

            default:
                $query = "select * from mydrive where trash=0 && user_id='$user_id' && folder_id= '$folder_id' order by id desc limit 30";
                break;
        }

        if(!empty($query_folder))
			$rows_folder = query($query_folder);
		
		if(empty($rows_folder))
			$rows_folder = [];

		$rows = query($query);
 		if(empty($rows))
			$rows = [];

		$rows = array_merge($rows_folder, $rows);
        if(!empty($rows))
		{
			foreach ($rows as $key => $row) {
				if(empty($row['file_type']))
				{
					$rows[$key]['file_type'] = 'folder';
					$row['file_type'] = 'folder';
					
					$rows[$key]['date_updated'] = $row['date_created'];
					$row['date_updated'] = $row['date_created'];
					
					$rows[$key]['file_size'] = 0;
					$row['file_size'] = 0;
					
					$rows[$key]['file_name'] = $row['name'];
					$row['file_name'] = $row['name'];	
				}

				$parts = explode(".", $row['file_name']);
				$ext = strtolower(end($parts));
				$rows[$key]['icon'] = get_icon($row['file_type'], $ext);
				$rows[$key]['date_created'] = get_date($row['date_created']);
				$rows[$key]['date_updated'] = get_date($row['date_updated']);
				$rows[$key]['file_size'] = round($row['file_size'] / (1024 * 1024)) . "Mb";

				if($rows[$key]['file_size'] == "0Mb")
					$rows[$key]['file_size'] = round($row['file_size'] / (1024)) . "kb";
			}

			$info['rows'] = $rows;
			$info['success'] = true;
		}
    }
}

echo json_encode($info); //encodes data stored in $info to json(key-pair) format and send it as a respone to client