<?php
require_once 'includes/config.php';
$db = getDB();
echo "Starting ML Training Data Generation...\n";
$db->query("ALTER TABLE admission_records ADD COLUMN IF NOT EXISTS entrance_scores DECIMAL(5,2) DEFAULT 0 AFTER previous_scores");
$db->query("TRUNCATE TABLE admission_records");
echo "Schema ready, old records cleared.\n";

global $COLLEGE_CUTOFFS;
$colleges = $db->query("SELECT * FROM colleges")->fetch_all(MYSQLI_ASSOC);
if(empty($colleges)) die("No colleges found.\n");

function gauss($mean,$std){
  $u1=mt_rand(1,1000000)/1000000.0;
  $u2=mt_rand(1,1000000)/1000000.0;
  return $mean + $std * sqrt(-2*log($u1)) * cos(2*M_PI*$u2);
}

$stmt=$db->prepare("INSERT INTO admission_records (student_id,college_id,outcome,previous_scores,entrance_scores,year) VALUES (?,?,?,?,?,?)");
$years=[2022,2023,2024,2025];
$total=0;

foreach($colleges as $c){
  $cid=$c['college_id'];
  $realData=$COLLEGE_CUTOFFS[$cid]??null;
  $boardCutoff = $realData ? $realData['board'] : floatval($c['cutoff_scores']);
  // Use General/Other cutoff as entrance baseline for synthetic data
  $entrCutoff  = $realData ? ($realData['exams']['General/Other']??floatval($c['entrance_cutoff'])) : floatval($c['entrance_cutoff']);
  $diff        = $realData['difficulty']??'moderate';
  $tierRaw     = trim(str_replace(' ','',strtolower($c['tier'])));
  
  $bSpread = match($diff){ 'extreme'=>4, 'very_high'=>6, 'moderate'=>9, default=>12 };
  $eSpread = match($diff){ 'extreme'=>4, 'very_high'=>5, 'moderate'=>8, default=>11 };

  for($i=0;$i<50;$i++){
    $bs = max(0, min(100, gauss($boardCutoff,$bSpread)));
    $es = max(0, min(100, gauss($entrCutoff,$eSpread)));
    $year=$years[array_rand($years)];
    $bm=$bs-$boardCutoff; $em=$es-$entrCutoff;
    $outcome='Rejected';

    if($diff==='extreme'){
      $cm=($bm*0.35)+($em*0.65);
      if($cm>=1.5)      $outcome=(mt_rand(1,100)<=85)?'Admitted':'Waitlisted';
      elseif($cm>=-0.5) $outcome=(mt_rand(1,100)<=25)?'Waitlisted':'Rejected';
    }elseif($diff==='very_high'){
      $cm=($bm*0.40)+($em*0.60);
      if($cm>=1.0)      $outcome=(mt_rand(1,100)<=82)?'Admitted':'Waitlisted';
      elseif($cm>=-1.5) $outcome=(mt_rand(1,100)<=30)?'Waitlisted':'Rejected';
    }elseif($tierRaw==='tier3'){
      $cm=($bm*0.65)+($em*0.35);
      if($cm>=0)        $outcome=(mt_rand(1,100)<=88)?'Admitted':'Waitlisted';
      elseif($cm>=-4)   $outcome=(mt_rand(1,100)<=50)?'Admitted':(mt_rand(1,100)<=50?'Waitlisted':'Rejected');
      elseif($cm>=-8)   $outcome=(mt_rand(1,100)<=20)?'Waitlisted':'Rejected';
    }else{
      $cm=($bm*0.48)+($em*0.52);
      if($cm>=1.0)      $outcome=(mt_rand(1,100)<=82)?'Admitted':'Waitlisted';
      elseif($cm>=-2)   $outcome=(mt_rand(1,100)<=45)?'Admitted':(mt_rand(1,100)<=50?'Waitlisted':'Rejected');
      elseif($cm>=-5)   $outcome=(mt_rand(1,100)<=15)?'Waitlisted':'Rejected';
    }

    $sid=1;
    $stmt->bind_param('iisddi',$sid,$cid,$outcome,$bs,$es,$year);
    $stmt->execute();
    $total++;
  }
}
$stmt->close(); $db->close();
echo "Done! Generated $total records using real 2024 cutoff data as centers.\n";
echo "Run predictions now — model is calibrated.\n";
?>
