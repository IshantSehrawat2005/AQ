<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admission_predictor');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

session_start();

function isLoggedIn()  { return isset($_SESSION['student_id']); }
function requireLogin() {
    if (!isLoggedIn()) { header('Location: ../index.php'); exit; }
}

// ============================================================
// ENTRANCE EXAMS  (name => max marks)
// ============================================================
$EXAMS = [
    'JEE Main'     => 300,
    'JEE Advanced' => 360,
    'BITSAT'       => 390,
    'VITEEE'       => 125,
    'CUET'         => 800,
    'IPU CET'      => 400,
    'SRMJEEE'      => 100,
    'MET (Manipal)'=> 100,
    'LPUNEST'      => 100,
    'General/Other'=> 100,
];

// ============================================================
// TARGET COURSES
// ============================================================
$COURSES = [
    'B.Tech CSE', 'B.Tech CSAI', 'B.Tech IT',
    'B.Tech ECE', 'B.Tech EEE', 'B.Tech Mechanical',
    'B.Tech Civil', 'B.Tech Chemical', 'B.Tech Biomedical',
    'B.Tech Data Science', 'B.Tech AIML', 'B.Tech Robotics',
    'BCA', 'MCA', 'BBA', 'MBA', 'BA Economics',
];

// ============================================================
// REAL PAST-YEAR CUTOFF DATA (2022-2024 composite averages)
// ============================================================
// Format: college_id => [
//   'board'  => min board % for consideration,
//   'exams'  => [exam_name => cutoff as % of max marks],
//   'accepted_exams' => [list],
// ]
// Sources: JoSAA 2024, BITSAT 2024, VITEEE 2024, college websites
//
// JEE Main percentile mapped to % of max (300):
//   98.5 percentile ≈ score of ~250-260 → 85%
//   97 percentile   ≈ score of ~220-230 → 76%
// JEE Advanced: score out of 360
//   IIT Bombay CSE closing ~85.5% (308/360)
//   IIT Delhi  CSE closing ~83.3% (300/360)
// ============================================================
$COLLEGE_CUTOFFS = [
    // ── Tier 1 ─────────────────────────────────────────────
    1 => [ // IIT Delhi
        'board'          => 75.0,
        'accepted_exams' => ['JEE Advanced'],
        'exams' => [
            'JEE Advanced'  => 83.0,   // ~299/360, CSE Gen closing 2024
            'General/Other' => 90.0,
        ],
        'difficulty' => 'extreme',
    ],
    2 => [ // IIT Bombay
        'board'          => 75.0,
        'accepted_exams' => ['JEE Advanced'],
        'exams' => [
            'JEE Advanced'  => 85.0,   // ~306/360, CSE Gen closing 2024
            'General/Other' => 92.0,
        ],
        'difficulty' => 'extreme',
    ],
    3 => [ // NIT Trichy
        'board'          => 75.0,
        'accepted_exams' => ['JEE Main', 'JEE Advanced'],
        'exams' => [
            'JEE Main'      => 84.7,   // ~254/300, 98.4-98.9 percentile
            'JEE Advanced'  => 75.0,
            'General/Other' => 88.0,
        ],
        'difficulty' => 'very_high',
    ],
    4 => [ // BITS Pilani
        'board'          => 75.0,
        'accepted_exams' => ['BITSAT'],
        'exams' => [
            'BITSAT'        => 94.9,   // 370/390 for CSE Pilani 2024
            'General/Other' => 92.0,
        ],
        'difficulty' => 'very_high',
    ],
    // ── Tier 2 ─────────────────────────────────────────────
    5 => [ // VIT Vellore
        'board'          => 70.0,
        'accepted_exams' => ['VITEEE', 'JEE Main', 'JEE Advanced'],
        'exams' => [
            'VITEEE'        => 72.0,   // ~90/125, rank ~1-15000
            'JEE Main'      => 68.0,   // ~204/300, ~85 percentile
            'JEE Advanced'  => 60.0,
            'General/Other' => 75.0,
        ],
        'difficulty' => 'moderate',
    ],
    6 => [ // Manipal Institute
        'board'          => 60.0,
        'accepted_exams' => ['MET (Manipal)', 'JEE Main', 'General/Other'],
        'exams' => [
            'MET (Manipal)' => 55.0,   // 55/100 normalized
            'JEE Main'      => 55.0,   // ~165/300, ~75 percentile
            'JEE Advanced'  => 50.0,
            'General/Other' => 60.0,
        ],
        'difficulty' => 'moderate',
    ],
    7 => [ // SRM University
        'board'          => 60.0,
        'accepted_exams' => ['SRMJEEE', 'JEE Main', 'General/Other'],
        'exams' => [
            'SRMJEEE'       => 50.0,
            'JEE Main'      => 50.0,   // ~150/300, ~70 percentile
            'JEE Advanced'  => 45.0,
            'General/Other' => 55.0,
        ],
        'difficulty' => 'low',
    ],
    8 => [ // Amity University
        'board'          => 55.0,
        'accepted_exams' => ['CUET', 'JEE Main', 'General/Other'],
        'exams' => [
            'CUET'          => 50.0,   // 400/800
            'JEE Main'      => 45.0,   // ~135/300
            'General/Other' => 50.0,
        ],
        'difficulty' => 'low',
    ],
    // ── Tier 3 ─────────────────────────────────────────────
    9 => [ // Lovely Professional
        'board'          => 50.0,
        'accepted_exams' => ['LPUNEST', 'JEE Main', 'CUET', 'General/Other'],
        'exams' => [
            'LPUNEST'       => 40.0,
            'JEE Main'      => 38.0,
            'CUET'          => 40.0,
            'General/Other' => 42.0,
        ],
        'difficulty' => 'easy',
    ],
    10 => [ // SGT University
        'board'          => 45.0,
        'accepted_exams' => ['General/Other', 'CUET', 'JEE Main'],
        'exams' => [
            'General/Other' => 35.0,
            'CUET'          => 35.0,
            'JEE Main'      => 32.0,
        ],
        'difficulty' => 'easy',
    ],
];

// ============================================================
// JEE MAIN PERCENTILE LOOKUP (normalized score → approx rank %)
// Peer comparison: score% → approx "beats X% of test-takers"
// Based on 2024 JEE Main session statistics
// ============================================================
function getJEEMainPeerPercentile($pct) {
    // pct = (raw/300)*100
    if ($pct >= 96.7) return 99.9;
    if ($pct >= 93.3) return 99.5;
    if ($pct >= 90.0) return 99.0;
    if ($pct >= 86.7) return 98.5;
    if ($pct >= 83.3) return 97.5;
    if ($pct >= 80.0) return 96.0;
    if ($pct >= 76.7) return 94.0;
    if ($pct >= 73.3) return 91.0;
    if ($pct >= 70.0) return 87.0;
    if ($pct >= 66.7) return 82.0;
    if ($pct >= 63.3) return 76.0;
    if ($pct >= 60.0) return 70.0;
    if ($pct >= 56.7) return 63.0;
    if ($pct >= 53.3) return 55.0;
    if ($pct >= 50.0) return 47.0;
    if ($pct >= 46.7) return 40.0;
    if ($pct >= 40.0) return 30.0;
    if ($pct >= 33.3) return 20.0;
    return max(1, $pct * 0.4);
}

function getBITSATPeerPercentile($pct) {
    if ($pct >= 97.4) return 99.5;
    if ($pct >= 94.9) return 98.0;
    if ($pct >= 92.3) return 96.0;
    if ($pct >= 89.7) return 93.0;
    if ($pct >= 87.2) return 89.0;
    if ($pct >= 84.6) return 84.0;
    if ($pct >= 82.1) return 78.0;
    if ($pct >= 79.5) return 71.0;
    if ($pct >= 76.9) return 63.0;
    if ($pct >= 74.4) return 55.0;
    if ($pct >= 71.8) return 47.0;
    if ($pct >= 69.2) return 39.0;
    if ($pct >= 66.7) return 32.0;
    return max(1, $pct * 0.3);
}

function getVITEEEPeerPercentile($pct) {
    // VITEEE score out of 125
    if ($pct >= 96.0) return 99.5;
    if ($pct >= 92.0) return 97.0;
    if ($pct >= 88.0) return 94.0;
    if ($pct >= 84.0) return 90.0;
    if ($pct >= 80.0) return 85.0;
    if ($pct >= 76.0) return 78.0;
    if ($pct >= 72.0) return 70.0;
    if ($pct >= 68.0) return 60.0;
    if ($pct >= 64.0) return 50.0;
    if ($pct >= 60.0) return 40.0;
    return max(1, $pct * 0.4);
}

function getPeerPercentile($examName, $scorePct) {
    switch ($examName) {
        case 'JEE Main':     return getJEEMainPeerPercentile($scorePct);
        case 'JEE Advanced': return min(99.9, $scorePct * 1.05); // smaller pool, tighter
        case 'BITSAT':       return getBITSATPeerPercentile($scorePct);
        case 'VITEEE':       return getVITEEEPeerPercentile($scorePct);
        default:             return min(99, max(1, $scorePct));
    }
}
?>
