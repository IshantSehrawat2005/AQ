<?php
require_once 'includes/config.php';
$db = getDB();

echo "Starting migration...\n";

// 1. Alter students table
$db->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_name VARCHAR(100) DEFAULT 'General/Other'");
$db->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_raw_score DECIMAL(10,2) DEFAULT 0");

// 2. Alter colleges table
$db->query("ALTER TABLE colleges ADD COLUMN IF NOT EXISTS accepted_exams VARCHAR(255) DEFAULT 'JEE Main, IPU CET, CUET'");

// 3. Map exams to colleges based on their name
$mappings = [
    'IIT Bombay' => 'JEE Advanced',
    'IIT Delhi' => 'JEE Advanced',
    'NIT Trichy' => 'JEE Main',
    'NIT Delhi' => 'JEE Main',
    'DTU' => 'JEE Main',
    'NSUT' => 'JEE Main',
    'IIIT Delhi' => 'JEE Main',
    'IGDTUW' => 'JEE Main',
    'BITS Pilani' => 'BITSAT',
    'VIT Vellore' => 'VITEEE',
    'Jamia Millia Islamia' => 'JEE Main',
    'JNU' => 'JEE Main',
    'Shiv Nadar' => 'JEE Main',
    'Ambedkar University' => 'CUET',
    'Delhi University' => 'CUET'
];

foreach ($mappings as $keyword => $exam) {
    $stmt = $db->prepare("UPDATE colleges SET accepted_exams = ? WHERE college_name LIKE ?");
    $like = "%$keyword%";
    $stmt->bind_param('ss', $exam, $like);
    $stmt->execute();
    $stmt->close();
}

echo "Migration completed successfully!\n";
?>
