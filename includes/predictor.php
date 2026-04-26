<?php
// ============================================================
// AI PREDICTION ENGINE v3 — Real Cutoff + Exam-Aware
// ============================================================
// Architecture:
//   1. Exam eligibility check (college accepts this exam?)
//   2. Primary signal: calibrated sigmoid using REAL cutoff data
//      from $COLLEGE_CUTOFFS in config.php
//   3. Secondary: KNN blend from historical records (±35% weight)
//   4. Board marks as an additional gate (colleges need min board%)
// ============================================================

function calculateAdmissionProbability(
    $studentMarks,        // board % (0-100)
    $entranceScore,       // normalized entrance score (0-100)
    $collegeCutoff,       // legacy: board cutoff from DB (used if no real data)
    $collegeEntranceCutoff, // legacy: entrance cutoff from DB
    $db,
    $collegeId,
    $tier = 'Tier 2',
    $examName = 'General/Other'  // NEW: which exam the student took
) {
    global $COLLEGE_CUTOFFS;

    $tierKey = trim(str_replace(' ', '', strtolower($tier)));

    // ── 1. Load real cutoff data if available ────────────────
    $realData       = $COLLEGE_CUTOFFS[$collegeId] ?? null;
    $acceptedExams  = $realData['accepted_exams'] ?? null;
    $difficulty     = $realData['difficulty'] ?? 'moderate';

    // Use real exam-specific cutoff if we have it
    $realExamCutoff  = null;
    $realBoardCutoff = null;
    if ($realData) {
        $realBoardCutoff = $realData['board'] ?? $collegeCutoff;
        // Try exact exam match first, then fallback to General/Other
        $realExamCutoff  = $realData['exams'][$examName]
                        ?? $realData['exams']['General/Other']
                        ?? $collegeEntranceCutoff;
    } else {
        $realBoardCutoff = $collegeCutoff;
        $realExamCutoff  = $collegeEntranceCutoff;
    }

    // ── 2. Hard gate: board marks too low ────────────────────
    // If student's board is significantly below minimum requirement
    $boardGate = ($realBoardCutoff * 0.90); // 10% grace below stated min
    if ($studentMarks < $boardGate) {
        // Very unlikely, but not impossible for tier3
        if ($tierKey === 'tier3') {
            return 8.0;
        }
        return 3.0;
    }

    // ── 3. Exam compatibility check ──────────────────────────
    // If college has accepted_exams list and this exam isn't on it → penalize
    $examAccepted = true;
    if ($acceptedExams !== null && $examName !== 'General/Other') {
        $examAccepted = in_array($examName, $acceptedExams);
    }

    // ── 4. Tier parameters ────────────────────────────────────
    switch ($tierKey) {
        case 'tier1':
            $steepness = 0.60;
            $boardW    = 0.35;   // Tier 1 cares more about entrance
            $entrW     = 0.65;
            break;
        case 'tier3':
            $steepness = 0.28;
            $boardW    = 0.65;   // Board-heavy
            $entrW     = 0.35;
            break;
        default:
            $steepness = 0.42;
            $boardW    = 0.48;
            $entrW     = 0.52;
            break;
    }

    // For extreme difficulty colleges, raise the bar further
    if ($difficulty === 'extreme') { $steepness = 0.70; }
    if ($difficulty === 'very_high') { $steepness = 0.55; }

    // ── 5. Compute combined weighted margin ───────────────────
    // margin > 0 → student is above cutoff (good)
    // margin < 0 → student is below cutoff (bad)
    $boardMargin    = $studentMarks  - $realBoardCutoff;
    $entranceMargin = $entranceScore - $realExamCutoff;
    $combinedMargin = ($boardMargin * $boardW) + ($entranceMargin * $entrW);

    // ── 6. Primary sigmoid probability ───────────────────────
    // At margin=0 → 50%, rises to ~95% at +8, drops to ~5% at -8
    $sigProb = 100.0 / (1.0 + exp(-$steepness * $combinedMargin));

    // If exam not accepted by college, cap the probability
    if (!$examAccepted) {
        $sigProb = min($sigProb, 35.0); // Large penalty
    }

    // ── 7. KNN blend from historical records ─────────────────
    $K = 10;
    $stmt = $db->prepare("SELECT outcome, previous_scores AS board_scores, entrance_scores FROM admission_records WHERE college_id = ?");
    $stmt->bind_param('i', $collegeId);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($records) < $K) {
        $stmt = $db->prepare("
            SELECT ar.outcome, ar.previous_scores AS board_scores, ar.entrance_scores
            FROM admission_records ar
            JOIN colleges c ON ar.college_id = c.college_id
            WHERE c.tier = ?
        ");
        $stmt->bind_param('s', $tier);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    $knnProb = $sigProb; // fallback
    if (!empty($records)) {
        foreach ($records as &$rec) {
            $bDiff = (floatval($rec['board_scores'])   - $studentMarks)  * $boardW;
            $eDiff = (floatval($rec['entrance_scores']) - $entranceScore) * $entrW;
            $rec['distance'] = sqrt($bDiff*$bDiff + $eDiff*$eDiff);
        }
        unset($rec);
        usort($records, fn($a,$b) => $a['distance'] <=> $b['distance']);
        $knn = array_slice($records, 0, $K);

        $totalW = 0.0; $admitW = 0.0;
        foreach ($knn as $nn) {
            $w = 1.0 / ($nn['distance'] + 0.5);
            $totalW += $w;
            if ($nn['outcome'] === 'Admitted')   { $admitW += $w * 1.0; }
            elseif ($nn['outcome'] === 'Waitlisted') { $admitW += $w * 0.35; }
        }
        if ($totalW > 0) {
            $knnProb = ($admitW / $totalW) * 100.0;
        }
    }

    // ── 8. Blend: sigmoid 70% + KNN 30% ─────────────────────
    $finalProb = ($sigProb * 0.70) + ($knnProb * 0.30);

    return round(min(98.0, max(1.0, $finalProb)), 1);
}

// ── Classification ────────────────────────────────────────────
function getClassification($probability) {
    if ($probability >= 70) return ['label' => 'Safe',     'color' => '#34d399', 'icon' => '✅'];
    if ($probability >= 45) return ['label' => 'Target',   'color' => '#fbbf24', 'icon' => '🎯'];
    if ($probability >= 20) return ['label' => 'Reach',    'color' => '#fb923c', 'icon' => '🚀'];
    return                         ['label' => 'Unlikely', 'color' => '#f87171', 'icon' => '⚠️'];
}

// ── Exam eligibility ──────────────────────────────────────────
function isExamAccepted($collegeId, $examName) {
    global $COLLEGE_CUTOFFS;
    if ($examName === 'General/Other') return true;
    $data = $COLLEGE_CUTOFFS[$collegeId] ?? null;
    if (!$data || !isset($data['accepted_exams'])) return true; // unknown = assume ok
    return in_array($examName, $data['accepted_exams']);
}

// ── "What score do I need?" reverse calculator ───────────────
// Returns: the entrance score % needed for ~70% admission chance
function getScoreNeeded($collegeId, $studentMarks, $tier, $examName = 'General/Other') {
    global $COLLEGE_CUTOFFS;
    $realData = $COLLEGE_CUTOFFS[$collegeId] ?? null;
    if (!$realData) return null;

    $realExamCutoff  = $realData['exams'][$examName] ?? $realData['exams']['General/Other'] ?? null;
    $realBoardCutoff = $realData['board'] ?? 75;
    $difficulty      = $realData['difficulty'] ?? 'moderate';

    $tierKey   = trim(str_replace(' ', '', strtolower($tier)));
    $boardW    = ($tierKey === 'tier1') ? 0.35 : (($tierKey === 'tier3') ? 0.65 : 0.48);
    $entrW     = 1.0 - $boardW;
    $steepness = match($difficulty) {
        'extreme'   => 0.70,
        'very_high' => 0.55,
        'moderate'  => 0.42,
        default     => 0.28,
    };

    // For 70% probability: sigmoid = 70 → solve for combinedMargin
    // 70 = 100/(1+e^(-k*m)) → e^(-k*m) = 3/7 → m = -ln(3/7)/k = ln(7/3)/k
    $targetMargin = log(7.0/3.0) / $steepness; // ~0.847/k
    $boardMargin  = $studentMarks - $realBoardCutoff;
    // combinedMargin = boardMargin*boardW + entranceMargin*entrW = targetMargin
    // → entranceMargin = (targetMargin - boardMargin*boardW) / entrW
    if ($entrW < 0.01) return null;
    $neededEntrMargin = ($targetMargin - ($boardMargin * $boardW)) / $entrW;
    $neededEntrScore  = $realExamCutoff + $neededEntrMargin;

    return [
        'needed_pct'  => round(min(99, max(0, $neededEntrScore)), 1),
        'your_pct'    => null, // filled by caller
        'cutoff_pct'  => $realExamCutoff,
    ];
}

// ── Recommendation text ───────────────────────────────────────
function getRecommendation($probability, $collegeName, $tier, $examName = null) {
    $cl = getClassification($probability);
    $label = $cl['label'];
    if ($label === 'Safe') {
        return "Strong fit for $collegeName! Your profile closely matches historically admitted students — consider this your safety option.";
    } elseif ($label === 'Target') {
        return "Good prospects at $collegeName. You are in a competitive band — a solid application can tip the scales.";
    } elseif ($label === 'Reach') {
        return "$collegeName is a reach school. Statistically possible, but you'll want stronger scores or a standout application.";
    } else {
        return "Based on past cutoffs, $collegeName is unlikely with your current profile. Consider building a backup list.";
    }
}

// ── Save prediction ───────────────────────────────────────────
function savePrediction($db, $studentId, $collegeId, $probability, $recommendation) {
    $stmt = $db->prepare("DELETE FROM predictions WHERE student_id = ? AND college_id = ?");
    $stmt->bind_param('ii', $studentId, $collegeId);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO predictions (student_id, college_id, probability_score, recommendation) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iids', $studentId, $collegeId, $probability, $recommendation);
    $stmt->execute();
    $stmt->close();
}
?>
