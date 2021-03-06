<?php

    require_once 'config.php';

    //UPDATE project
    if(isset($_POST['update_project'])) {
        
        $ot_id = $_POST["txtOT_ID"];
        $project_name = trim($_POST["txtProject_name"]);
        $ot_owner = trim($_POST["txtOT_owner"]);
        $update_by = $_SESSION['login_userName'];
        $signer = trim($_POST["signer"]);

        date_default_timezone_set('asia/bangkok');
        $update_date = date("Y/m/d H:i:s");
        $sqlUpdate = "UPDATE ot_project SET OT_NAME = '$project_name', OT_OWNER = '$ot_owner', SIGNER = '$signer', UPDATE_BY = '$update_by', UPDATE_DATE = '$update_date'
                        WHERE OT_ID = $ot_id";
        mysqli_query($conn, $sqlUpdate);
    } 

    //Find max OT_ITEM_ID
    $sql_maxItemID = "SELECT MAX(ITEM_ID) as maxotitem_id FROM ot_item";
    $result_maxItemID = mysqli_query($conn, $sql_maxItemID);
    $row = mysqli_fetch_assoc($result_maxItemID);
    $newOTItem_id  = $row['maxotitem_id'] + 1;
        
    //INSERT holiday
    if(isset($_POST["addNewOTItem"])){
        $ot_id = $_POST['ot_id'];
        $otItem_id = $_POST['otItem_id'];
        $work_date = $_POST['datepick'];
        $create_by = $_SESSION['login_userName'];
        $create_id = $_SESSION['login_userID'];
        date_default_timezone_set('asia/bangkok');
        $create_date = date("Y-m-d H:i:s");
        $hr_id = $_POST['hrID_value'];
        
        
        //get time work from-to
        $work_from = $_POST['work_from'];
        $work_to = $_POST['work_to'];
        //get mutiple values from select 
        $value = filter_input(INPUT_POST, 'ot_type');
        $exploded_value = explode('|', $value);
        $ot_type = $exploded_value[0];
        $ot_rate = $exploded_value[1];

        $err = check($conn, $hr_id, $work_date, $work_from, $work_to, $ot_id);

        

        if(empty($_POST['hrID_value'])) {
            $err = 'กรุณาเลือกชื่อบุคลากร';
        }
        
        if(!empty($err)) {
            showErr($err);
        } else {
            //find HR information
            $sql = "SELECT HR_NAME, HR_SURNAME, PREFIX_2 FROM hr_master WHERE HR_ID = '$hr_id'";
            $result = mysqli_query($conn, $sql);
            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_array($result);
                $hr_name = $row['HR_NAME'];
                $hr_surname = $row['HR_SURNAME'];
                $hr_position = $row['PREFIX_2'];
            }
            
            $amount = calculateAmount($ot_type, $work_from, $work_to, $ot_rate);
            $sqlInsert = "INSERT INTO ot_item(ITEM_ID, OT_ID, OT_TYPE, ITEM_STATUS, HR_ID, NAME, SURNAME, POSITION_NAME, WORK_DATE, WORK_FROM, WORK_TO, AMOUNT, CREATE_BY, CREATE_DATE, CREATE_ID) 
                        VALUES ('$otItem_id','$ot_id','$ot_type', '5', '$hr_id', '$hr_name', '$hr_surname', '$hr_position', '$work_date', '$work_from', '$work_to', '$amount', '$create_by', '$create_date', '$create_id')";
            $insert = mysqli_query($conn, $sqlInsert);
            
            //update ot_project
            //calculate total amount
            $total_amount = calTotal_amount($conn, $ot_id);

            date_default_timezone_set('asia/bangkok');
            $update_date = date("Y/m/d H:i:s");
            
            $sqlUpdate = "UPDATE ot_project SET TOTAL_AMOUNT = '$total_amount', UPDATE_BY = '$create_by', UPDATE_DATE = '$update_date'
                        WHERE OT_ID = $ot_id";
            $update = mysqli_query($conn, $sqlUpdate);
            
            if($insert && $update) {
                header("location: editOT_page.php?edit_id=$ot_id");
            }
        }
    }

    if(isset($_GET['deleteOTItem'])) {
    
        $ot_id = $_GET['ot_id'];
        $hr_id = $_GET['hr_id'];
        $item_id = $_GET['deleteOTItem'];
        $sqlDelete = "DELETE FROM ot_item WHERE ITEM_ID=$item_id";
        $delete = mysqli_query($conn, $sqlDelete);

        //update ot_project
        //calculate total amount
        $total_amount = calTotal_amount($conn, $ot_id);

        date_default_timezone_set('asia/bangkok');
        $update_date = date("Y/m/d H:i:s");
        $update_by = $_SESSION['login_userName'];
        $sqlUpdate = "UPDATE ot_project SET TOTAL_AMOUNT = '$total_amount', UPDATE_BY = '$update_by', UPDATE_DATE = '$update_date'
                        WHERE OT_ID = $ot_id";
        $update = mysqli_query($conn, $sqlUpdate);
            
        if($delete && $update) {
            header("location: pages/ot_item.php?hr_id=$hr_id&ot_id=$ot_id");
        }
    }
        

    function showErr($err) {
        echo '<script type="text/javascript">';
        echo 'alert("'.$err.'")'; 
        echo '</script>';
    }

    function TimeDiff($strTime1,$strTime2) {
        return abs((strtotime($strTime2) - strtotime($strTime1))/  ( 60 * 60 ));
    }

    function calculateAmount($ot_type, $work_from, $work_to, $ot_rate) {
        $amount = 0;
        if($ot_type == 1 || $ot_type == 2) {
            return $amount = $ot_rate;

        } elseif($ot_type == 3) {
            $time_work = TimeDiff($work_from,$work_to);
            $amount = $ot_rate*$time_work;
            
            return number_format($amount , 2 );
        }
    }

    function check($conn, $hr_id, $work_date, $work_from, $work_to, $ot_id) {
        $err = "";

        $sql = "SELECT WORK_DATE, WORK_FROM, WORK_TO FROM ot_item WHERE OT_ID=$ot_id AND HR_ID = $hr_id";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_array($result)) {
                if ($row['WORK_DATE'] == $work_date) {
                    if( $work_from < $work_to) {
                        if($row['WORK_FROM'] >= $work_from  && $row['WORK_FROM'] >= $work_to) {
                            $err = "";
                        } elseif($row['WORK_TO'] <= $work_from  && $row['WORK_TO'] <=  $work_to) {
                            $err = "";
                        } else {
                            $err = "เกิดข้อผิดพลาด เนื่องจากเพิ่มช่วงเวลาซ้ำ";
                        }
                    }  else {
                        $err = "เกิดข้อผิดพลาด เนื่องจากเวลา";
                    }
                }
            }
        }


        return $err;
    }

    function calTotal_amount($conn, $ot_id) {
        $sum_allProject = "SELECT SUM(AMOUNT) as total FROM ot_item WHERE OT_ID = $ot_id";
        $query_sum = mysqli_query($conn, $sum_allProject);
        $result_sum = mysqli_fetch_array($query_sum);
        $total_amount = $result_sum['total'];
        return $total_amount;
    }
    

 ?>       
