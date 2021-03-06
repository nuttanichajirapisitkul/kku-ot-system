<?php
    
    require_once 'config.php';
    
    //DELETE project
    if(isset($_GET['deleteproject'])) {

        $ot_id = $_GET['deleteproject'];
        //delete from ot_project table
        $sqlDelete = "DELETE FROM ot_project WHERE OT_ID=$ot_id";
        $result = mysqli_query($conn, $sqlDelete);

        //delete from ot_item table
        $sqlDelete = "DELETE FROM ot_item WHERE OT_ID=$ot_id";
        $result = mysqli_query($conn, $sqlDelete);

        header("location: pages/ot.php");
    }
    
    //INSERT project
    if(isset($_POST["addproject"])) {
        $ot_id = $_POST["txtOT_ID"];
        $project_name = trim($_POST["txtProject_name"]);
        $ot_owner = trim($_POST["txtOT_owner"]);
        $create_by = $_SESSION['login_userName'];
        $create_id = $_SESSION['login_userID'];                
        date_default_timezone_set('asia/bangkok');
        $create_date = date("Y/m/d H:i:s");
        $create_user_text = $_SESSION['login_hrName'];
        $fac_id = getFacultyId($conn, $_SESSION['login_hrID']);
        $sqlInsert = "INSERT INTO ot_project(OT_ID, OT_NAME, OT_OWNER, CREATE_BY, CREATE_DATE, CREATE_ID, CREATE_USER_TEXT, FACULTY_ID) VALUES ('$ot_id', '$project_name', '$ot_owner', '$create_by', '$create_date', '$create_id', '$create_user_text', '$fac_id')";
        $insert = mysqli_query($conn, $sqlInsert);
        if($insert) {
            header("location: ot.php");
        }
    }

    
        $sql_maxID = "SELECT MAX(OT_ID) as maxot_id FROM ot_project";
        $result_maxID = mysqli_query($conn, $sql_maxID);
        $row = mysqli_fetch_assoc($result_maxID);
        $_SESSION['newot_id']  = $row['maxot_id'] + 1;
        

    function getFacultyId($conn, $hr_id)
    {
        $fac_id="";
        if($hr_id !=""){
	        $sql="select * from hr_master where HR_ID = $hr_id";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            $fac_id=$row['FACULTY_ID'];
	        return $fac_id;
        }   
    }

?>