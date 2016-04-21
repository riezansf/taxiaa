<?php
include 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
function printArray($array){ echo "<pre>"; print_r($array); echo "</pre>";}

//---------

$no=1;
$fileNo=1;
$countFile=0;
foreach(glob('dataperhari/*.*') as $file) {
    if(strpos($file, '1 1') !== false){ $countFile++; }
    echo "<br>".$fileNo++.". ".$file."<br>";
    
    $pdf    = $parser->parseFile($file);
    $line   = explode("\n",$pdf->getText());
    //printArray($line);

    echo "DATE | TAXI_NUMBER | START_TRIP | END_TRIP | PAID_KM | AMOUNT<br>";
    
    for($i=0;$i<sizeof($line);$i++){ //exclude last line
        //constant line
        if($i==2){ //DATE
            //$date=explode(" ",$line[$i])[2];
            $date="XX";
            //printArray(explode(" ",$line[$i])[2]); 
        }
        else if ($i==3){ //TAXI NUMBER
            $taxiNumber=substr(explode(" ",$line[$i])[0], -3);
            //printArray(substr(explode(" ",$line[$i])[0], -3)); 
        }
        else if($i>=5 and $i<=sizeof($line)-2){ //TRIP - 1 line 1 or 2 trip
            $trip=explode(" ",trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $line[$i]))));
            //printArray($trip);

            //col 1
            if($trip[0]!="--" and $trip[0]!=""){
                $startEndTrip=explode(".",$trip[2]);
                if(sizeof($startEndTrip)>1){ // if contains date, TODO : use date from this!!!!!
                    $startTrip=explode("-",$startEndTrip[1])[0];    
                    $endTrip=explode("-",$startEndTrip[1])[1];    
                }else{
                    $startTrip=explode("-",$trip[2])[0];    
                    $endTrip=explode("-",$trip[2])[1];  
                }
                echo "(".$no++.") ".$date.",".$taxiNumber.",".$startTrip.",".$endTrip.",".$trip[3].",".str_replace(",",".",$trip[4])."<br>";

                //col 2 
                if($trip[6]!="--" and $trip[6]!=""){
                    $startEndTrip=explode(".",$trip[8]); 
                    if(sizeof($startEndTrip)>1){ // if contains date, TODO : use date from this!!!!!
                        $startTrip=explode("-",$startEndTrip[1])[0];    
                        $endTrip=explode("-",$startEndTrip[1])[1];    
                    }else{
                        $startTrip=explode("-",$trip[8])[0];    
                        $endTrip=explode("-",$trip[8])[1];  
                    }
                    echo "(".$no++.") ".$date.",".$taxiNumber.",".$startTrip.",".$endTrip.",".$trip[9].",".$trip[10]."<br>";
                }
            }
        }
    }
} echo "92 : ".$countFile;
?>



