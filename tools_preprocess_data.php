<?php
    $server="127.0.0.1"; $username="root"; $password="root"; $database="taxiaa";
    mysql_connect($server,$username,$password) or die("Koneksi gagal");
    mysql_select_db($database) or die("DB not available");


    if(isset($_GET['req']) && $_GET['req']!=""){
        switch($_GET['req']){
            case "getData" : 
                $wherePeriod=(isset($_GET['startPeriod']) && isset($_GET['endPeriod']))? " AND trip_date BETWEEN STR_TO_DATE('".$_GET['startPeriod']."', '%Y-%m-%d') AND STR_TO_DATE('".$_GET['endPeriod']."', '%Y-%m-%d')" : "" ;
                $result=mysql_query("
                    SELECT * FROM argo_gps_join_12 
                    WHERE 
                        pickup_loc_2_lat is not null and pickup_loc_2_lat!='' and
                        pickup_loc_2_long is not null and pickup_loc_2_long!='' and
                        dropoff_loc_2_lat is not null and dropoff_loc_2_lat!='' and
                        dropoff_loc_2_long is not null and dropoff_loc_2_long!=''
                    ".$wherePeriod."
                    ORDER BY trip_date,pickup
                ");
                $i=0;
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data;
                    $i++;
                }
                echo @json_encode($trip);
                break;
                
            case "getArea" :
                $result=mysql_query(" 
                SELECT area_name, GROUP_CONCAT(id SEPARATOR ',') id 
                FROM grid_area
                WHERE area_name IS NOT NULL and area_name!=''
                GROUP BY area_name
                ORDER BY area_name ASC");
                $i=0;
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data;
                    $i++;
                }
                echo @json_encode($trip);
                break;
                
            case "update":
                $result=mysql_query("UPDATE GRID_AREA SET AREA_NAME=NULL WHERE ID IN(".$_GET["oldGrid"].")");
                $result1=mysql_query("UPDATE GRID_AREA SET AREA_NAME='".$_GET["areaName"]."' WHERE ID IN(".$_GET["grid"].")");
                echo ($result1);
                break;
//            case "generateGrid" : 
//                $row=$_GET['row'];
//                for($i=0;$i<$row;$i++){
//                    $result=mysql_query(" INSERT INTO GRID_AREA (ROW,COL,AREA_NAME) VALUES (NULL,NULL,NULL) ");
//                }
//                //print_r($result);
//                break;
            default : break;
        }
    }
    
?>