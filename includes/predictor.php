<?php
// ============================================================
// AI PREDICTION ENGINE: Weighted K-Nearest Neighbors (KNN)
// Trained on real historical database records
// ============================================================

function calculateAdmissionProbability($studentMarks, $entranceScore, $collegeCutoff, $collegeEntranceCutoff, $db, $collegeId, $tier = 'Tier 2') {
    // K value (number of nearest neighbors to consider)
    $K = 15;

    // 1. Fetch historical records for this specific college
    $stmt = $db->prepare("SELECT outcome, previous_scores as board_scores, entrance_scores FROM admission_records WHERE college_id = ?");
    $stmt->bind_param('i', $collegeId);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 2. Fallback to Tier-wide data if specific college has insufficient data (< K records)
    if (count($records) < $K) {
        $stmt = $db->prepare("
            SELECT ar.outcome, ar.previous_scores as board_scores, ar.entrance_scores 
            FROM admission_records ar 
            JOIN colleges c ON ar.college_id = c.college_id 
            WHERE c.tier = ?
        ");
        $stmt->bind_param('s', $tier);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // 3. If STILL no records (empty database), fallback to a smoothed baseline formula
    if (empty($records)) {
        $boardDiff = $studentMarks - $collegeCutoff;
        $entranceDiff = $entranceScore - $collegeEntranceCutoff;
        $weightedDiff = ($boardDiff * 0.5) + ($entranceDiff * 0.5);
        $baseProb = 100 / (1 + exp(-0.1 * ($weightedDiff + 2)));
        return round(min(99, max(1, $baseProb)), 1);
    }

    // 4. Calculate Euclidean Distance for all records
    // Distance = sqrt((x2 - x1)^2 + (y2 - y1)^2)
    foreach ($records as &$rec) {
        $bDiff = floatval($rec['board_scores']) - $studentMarks;
        $eDiff = floatval($rec['entrance_scores']) - $entranceScore;
        
        // We can weight entrance scores slightly higher in the distance calculation if it's Tier 1
        $tierKey = trim(str_replace(' ', '', strtolower($tier)));
        if ($tierKey === 'tier1') {
            $eDiff *= 1.5; // Entrance score differences matter more for Tier 1
        } elseif ($tierKey === 'tier3') {
            $bDiff *= 1.5; // Board score differences matter more for Tier 3
        }

        $distance = sqrt(pow($bDiff, 2) + pow($eDiff, 2));
        $rec['distance'] = $distance;
    }

    // 5. Sort records by distance (ascending) to find the Nearest Neighbors
    usort($records, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // 6. Select the Top K Nearest Neighbors
    $nearestNeighbors = array_slice($records, 0, $K);

    // 7. Calculate Weighted Probability
    $totalWeight = 0;
    $admitWeight = 0;

    foreach ($nearestNeighbors as $nn) {
        // Inverse distance weighting (add 1 to prevent division by zero)
        // Closer neighbors have an exponentially higher impact on the prediction
        $weight = 1 / ($nn['distance'] + 1);
        
        $totalWeight += $weight;

        if ($nn['outcome'] === 'Admitted') {
            $admitWeight += $weight * 1.0;  // 100% value
        } elseif ($nn['outcome'] === 'Waitlisted') {
            $admitWeight += $weight * 0.4;  // 40% value
        }
        // Rejected adds 0 to admitWeight
    }

    // Final ML Probability Calculation
    $finalProb = ($admitWeight / $totalWeight) * 100;

    // Apply a slight safety ceiling/floor
    return round(min(99.9, max(0.1, $finalProb)), 1);
}

function getRecommendation($probability, $collegeName, $tier) {
    if ($probability >= 80) {
        return "Strong chance at $collegeName! Your profile mathematically aligns closely with historically admitted students.";
    } elseif ($probability >= 50) {
        return "Good prospects at $collegeName. You share similarities with past admits, but it's a competitive bracket.";
    } elseif ($probability >= 25) {
        return "$collegeName is a reach school. Statistical analysis of past records shows admission is possible but less likely.";
    } else {
        return "Based on historical data, $collegeName is highly unlikely. We recommend focusing on colleges where your profile matches historical admits.";
    }
}

function savePrediction($db, $studentId, $collegeId, $probability, $recommendation) {
    // Remove old prediction for this student-college pair
    $stmt = $db->prepare("DELETE FROM predictions WHERE student_id = ? AND college_id = ?");
    $stmt->bind_param('ii', $studentId, $collegeId);
    $stmt->execute();
    $stmt->close();

    // Insert fresh prediction
    $stmt = $db->prepare("INSERT INTO predictions (student_id, college_id, probability_score, recommendation) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iids', $studentId, $collegeId, $probability, $recommendation);
    $stmt->execute();
    $stmt->close();
}
?>
