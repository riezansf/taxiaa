<?php
    $server="127.0.0.1"; $username="root"; $password=""; $database="taxiaa";
    mysql_connect($server,$username,$password) or die("Koneksi gagal");
    mysql_select_db($database) or die("DB not available");


    if(isset($_GET['req']) && $_GET['req']!=""){
        $INDEX=$_GET['index'];
        $pickup_area="pickup".$INDEX."_area";
        $dropoff_area="dropoff".$INDEX."_area";
        $pickup_lat="pickup".$INDEX."_lat";
        $dropoff_lat="dropoff".$INDEX."_lat";
        $pickup_long="pickup".$INDEX."_long";
        $dropoff_long="dropoff".$INDEX."_long";
        
        switch($_GET['req']){
            case "getTrip" : 
                $wherePeriod=(isset($_GET['startPeriod']) && isset($_GET['endPeriod']))? " AND trip_date BETWEEN STR_TO_DATE('".$_GET['startPeriod']."', '%Y-%m-%d') AND STR_TO_DATE('".$_GET['endPeriod']."', '%Y-%m-%d')" : "" ;
                $whereArea=isset($_GET['area'])? "AND $pickup_area='".$_GET['area']."'  " :"";
                //or $dropoff_area='".$_GET['area']."'
                $result=mysql_query("
                    SELECT * FROM trip_12 
                    WHERE 
                        $pickup_lat is not null and $pickup_long!='' and
                        $pickup_long is not null and $pickup_long!='' and
                        $dropoff_lat is not null and $dropoff_lat!='' and
                        $dropoff_long is not null and $dropoff_long!=''
                        ".$wherePeriod."
                        ".$whereArea."
                    ORDER BY trip_id
                ");
                $i=0;
                
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data; $i++;
                }
                echo @json_encode($trip);
                break;
                
            case "getGridArea" :
                mysql_query("SET SESSION group_concat_max_len = 1000000");
                $result=mysql_query("
                    SELECT area_name, GROUP_CONCAT(id SEPARATOR ',') id 
                    FROM grid_area
                    WHERE area_name IS NOT NULL and area_name!=''
                    GROUP BY area_name
                    ORDER BY area_name ASC
                ");
                $i=0;
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data;
                    $i++;
                }
                echo @json_encode($trip);
                break;
            
            case "exportData" :
                $result=mysql_query("
                SELECT REPLACE($pickup_area, ' ', '') $pickup_area,REPLACE($dropoff_area, ' ', '') $dropoff_area 
                FROM trip_12
                WHERE 
                    $pickup_area IS NOT NULL and $pickup_area!='' and 
                    $dropoff_area IS NOT NULL and $dropoff_area!=''
                ORDER BY $pickup_area,$dropoff_area ASC
                ");
            
                $myfile = fopen("data/area_to_area".$INDEX.".csv", "w") or die("Unable to open file!");
                while ($data=mysql_fetch_array($result)){
                    fwrite($myfile, $data[$pickup_area].",".$data[$dropoff_area]."\n");
                }
                echo fclose($myfile);
                break;
                   
            //=============== JAMIL
            case "getTripForArimaData" : 
                $timePeriod=explode("-",$_GET['timePeriod']);
                $wherePeriod=(isset($_GET['datePeriod']) && isset($_GET['timePeriod']))? 
                    " AND trip_date <= STR_TO_DATE('".$_GET['datePeriod']." ".$timePeriod[0]."', '%Y-%m-%d %H:%i') AND trip_date >= STR_TO_DATE('2015-12-07 00:00', '%Y-%m-%d %H:%i')" : "" ;
                $result=mysql_query("
                    SELECT * FROM trip_12 
                    WHERE 
                        pickup2_grid100!='' AND pickup2_grid100 IS NOT NULL
                    ".$wherePeriod."
                    ORDER BY trip_date,pickup ASC
                ");
                $i=0;
//                echo "
//                    SELECT * FROM trip_12 
//                    WHERE 
//                        pickup2_grid100!='' AND pickup2_grid100 IS NOT NULL
//                    ".$wherePeriod."
//                    ORDER BY trip_date,pickup ASC
//                ";
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data;
                    $i++;
                }
                echo @json_encode($trip);
                break;   
                
            case "getArimaData" :
                $timePeriod=explode("-",$_GET['timePeriod']);
                
                $result=mysql_query("
                    SELECT grid,count 
                    FROM arimaData 
                    where period='2015-12-08 12:00-2015-12-08 15:00' 
                    ORDER BY grid,period ASC
                ");
                //where period='".$_GET['datePeriod']." ".$timePeriod[0]."-".$_GET['datePeriod']." ".$timePeriod[1]."' 
//                echo "SELECT grid,count 
//                    FROM arimaData 
//                    where period='".$_GET['datePeriod']." ".$timePeriod[0]."-".$_GET['datePeriod']." ".$timePeriod[1]."' 
//                    ORDER BY grid,period ASC";
                $i=0;
                
                while ($data=mysql_fetch_array($result)){
                    $trip[$i]=$data;
                    $i++;
                }
                echo @json_encode($trip);
                break;    
                
            default : break;
        }
    }
    
?>