<?php
    $server="127.0.0.1"; $username="root"; $password=""; $database="aataksi";
    mysql_connect($server,$username,$password) or die("Koneksi gagal");
    mysql_select_db($database) or die("DB not available");

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

//    echo $_GET["startPeriod"];
//    echo $_GET["endPeriod"];
?>