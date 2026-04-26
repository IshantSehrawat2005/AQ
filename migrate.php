<?php
require_once 'includes/config.php';
$db = getDB();
echo "Running migrations...\n";

// Add accepted_exams column if missing
$db->query("ALTER TABLE admission_records ADD COLUMN IF NOT EXISTS entrance_scores DECIMAL(5,2) DEFAULT 0 AFTER previous_scores");
$db->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_name VARCHAR(50) DEFAULT 'General/Other' AFTER entrance_scores");
$db->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_raw_score DECIMAL(8,2) DEFAULT 0 AFTER exam_name");
$db->query("ALTER TABLE colleges ADD COLUMN IF NOT EXISTS accepted_exams VARCHAR(255) DEFAULT '' AFTER entrance_cutoff");
echo "Columns ensured.\n";

// Update colleges with real cutoff data and accepted exams
$updates = [
  [1, 95.00, 90.00, 'JEE Advanced'],           // IIT Delhi
  [2, 96.00, 92.00, 'JEE Advanced'],            // IIT Bombay
  [3, 88.00, 85.00, 'JEE Main,JEE Advanced'],   // NIT Trichy (85% of 300 = 255)
  [4, 90.00, 85.00, 'BITSAT'],                   // BITS Pilani
  [5, 82.00, 70.00, 'VITEEE,JEE Main,JEE Advanced'], // VIT
  [6, 78.00, 65.00, 'MET (Manipal),JEE Main'],  // Manipal
  [7, 75.00, 60.00, 'SRMJEEE,JEE Main'],         // SRM
  [8, 70.00, 55.00, 'CUET,JEE Main'],            // Amity
  [9, 65.00, 50.00, 'LPUNEST,JEE Main,CUET'],    // LPU
  [10,60.00, 45.00, 'General/Other,CUET'],        // SGT
];

$s=$db->prepare("UPDATE colleges SET cutoff_scores=?,entrance_cutoff=?,accepted_exams=? WHERE college_id=?");
foreach($updates as [$id,$b,$e,$ex]){
  $s->bind_param('ddsi',$b,$e,$ex,$id);
  $s->execute();
}
$s->close();
echo "College cutoffs updated with real data.\n";
$db->close();
echo "Migration complete!\n";
?>
