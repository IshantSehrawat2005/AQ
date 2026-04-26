<?php
// ============================================================
// AI PREDICTION ENGINE
// Rule-Based Logic + Historical Data Analysis
// ============================================================

function calculateAdmissionProbability($studentMarks, $entranceScore, $collegeCutoff, $collegeEntranceCutoff, $db, $collegeId) {
    // ---- Factor 1: Academic performance gap ----
    $marksDiff = $studentMarks - $collegeCutoff;
    $entranceDiff = $entranceScore - $collegeEntranceCutoff;

    // ---- Factor 2: Base probability from scores ----
    if ($marksDiff >= 10 && $entranceDiff >= 10) {
        $baseProb = 90 + min(($marksDiff + $entranceDiff) / 10, 9);
    } elseif ($marksDiff >= 5 && $entranceDiff >= 5) {
        $baseProb = 75 + ($marksDiff + $entranceDiff) / 2;
    } elseif ($marksDiff >= 0 && $entranceDiff >= 0) {
        $baseProb = 55 + ($marksDiff + $entranceDiff);
    } elseif ($marksDiff >= -5 || $entranceDiff >= -5) {
        $baseProb = 35 + max($marksDiff, $entranceDiff) * 2;
    } else {
        $baseProb = max(5, 20 + ($marksDiff + $entranceDiff));
    }

    // ---- Factor 3: Historical admission rate for this college ----
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN outcome = 'Admitted' THEN 1 ELSE 0 END) as admitted
        FROM admission_records WHERE college_id = ?");
    $stmt->bind_param('i', $collegeId);
    $stmt->execute();
    $hist = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($hist['total'] > 0) {
        $historicalRate = ($hist['admitted'] / $hist['total']) * 100;
        // Blend: 70% score-based, 30% historical
        $finalProb = ($baseProb * 0.70) + ($historicalRate * 0.30);
    } else {
        $finalProb = $baseProb;
    }

    return round(min(99, max(1, $finalProb)), 1);
}

function getRecommendation($probability, $collegeName, $tier) {
    if ($probability >= 80) {
        return "Strong chance at $collegeName! Your profile comfortably exceeds their requirements. Apply with confidence.";
    } elseif ($probability >= 60) {
        return "Good prospects at $collegeName. Your scores are competitive. Prepare a strong application to maximize your chances.";
    } elseif ($probability >= 40) {
        return "$collegeName is a reach school for you. Your scores are close to the cutoff — worth applying but ensure you have safer backups.";
    } elseif ($probability >= 20) {
        return "$collegeName is a stretch target. Consider applying if it's your dream school, but focus more on realistic options.";
    } else {
        return "Your current profile may not meet $collegeName's requirements. Focus on improving scores or consider similar Tier colleges.";
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
