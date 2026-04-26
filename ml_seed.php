<?php
require_once 'includes/config.php';
$db = getDB();

echo "Starting ML Training Data Generation...\n";

// 1. Alter schema safely
$db->query("ALTER TABLE admission_records ADD COLUMN entrance_scores DECIMAL(5,2) DEFAULT 0 AFTER previous_scores");
echo "Schema updated.\n";

// 2. Clear old demo records
$db->query("TRUNCATE TABLE admission_records");

// 3. Fetch all colleges
$colleges = $db->query("SELECT * FROM colleges")->fetch_all(MYSQLI_ASSOC);

// 4. Generate records
$recordsInserted = 0;
$stmt = $db->prepare("INSERT INTO admission_records (student_id, college_id, outcome, previous_scores, entrance_scores, year) VALUES (?, ?, ?, ?, ?, ?)");

$years = [2022, 2023, 2024];

foreach ($colleges as $c) {
    $cid = $c['college_id'];
    $boardCutoff = floatval($c['cutoff_scores']);
    $entranceCutoff = floatval($c['entrance_cutoff']);
    
    // Generate 30 records per college
    for ($i = 0; $i < 40; $i++) {
        // Randomize scores around the cutoff to create realistic distributions
        // Standard deviation of ~8 points
        
        $boardScore = $boardCutoff + (mt_rand(-120, 80) / 10);
        $entranceScore = $entranceCutoff + (mt_rand(-150, 100) / 10);
        
        // Cap at 100
        if ($boardScore > 100) $boardScore = 100;
        if ($entranceScore > 100) $entranceScore = 100;
        
        $year = $years[array_rand($years)];
        $studentId = 1; // Tie all to demo student for simplicity, or we could insert random student IDs
        
        // Determine realistic outcome based on tier and scores
        $outcome = 'Rejected';
        
        $boardDiff = $boardScore - $boardCutoff;
        $entranceDiff = $entranceScore - $entranceCutoff;
        
        $tier = trim(str_replace(' ', '', strtolower($c['tier'])));
        if ($tier === 'tier1') {
            // Tier 1 is extremely strict. Both must be close to or above cutoff.
            if ($boardDiff >= -2 && $entranceDiff >= -1) {
                $outcome = (mt_rand(1, 100) > 20) ? 'Admitted' : 'Waitlisted'; // 80% admit if meeting strict cutoffs
            } elseif ($boardDiff >= -3 && $entranceDiff >= -3) {
                $outcome = 'Waitlisted';
            }
        } elseif ($tier === 'tier3') {
            // Tier 3 is forgiving. High board can compensate for bad entrance.
            if ($boardDiff + ($entranceDiff * 0.5) >= -5) {
                $outcome = (mt_rand(1, 100) > 10) ? 'Admitted' : 'Waitlisted';
            }
        } else {
            // Tier 2
            if ($boardDiff + $entranceDiff >= -3) {
                $outcome = (mt_rand(1, 100) > 30) ? 'Admitted' : 'Waitlisted';
            }
        }
        
        $stmt->bind_param('iisddi', $studentId, $cid, $outcome, $boardScore, $entranceScore, $year);
        $stmt->execute();
        $recordsInserted++;
    }
}
$stmt->close();
$db->close();

echo "Successfully generated and injected $recordsInserted historical training records!\n";
?>
