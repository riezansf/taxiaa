<?php
    $server="127.0.0.1"; $username="root"; $password=""; $database="taxiaa";
    mysql_connect($server,$username,$password) or die("Koneksi gagal");
    mysql_select_db($database) or die("DB not available");

    if(isset($_POST['req']) && $_POST['req']!=""){
        if(isset($_GET['index']) && $_GET['index']!=""){
            $INDEX=$_GET['index'];
            $pickup_area="pickup".$INDEX."_area";
            $dropoff_area="dropoff".$INDEX."_area";
            $pickup_lat="pickup".$INDEX."_lat";
            $dropoff_lat="dropoff".$INDEX."_lat";
            $pickup_long="pickup".$INDEX."_long";
            $dropoff_long="dropoff".$INDEX."_long";

            $pickup_grid100="pickup".$INDEX."_grid100";
            $dropoff_grid100="dropoff".$INDEX."_grid100";
        }
        
        switch($_POST['req']){       
            case "updateTrip":
                $data=$_POST["data"];
                for($i=0;$i<sizeof($data);$i++){
                    $result=mysql_query("
                        UPDATE trip_12 
                        SET 
                            $pickup_grid100='".$data[$i][$pickup_grid100]."' ,
                            $dropoff_grid100='".$data[$i]["$dropoff_grid100"]."' ,
                            $pickup_area='".$data[$i][$pickup_area]."' ,
                            $dropoff_area='".$data[$i]["$dropoff_area"]."'
                        WHERE trip_id='".$data[$i]["id"]."'");
                }
                echo $result;
                break;
            
            case "updateGridArea":
                $result=mysql_query("UPDATE GRID_AREA SET AREA_NAME=NULL WHERE ID IN(".$_POST["oldGrid"].")");
                $result1=mysql_query("UPDATE GRID_AREA SET AREA_NAME='".$_POST["areaName"]."' WHERE ID IN(".$_POST["grid"].")");
                echo ($result1);
                break;
            
//            case "generateGridRecord" : 
//                $row=$_GET['row'];
//                for($i=0;$i<$row;$i++){
//                    $result=mysql_query(" INSERT INTO GRID_AREA (ROW,COL,AREA_NAME) VALUES (NULL,NULL,NULL) ");
//                }
//                //print_r($result);
//                break;
                
            //======= JAMIL
            case "saveArimaData":
                //print_r($_POST['arimaData']);
                
                mysql_query("delete from arimaData"); 
                
                for($i=0;$i<sizeof($_POST['arimaData']);$i++){
                    $data=explode(",",$_POST['arimaData'][$i]);
                    mysql_query("
                        INSERT INTO arimaData VALUES(".$data[0].",'".$data[1]."',".$data[2].")
                    "); 
                    //echo " INSERT INTO arimaData VALUES(".$data[0].",'".$data[1]."',".$data[2].")";
                }
            break;
            
            default : break;
        }
    }
    
?>