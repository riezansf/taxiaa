<?php
include 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
function printArray($array){ echo "<pre>"; print_r($array); echo "</pre>";}

$filecsv = fopen("argo_trip_07.csv","w");

$noTripPerCar=1;
$noFile=1;
$taxiSeq=1;
$totalTrip=0;

foreach(glob('7/*.*') as $file) {
//foreach(glob('12/2015-12-05.pdf') as $file) {
    $reportDate=explode("-",substr($file,3,-4));
    echo $noFile++.". ".substr($file,3,-4)."<br><br>";
    
    $pdf    = $parser->parseFile($file);
    $line   = explode("\n",$pdf->getText());
    //printArray($line);
    
    //search line-number-begin of TRIP table "ANALYSIS OF HIRED DETAILS"
    $begin=array_keys($line, 'ANALYSIS OF HIRED DETAILS');
    
    for($i=0;$i<sizeof($begin);$i++){
        echo "DATE | TAXI_NUMBER | START_TRIP | END_TRIP | PAID_KM | AMOUNT<br>";
        
        //search TAXI_NUMBER
        for($l=$begin[$i];$l>0;$l--){
            if(strpos($line[$l], 'COMPREHENSIVE OPERATION RECORDS') !== false){
                $taxiNumber=substr(explode(" ",$line[$l+2])[0], -3);
                break;
            }
        }
        
        //search line-number-end of TRIP table, line which contain string "Tot."
        for($j=$begin[$i];$j<sizeof($line);$j++){
            if(strpos($line[$j], 'Tot.') !== false){
                echo "#".$taxiSeq++." ".$begin[$i]."-".$j."<br>";
                
                for($k=$begin[$i]+2;$k<$j;$k++){ //Ignore 2 first line(Title & Table header)
                   
                    if(strpos($line[$k],'AA TaksiPage') === false and strpos($line[$k],'ANALYSIS')===false and strpos($line[$k],'No.')===false and $line[$k]!="") {  //ignore page-break line from previous
                        $trip=explode(" ",trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $line[$k]))));
                        //printArray($trip);

                        //col 1
                        if($trip[0]!="--" and $trip[0]!=""){
                            $startEndTrip=explode(".",$trip[2]);
                            if(sizeof($startEndTrip)>1){ //if contain date
                                
                                //Handle different beetwen REPORT-DATE and HIRED-DATE/TRIP-DATE
                                $diff=(ltrim($reportDate[2], '0')-$startEndTrip[0]); echo "diff ".$diff."<br>";
                                if($diff!=0){ //if reportdate!=date in hired table
                                    if(!($diff>-7 && $diff<7)){ //if out of range (-7..7), can handle if report collected in 7 days latter/before
                                        if($reportDate[2]>=1 && $reportDate[2]<=7){ //1st Week, month-1
                                            $date="2015-".($reportDate[1]-1)."-".sprintf("%02d", $startEndTrip[0]);
                                        }else{ //last week, month+1
                                            $date="2015-".($reportDate[1]+1)."-".sprintf("%02d", $startEndTrip[0]);
                                        }
                                    }else{ // if in range (-7..7)
                                        $date="2015-".$reportDate[1]."-".sprintf("%02d", $startEndTrip[0]);
                                    }
                                }else{
                                   $date="2015-".$reportDate[1]."-".sprintf("%02d", $startEndTrip[0]);
                                }
                                
                                $startTrip=explode("-",$startEndTrip[1])[0];    
                                $endTrip=explode("-",$startEndTrip[1])[1];    
                            }else{
                                
                                $startTrip=explode("-",$trip[2])[0];    
                                $endTrip=explode("-",$trip[2])[1];  
                            }
                          
                            echo "(".$noTripPerCar++.") ".$date.",".$taxiNumber.",".$startTrip.",".$endTrip.",".$trip[3].",".str_replace(",","",$trip[4])."<br>";
                            $totalTrip++;
                            fputcsv($filecsv,[$date,$taxiNumber,$startTrip,$endTrip,$trip[3],str_replace(",","",$trip[4])],",","'");

                            //col 2 
                            if($trip[6]!="--" and $trip[6]!=""){
                                $startEndTrip=explode(".",$trip[8]); 
                                if(sizeof($startEndTrip)>1){

                                    //Handle different beetwen REPORT-DATE and HIRED-DATE/TRIP-DATE
                                    $diff=(ltrim($reportDate[2], '0')-$startEndTrip[0]); echo "diff ".$diff."<br>";
                                    if($diff!=0){ //if reportdate!=date in hired table
                                        if(!($diff>-7 && $diff<7)){ //if out of range (-7..7), can handle if report collected in 7 days latter/before
                                            if($reportDate[2]>=1 && $reportDate[2]<=7){ //1st Week, month-1
                                                $date="2015-".($reportDate[1]-1)."-".sprintf("%02d", $startEndTrip[0]);
                                            }else{ //last week, month+1
                                                $date="2015-".($reportDate[1]+1)."-".sprintf("%02d", $startEndTrip[0]);
                                            }
                                        }else{ // if in range (-7..7)
                                            $date="2015-".$reportDate[1]."-".sprintf("%02d", $startEndTrip[0]);
                                        }
                                    }else{
                                       $date="2015-".$reportDate[1]."-".sprintf("%02d", $startEndTrip[0]);
                                    } 
                                    
                                    $startTrip=explode("-",$startEndTrip[1])[0];    
                                    $endTrip=explode("-",$startEndTrip[1])[1];    
                                }else{
                                    $startTrip=explode("-",$trip[8])[0];    
                                    $endTrip=explode("-",$trip[8])[1];  
                                }
                               
                                echo "(".$noTripPerCar++.") ".$date.",".$taxiNumber.",".$startTrip.",".$endTrip.",".$trip[9].",".str_replace(",","",$trip[10])."<br>";
                                $totalTrip++;
                                fputcsv($filecsv,[$date,$taxiNumber,$startTrip,$endTrip,$trip[9],str_replace(",","",$trip[10])],",","'");
                            } // end if line not -- or blank for colum 2
                        } // end if line not -- or blank for colum 1
                    }//end if found page-break line from previous page
                }// end loop for get each trip line, 0/1/2 trip per line
                $noTripPerCar=1; //counter for trip per car
                break; // quit loop if "Tot." occur
            }// end if "Tot."
        }// end off loop for search end of HIRED TABLE 
        echo "<br>";
    }// end loop from line ANALYSIS HIRED DETAIL  
    echo "TOTAL TRIP : ".$totalTrip."<br>";
    echo "AVERAGE TRIP / DAY / TAXI : ".round($totalTrip/$taxiSeq)."<br>";
    //break; 
}// end loop file PDF in directory
fclose($filecsv); 
?>



