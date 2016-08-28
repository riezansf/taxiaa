<?php
    $server="127.0.0.1"; $username="root"; $password=""; $database="taxiaa";
    mysql_connect($server,$username,$password) or die("Koneksi gagal");
    mysql_select_db($database) or die("DB not available");

    if(isset($_GET['req']) && $_GET['req']!=""){
        if(isset($_GET['index']) && $_GET['index']!=""){
            $INDEX=$_GET['index'];
            $pickup_area="pickup".$INDEX."_area";
            $dropoff_area="dropoff".$INDEX."_area";
            $pickup_lat="pickup".$INDEX."_lat";
            $dropoff_lat="dropoff".$INDEX."_lat";
            $pickup_long="pickup".$INDEX."_long";
            $dropoff_long="dropoff".$INDEX."_long";
        }
        
        function getDay(){
            $dayTrim="";
            if(isset($_GET['day']) && $_GET['day']!=''){
                $day=explode(",",$_GET['day']);
                for($i=0;$i<sizeof($day);$i++){
                    $hour=explode("-",$day[$i]);
                    if($i>0){
                        $dayTrim.="-";
                    }
                    $dayTrim.=$hour[0];
                }
            }
            return $dayTrim;
        }
        
        function getWherePeriod(){
            //echo $pickup_area;
            
            $INDEX=$_GET['index'];
            $pickup_area="pickup".$INDEX."_area";
            $dropoff_area="dropoff".$INDEX."_area";
            $pickup_lat="pickup".$INDEX."_lat";
            $dropoff_lat="dropoff".$INDEX."_lat";
            $pickup_long="pickup".$INDEX."_long";
            $dropoff_long="dropoff".$INDEX."_long";
            
            $wherePeriod=(isset($_GET['startPeriod']) && isset($_GET['endPeriod']))? " AND trip_date BETWEEN STR_TO_DATE('".$_GET['startPeriod']."', '%Y-%m-%d') AND STR_TO_DATE('".$_GET['endPeriod']."', '%Y-%m-%d')" : "" ;
            $whereArea=isset($_GET['area'])? "AND $pickup_area='".$_GET['area']."'  " :""; //or $dropoff_area='".$_GET['area']."'
            $whereWeekday=(isset($_GET['weekday']) && $_GET['weekday']!='') ? "and WEEKDAY(trip_date) in (".$_GET['weekday'].")" : "";
            $whereDay="";
            if(isset($_GET['day']) && $_GET['day']!=''){
                $whereDay=" AND (";
                $day=explode(",",$_GET['day']);
                for($i=0;$i<sizeof($day);$i++){
                    $hour=explode("-",$day[$i]);
                    if($i>0){
                        $whereDay.=" OR";
                    } 
                    $whereDay.=" (TIME(pickup) between '".$hour[0]."' AND '".$hour[1]."')";
                }
                $whereDay.=" ) ";
            }
            return $wherePeriod." ".$whereArea." ".$whereWeekday." ".$whereDay; 
        }
        
        switch($_GET['req']){
            case "getTrip" : 
                $query="
                    SELECT * FROM trip_12 
                    WHERE 
                        $pickup_lat is not null and $pickup_long!='' and
                        $pickup_long is not null and $pickup_long!='' and
                        $dropoff_lat is not null and $dropoff_lat!='' and
                        $dropoff_long is not null and $dropoff_long!=''
                        ".getWherePeriod()." 
                    ORDER BY trip_id
                ";
//                AND $pickup_area='Kebon Jati' AND $dropoff_area='Kebon Jati'
                //echo $query;
                $result=mysql_query($query);
                $i=0;
                while ($data=mysql_fetch_array($result)){ $trip[$i]=$data; $i++; }
                echo @json_encode($trip);
                break;
                
            case "getCountTrip" : 
                $query="
                    select $pickup_area, $dropoff_area, count(*) weight 
                    from trip_12 
                    WHERE 
                        $pickup_lat is not null and $pickup_long!='' and
                        $pickup_long is not null and $pickup_long!='' and
                        $dropoff_lat is not null and $dropoff_lat!='' and
                        $dropoff_long is not null and $dropoff_long!=''
                        ".getWherePeriod()." 
                    group by $pickup_area, $dropoff_area
                    ORDER BY $pickup_area, $dropoff_area
                ";
                $result=mysql_query($query);
                $i=0;
                while ($data=mysql_fetch_array($result)){ $trip[$i]=$data; $i++; }
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
                while ($data=mysql_fetch_array($result)){ $trip[$i]=$data; $i++; }
                echo @json_encode($trip);
                break;
            
            case "exportData" :
                $result=mysql_query("
                SELECT REPLACE($pickup_area, ' ', '') $pickup_area,REPLACE($dropoff_area, ' ', '') $dropoff_area 
                FROM trip_12
                WHERE 
                    $pickup_area IS NOT NULL and $pickup_area!='' and 
                    $dropoff_area IS NOT NULL and $dropoff_area!=''
                    ".getWherePeriod()."
                ORDER BY $pickup_area,$dropoff_area ASC
                ");
                
                $start=explode("-",$_GET['startPeriod'])[2];
                $end=explode("-",$_GET['endPeriod'])[2];
                $filename="Gp".$INDEX."_".$start."-".$end."_".str_replace(',', '', $_GET['weekday'])."_".getDay();
                
                $myfile = fopen("data/".$filename.".csv", "w") or die("Unable to open file!");
                while ($data=mysql_fetch_array($result)){
                    fwrite($myfile, $data[$pickup_area].",".$data[$dropoff_area]."\n");
                }
                fclose($myfile);
                echo @json_encode($filename);
                break;
            
            //Graph statistic   
            case "getODRank" : 
                $query="
                    select $pickup_area, $dropoff_area, count(*) weight 
                    from trip_12 
                    WHERE 
                        $pickup_lat is not null and $pickup_long!='' and
                        $pickup_long is not null and $pickup_long!='' and
                        $dropoff_lat is not null and $dropoff_lat!='' and
                        $dropoff_long is not null and $dropoff_long!=''
                        ".getWherePeriod()." 
                    group by $pickup_area, $dropoff_area
                    ORDER BY weight DESC,$pickup_area, $dropoff_area
                    LIMIT 10
                ";
                
                $result=mysql_query($query);
                $i=0;
                while ($data=mysql_fetch_array($result)){ $trip[$i]=$data; $i++; }
                echo @json_encode($trip);
                break; 
            break; 
                
            case "getWeightOut" : 
                $query="
                    select $pickup_area, count($pickup_area) weight_out
                    from trip_12
                    WHERE 
                        $pickup_lat is not null and $pickup_long!='' and
                        $pickup_long is not null and $pickup_long!='' and
                        $dropoff_lat is not null and $dropoff_lat!='' and
                        $dropoff_long is not null and $dropoff_long!=''
                        ".getWherePeriod()." 
                    group by $pickup_area
                    order by weight_out desc
                    LIMIT 10
                ";
                $result=mysql_query($query);
                $i=0;
                while ($data=mysql_fetch_array($result)){ $trip[$i]=$data; $i++; }
                echo @json_encode($trip);
            break;                 

                
                
                
                
                
                
                
            //
            //#weight out
            //select pickup3_area, count(pickup3_area) degree_out
            //from trip_12
            //group by pickup3_area
            //order by degree_out desc
            //
            //#weight in
            //select dropoff3_area, count(dropoff3_area) degree_in
            //from trip_12
            //group by dropoff3_area
            //order by degree_in desc
            //
            //#degree out
            //select pickup3_area, count(distinct(dropoff3_area)) degree_out 
            //from trip_12 
            //group by pickup3_area
            //order by degree_out desc
            //
            //#degree in
            //select dropoff3_area, count(distinct(pickup3_area)) degree_in 
            //from trip_12 
            //group by dropoff3_area
            //order by degree_in desc
            //
            //#average km out
            //select pickup3_area, count(pickup3_area) trip, round(avg(km)) avg_distance_out , round(avg(amount)) avg_amount
            //from trip_12 
            //group by pickup3_area
            //order by avg_distance_out desc
            //
            //#average km in
            //select dropoff3_area, count(dropoff3_area) trip, round(avg(km)) avg_distance_in , round(avg(amount)) avg_amount
            //from trip_12 
            //group by dropoff3_area
            //order by avg_distance_in desc
            //    
                
            //=============== JAMIL =======================================================
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
                    where period='2015-12-07 12:00-2015-12-07 15:00' 
                    ORDER BY grid,period ASC
                ");
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