<?php
require_once '../includes/config.php';
require_once '../includes/predictor.php';
requireLogin();
$db = getDB();
$studentId = $_SESSION['student_id'];
$stmt = $db->prepare("SELECT * FROM students WHERE student_id=?");
$stmt->bind_param('i',$studentId); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();
$colleges = $db->query("SELECT * FROM colleges ORDER BY tier, cutoff_scores DESC")->fetch_all(MYSQLI_ASSOC);
$initials = strtoupper(substr($student['name'],0,1));
$predictions = []; $error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    global $EXAMS;
    $marks    = floatval($_POST['student_marks'] ?? $student['student_marks']);
    $exam_name= $_POST['exam_name'] ?? $student['exam_name'] ?? 'General/Other';
    $exam_raw = floatval($_POST['exam_raw_score'] ?? $student['exam_raw_score'] ?? 0);
    $course   = $_POST['preferences'] ?? $student['preferences'] ?? '';
    $exam_total = $EXAMS[$exam_name] ?? 100;
    $ent = min(100, ($exam_raw / $exam_total) * 100);
    $sel = $_POST['college_ids'] ?? [];
    if (empty($sel)) { $error = 'Please select at least one college.'; }
    else {
        $stmt = $db->prepare("UPDATE students SET student_marks=?,entrance_scores=?,exam_name=?,exam_raw_score=?,preferences=? WHERE student_id=?");
        $stmt->bind_param('ddsdsi',$marks,$ent,$exam_name,$exam_raw,$course,$studentId);
        $stmt->execute(); $stmt->close();
        $student['student_marks']=$marks; $student['entrance_scores']=$ent;
        $student['exam_name']=$exam_name; $student['exam_raw_score']=$exam_raw;
        foreach ($sel as $cid) {
            $cid = intval($cid);
            $stmt = $db->prepare("SELECT * FROM colleges WHERE college_id=?");
            $stmt->bind_param('i',$cid); $stmt->execute();
            $col = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$col) continue;
            $prob = calculateAdmissionProbability($marks,$ent,$col['cutoff_scores'],$col['entrance_cutoff'],$db,$cid,$col['tier'],$exam_name);
            $rec  = getRecommendation($prob,$col['college_name'],$col['tier'],$exam_name);
            $scoreInfo = getScoreNeeded($cid,$marks,$col['tier'],$exam_name);
            $eligible = isExamAccepted($cid,$exam_name);
            savePrediction($db,$studentId,$cid,$prob,$rec);
            $predictions[] = ['college'=>$col,'probability'=>$prob,'recommendation'=>$rec,'score_info'=>$scoreInfo,'eligible'=>$eligible,'classification'=>getClassification($prob)];
        }
        usort($predictions, fn($a,$b)=>$b['probability']<=>$a['probability']);
    }
}
$examPct = isset($student['entrance_scores']) ? floatval($student['entrance_scores']) : 0;
$peerPct = getPeerPercentile($student['exam_name'] ?? 'General/Other', $examPct);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Run Prediction — AdmitIQ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Inter:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#090914;--surface:#131127;--card:#1c1836;--border:rgba(255,255,255,0.1);--accent:#d8b4e2;--accent2:#9d4edd;--accent3:#ff7eb3;--text:#f1f5f9;--muted:#8b85a3;--danger:#ff4d6d;--success:#06d6a0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
body::before{content:'';position:fixed;top:-20%;left:-10%;width:50vw;height:50vw;background:radial-gradient(circle,rgba(157,78,221,0.08) 0%,transparent 60%);pointer-events:none;z-index:0}
.sidebar{width:240px;min-height:100vh;background:rgba(19,17,39,0.85);backdrop-filter:blur(20px);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;z-index:100;padding:28px 0}
.sidebar-logo{font-family:'Dancing Script',cursive;font-size:3rem;font-weight:700;background:linear-gradient(135deg,var(--accent),var(--accent2),var(--accent3));-webkit-background-clip:text;-webkit-text-fill-color:transparent;padding:0 24px;margin-bottom:32px;line-height:1}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 24px;color:var(--muted);text-decoration:none;font-size:0.9rem;font-weight:500;transition:all 0.2s;border-left:3px solid transparent}
.nav-item:hover{color:var(--text);background:rgba(255,255,255,0.03)}
.nav-item.active{color:var(--accent);border-left-color:var(--accent);background:rgba(110,231,183,0.05)}
.nav-icon{font-size:1.1rem;width:20px}
.sidebar-footer{margin-top:auto;padding:20px 24px;border-top:1px solid var(--border)}
.logout-btn{display:block;text-align:center;padding:8px;margin-top:10px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);color:var(--danger);border-radius:8px;text-decoration:none;font-size:0.8rem}
.main{margin-left:240px;flex:1;padding:40px 48px;position:relative;z-index:1}
.page-title{font-family:'Inter',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:6px}
.page-sub{color:var(--muted);font-size:0.9rem;margin-bottom:28px}
.card{background:rgba(28,24,54,0.4);backdrop-filter:blur(24px);border:1px solid var(--border);border-radius:20px;padding:28px;margin-bottom:24px;box-shadow:0 8px 32px rgba(0,0,0,0.2)}
.card-title{font-family:'Inter',sans-serif;font-size:1rem;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0}
label{display:block;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);margin-bottom:6px}
input[type=number],select{width:100%;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.9rem;outline:none;transition:border-color 0.2s}
input[type=number]:focus,select:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(157,78,221,0.2)}
select option{background:var(--bg);color:var(--text)}
.score-inputs{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.input-wrapper{display:flex;align-items:center;background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:border-color 0.2s}
.input-wrapper:focus-within{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(157,78,221,0.2)}
.input-wrapper input{border:none;border-radius:0;background:transparent;width:100%;box-shadow:none}
.input-suffix{padding:0 14px;color:var(--muted);font-size:0.9rem;background:rgba(255,255,255,0.02);border-left:1px solid var(--border);white-space:nowrap}
.peer-banner{background:linear-gradient(135deg,rgba(157,78,221,0.12),rgba(255,126,179,0.08));border:1px solid rgba(157,78,221,0.25);border-radius:16px;padding:16px 24px;margin-bottom:24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.peer-num{font-family:'Inter',sans-serif;font-size:2.2rem;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.filter-bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.search-input{flex:1;min-width:200px;padding:12px 18px;background:rgba(28,24,54,0.4);backdrop-filter:blur(12px);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.95rem;outline:none;transition:border-color 0.2s}
.search-input:focus{border-color:var(--accent2)}
.filter-btn{padding:10px 18px;border-radius:20px;border:1px solid var(--border);background:none;color:var(--muted);font-size:0.82rem;cursor:pointer;transition:all 0.2s;font-family:'DM Sans',sans-serif}
.filter-btn.active,.filter-btn:hover{border-color:var(--accent);color:var(--accent);background:rgba(110,231,183,0.08)}
.college-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.college-check{position:relative;background:rgba(28,24,54,0.4);backdrop-filter:blur(12px);border:2px solid var(--border);border-radius:12px;padding:16px;cursor:pointer;transition:all 0.3s;display:flex;flex-direction:column;gap:5px}
.college-check:hover{border-color:var(--accent2);transform:translateY(-2px);box-shadow:0 4px 15px rgba(157,78,221,0.1)}
.college-check.selected{border-color:var(--accent2);background:rgba(157,78,221,0.08)}
.college-check input[type=checkbox]{position:absolute;top:14px;right:14px;width:18px;height:18px;accent-color:var(--accent);cursor:pointer}
.coll-name{font-family:'Inter',sans-serif;font-weight:700;font-size:0.88rem;padding-right:28px}
.coll-loc{font-size:0.76rem;color:var(--muted)}
.coll-cutoff{font-size:0.75rem;margin-top:2px}
.exam-pills{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
.exam-pill{padding:2px 7px;border-radius:8px;font-size:0.65rem;font-weight:600;background:rgba(157,78,221,0.12);color:var(--accent2);border:1px solid rgba(157,78,221,0.2)}
.exam-pill.match{background:rgba(6,214,160,0.12);color:var(--success);border-color:rgba(6,214,160,0.3)}
.coll-tier{display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.68rem;font-weight:600}
.tier1{background:rgba(129,140,248,0.1);color:var(--accent2)}
.tier2{background:rgba(110,231,183,0.1);color:var(--accent)}
.tier3{background:rgba(251,146,60,0.1);color:var(--accent3)}
.run-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;background:rgba(28,24,54,0.2);backdrop-filter:blur(12px);color:#fff;border:1px solid rgba(157,78,221,0.5);border-radius:12px;font-family:'Inter',sans-serif;font-size:1.05rem;font-weight:800;cursor:pointer;transition:all 0.3s;margin-top:10px}
.run-btn:hover{background:rgba(157,78,221,0.15);border-color:var(--accent2);transform:translateY(-2px);box-shadow:0 8px 25px rgba(157,78,221,0.4)}
.loader{display:none;text-align:center;padding:30px;color:var(--muted)}
.loader.show{display:block}
.spinner{display:inline-block;width:30px;height:30px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.8s linear infinite;margin-bottom:10px}
@keyframes spin{to{transform:rotate(360deg)}}
.msg{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:0.88rem}
.msg.error{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);color:var(--danger)}
/* RESULTS */
.results-header{background:linear-gradient(135deg,rgba(157,78,221,0.1),rgba(255,126,179,0.05));border:1px solid rgba(157,78,221,0.2);border-radius:16px;padding:20px 28px;margin-bottom:20px}
.rh-title{font-family:'Inter',sans-serif;font-weight:800;font-size:1.1rem;margin-bottom:6px}
.rh-sub{color:var(--muted);font-size:0.85rem}
.category-tabs{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.cat-tab{padding:8px 20px;border-radius:20px;font-size:0.82rem;font-weight:700;cursor:pointer;border:2px solid transparent;transition:all 0.2s}
.cat-safe{background:rgba(6,214,160,0.1);color:#34d399;border-color:rgba(6,214,160,0.2)}
.cat-safe.active{background:rgba(6,214,160,0.2);border-color:#34d399}
.cat-target{background:rgba(251,191,36,0.1);color:#fbbf24;border-color:rgba(251,191,36,0.2)}
.cat-target.active{background:rgba(251,191,36,0.2);border-color:#fbbf24}
.cat-reach{background:rgba(251,146,60,0.1);color:#fb923c;border-color:rgba(251,146,60,0.2)}
.cat-reach.active{background:rgba(251,146,60,0.2);border-color:#fb923c}
.cat-unlikely{background:rgba(248,113,113,0.1);color:#f87171;border-color:rgba(248,113,113,0.2)}
.cat-unlikely.active{background:rgba(248,113,113,0.2);border-color:#f87171}
.cat-all{background:rgba(157,78,221,0.1);color:var(--accent2);border-color:rgba(157,78,221,0.2)}
.cat-all.active{background:rgba(157,78,221,0.2);border-color:var(--accent2)}
.pred-list{display:flex;flex-direction:column;gap:14px}
.pred-item{background:rgba(28,24,54,0.5);backdrop-filter:blur(12px);border-radius:16px;border:1px solid var(--border);padding:20px;animation:fadeUp 0.35s ease both;transition:border-color 0.2s;position:relative}
.pred-item:hover{border-color:rgba(157,78,221,0.25)}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.pred-rank{position:absolute;top:20px;right:20px;font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:700;color:var(--muted);background:var(--surface);padding:3px 8px;border-radius:8px}
.pred-top-row{display:flex;align-items:center;gap:16px;margin-bottom:12px}
.prob-box{flex-shrink:0;min-width:80px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:0 20px 0 0;border-right:1px solid var(--border)}
.prob-num{font-family:'Inter',sans-serif;font-weight:800;font-size:2rem;line-height:1}
.prob-pct{font-size:0.7rem;color:var(--muted);font-weight:600;margin-top:3px;text-transform:uppercase;letter-spacing:0.5px}
.pred-meta{flex:1}
.pred-college-name{font-family:'Inter',sans-serif;font-weight:700;font-size:1rem;margin-bottom:4px}
.pred-badges{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px}
.class-badge{padding:3px 10px;border-radius:10px;font-size:0.72rem;font-weight:700}
.bar-outer{height:5px;background:var(--border);border-radius:3px;margin-top:8px;overflow:hidden}
.bar-inner{height:100%;border-radius:3px;transition:width 1.2s ease}
.pred-rec{font-size:0.82rem;color:var(--muted);margin-top:8px;line-height:1.6}
.info-row{display:flex;gap:20px;margin-top:10px;flex-wrap:wrap}
.info-chip{font-size:0.75rem;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:6px 12px;color:var(--muted)}
.info-chip strong{color:var(--text);display:block;font-size:0.8rem;margin-bottom:1px}
.score-needed{background:rgba(157,78,221,0.06);border:1px solid rgba(157,78,221,0.15);border-radius:10px;padding:8px 14px;font-size:0.78rem;margin-top:10px;color:var(--muted)}
.score-needed b{color:var(--accent2)}
.not-eligible{background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.2);border-radius:10px;padding:8px 14px;font-size:0.78rem;margin-top:10px;color:var(--danger)}
.best-bet-banner{background:linear-gradient(135deg,rgba(6,214,160,0.08),rgba(157,78,221,0.08));border:1px solid rgba(6,214,160,0.2);border-radius:16px;padding:16px 24px;margin-bottom:20px}
.best-bet-list{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
.best-bet-chip{background:rgba(6,214,160,0.1);border:1px solid rgba(6,214,160,0.25);border-radius:20px;padding:6px 16px;font-size:0.82rem;font-weight:600;color:#34d399}
</style>
</head>
<body>
<script>const EXAMS=<?= json_encode($EXAMS) ?>;</script>
<aside class="sidebar">
  <div class="sidebar-logo">AQ</div>
  <nav>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="predict.php"   class="nav-item active"><span class="nav-icon">🧠</span> Run Prediction</a>
    <a href="colleges.php"  class="nav-item"><span class="nav-icon">🏛️</span> Browse Colleges</a>
    <a href="history.php"   class="nav-item"><span class="nav-icon">📋</span> My History</a>
    <a href="profile.php"   class="nav-item"><span class="nav-icon">👤</span> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>
</aside>
<main class="main">
  <div class="page-title">🧠 Run Prediction</div>
  <div class="page-sub">Exam-aware AI engine using real 2022–2024 cutoff data</div>
  <!-- Peer Percentile Banner -->
  <?php if($student['entrance_scores'] > 0): ?>
  <div class="peer-banner">
    <div>
      <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px">Your <?= htmlspecialchars($student['exam_name'] ?? 'Exam') ?> Score Beats</div>
      <div class="peer-num"><?= number_format($peerPct,1) ?>%</div>
      <div style="font-size:0.78rem;color:var(--muted)">of all test-takers</div>
    </div>
    <div style="flex:1;padding-left:20px;border-left:1px solid var(--border)">
      <div style="font-size:0.85rem;margin-bottom:6px">Raw: <strong style="color:var(--text)"><?= $student['exam_raw_score'] ?? 0 ?></strong> / <?= $EXAMS[$student['exam_name'] ?? 'General/Other'] ?? 100 ?></div>
      <div style="font-size:0.85rem;margin-bottom:6px">Normalized: <strong style="color:var(--accent)"><?= number_format($student['entrance_scores'],1) ?>%</strong></div>
      <div style="font-size:0.85rem">Board: <strong style="color:var(--accent2)"><?= $student['student_marks'] ?>%</strong></div>
    </div>
    <div style="flex:1;padding-left:20px;border-left:1px solid var(--border)">
      <div style="font-size:0.78rem;color:var(--muted);margin-bottom:8px">Score Distribution</div>
      <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;position:relative">
        <div style="position:absolute;left:0;top:0;height:100%;width:<?= min(100,$peerPct) ?>%;background:linear-gradient(90deg,var(--accent2),var(--accent3));border-radius:3px;transition:width 1s ease"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php if($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" id="predForm">
    <div class="card">
      <div class="card-title"><div class="dot"></div> Your Academic Scores</div>
      <div class="score-inputs">
        <div>
          <label>Target Course</label>
          <select name="preferences" required>
            <?php global $COURSES; foreach($COURSES as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($student['preferences']??'')===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Board Marks %</label>
          <div class="input-wrapper">
            <input type="number" name="student_marks" id="input_board" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1" required oninput="updatePreview()">
            <div class="input-suffix">/ 100</div>
          </div>
        </div>
        <div>
          <label>Entrance Exam</label>
          <select name="exam_name" id="exam_select" onchange="updateExamMax();updatePreview();updateExamHighlights()" required>
            <?php global $EXAMS; foreach($EXAMS as $ex=>$max): ?>
            <option value="<?= htmlspecialchars($ex) ?>" data-max="<?= $max ?>" <?= ($student['exam_name']??'')===$ex?'selected':'' ?>><?= htmlspecialchars($ex) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Exam Raw Score</label>
          <div class="input-wrapper">
            <input type="number" name="exam_raw_score" id="exam_raw" value="<?= $student['exam_raw_score'] ?? 0 ?>" min="0" step="1" required oninput="updatePreview()">
            <div class="input-suffix" id="exam_suffix">/ 100</div>
          </div>
        </div>
      </div>
      <div style="background:rgba(157,78,221,0.05);border:1px solid rgba(157,78,221,0.15);border-radius:12px;padding:12px 18px;font-size:0.82rem;color:var(--muted)">
        Live Preview → Board: <strong id="prev_board" style="color:var(--text)">-</strong> &nbsp;|&nbsp; Exam: <strong id="prev_exam" style="color:var(--accent2)">-</strong> &nbsp;|&nbsp; Normalized: <strong id="prev_norm" style="color:var(--accent)">-</strong>
      </div>
    </div>
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent2)"></div> Select Colleges <span style="color:var(--muted);font-weight:400;font-size:0.8rem;margin-left:8px">— colleges highlighted ✅ accept your selected exam</span></div>
      <div class="filter-bar">
        <input type="text" id="search_col" placeholder="Search..." class="search-input" oninput="filterCols(currentTier,null)">
        <button type="button" class="filter-btn active" onclick="filterCols('all',this)">All</button>
        <button type="button" class="filter-btn" onclick="filterCols('Tier 1',this)">Tier 1</button>
        <button type="button" class="filter-btn" onclick="filterCols('Tier 2',this)">Tier 2</button>
        <button type="button" class="filter-btn" onclick="filterCols('Tier 3',this)">Tier 3</button>
        <button type="button" class="filter-btn" onclick="selectAll()">Select All</button>
        <button type="button" class="filter-btn" onclick="clearAll()">Clear</button>
      </div>
      <div class="college-grid" id="collegeGrid">
        <?php
        global $COLLEGE_CUTOFFS;
        foreach($colleges as $c):
          $tierCls=strtolower(str_replace(' ','',$c['tier']));
          $rid=$c['college_id'];
          $accExams=$COLLEGE_CUTOFFS[$rid]['accepted_exams'] ?? [];
          $accStr=implode(',',$accExams);
        ?>
        <label class="college-check" data-tier="<?= $c['tier'] ?>" data-exams="<?= strtolower($accStr) ?>" onclick="toggleCard(this)">
          <input type="checkbox" name="college_ids[]" value="<?= $rid ?>">
          <div class="coll-name"><?= htmlspecialchars($c['college_name']) ?></div>
          <div class="coll-loc">📍 <?= htmlspecialchars($c['location']) ?></div>
          <div class="coll-cutoff">Board min: <strong><?= $c['cutoff_scores'] ?>%</strong></div>
          <div class="exam-pills" id="pills_<?= $rid ?>">
            <?php foreach($accExams as $ae): ?>
            <span class="exam-pill"><?= htmlspecialchars($ae) ?></span>
            <?php endforeach; ?>
          </div>
          <span class="coll-tier <?= $tierCls ?>"><?= $c['tier'] ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <button type="submit" class="run-btn" id="runBtn" onclick="showLoader()">
      <span>⚡</span> Run AI Prediction Engine
    </button>
    <div class="loader" id="loader"><div class="spinner"></div><div>Analyzing your profile against real cutoff data...</div></div>
  </form>
  <!-- RESULTS -->
  <?php if(!empty($predictions)): ?>
  <?php
    $safes    = array_filter($predictions, fn($p)=>$p['classification']['label']==='Safe');
    $targets  = array_filter($predictions, fn($p)=>$p['classification']['label']==='Target');
    $reaches  = array_filter($predictions, fn($p)=>$p['classification']['label']==='Reach');
    $unlikes  = array_filter($predictions, fn($p)=>$p['classification']['label']==='Unlikely');
    $examName = $student['exam_name'] ?? 'your exam';
    $examRaw  = $student['exam_raw_score'] ?? 0;
    $examMax  = $EXAMS[$examName] ?? 100;
    $normPct  = number_format($student['entrance_scores'],1);
  ?>
  <div style="margin-top:32px">
    <div class="results-header">
      <div class="rh-title">🎓 Results Based on <?= htmlspecialchars($examName) ?> — <?= $examRaw ?>/<?= $examMax ?> (<?= $normPct ?>% normalized) &nbsp;·&nbsp; Board <?= $student['student_marks'] ?>%</div>
      <div class="rh-sub"><?= count($predictions) ?> college<?= count($predictions)>1?'s':'' ?> analyzed &nbsp;·&nbsp; <?= count($safes) ?> Safe &nbsp;·&nbsp; <?= count($targets) ?> Target &nbsp;·&nbsp; <?= count($reaches) ?> Reach &nbsp;·&nbsp; <?= count($unlikes) ?> Unlikely</div>
    </div>
    <?php if(!empty($safes)): ?>
    <div class="best-bet-banner">
      <div style="font-weight:700;color:#34d399;font-family:'Inter',sans-serif;margin-bottom:4px">✅ Your Best Bets (Safe Picks)</div>
      <div class="best-bet-list">
        <?php foreach(array_slice($safes,0,5) as $bp): ?>
        <div class="best-bet-chip"><?= htmlspecialchars($bp['college']['college_name']) ?> · <?= $bp['probability'] ?>%</div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="category-tabs">
      <button class="cat-tab cat-all active" onclick="filterResults('all',this)">All (<?= count($predictions) ?>)</button>
      <?php if(!empty($safes)): ?><button class="cat-tab cat-safe" onclick="filterResults('Safe',this)">✅ Safe (<?= count($safes) ?>)</button><?php endif; ?>
      <?php if(!empty($targets)): ?><button class="cat-tab cat-target" onclick="filterResults('Target',this)">🎯 Target (<?= count($targets) ?>)</button><?php endif; ?>
      <?php if(!empty($reaches)): ?><button class="cat-tab cat-reach" onclick="filterResults('Reach',this)">🚀 Reach (<?= count($reaches) ?>)</button><?php endif; ?>
      <?php if(!empty($unlikes)): ?><button class="cat-tab cat-unlikely" onclick="filterResults('Unlikely',this)">⚠️ Unlikely (<?= count($unlikes) ?>)</button><?php endif; ?>
    </div>
    <div class="pred-list" id="predList">
    <?php foreach($predictions as $i=>$p):
      $prob=$p['probability'];
      $col=$p['college'];
      $cls=$p['classification'];
      $color=$cls['color'];
      $tierCls=strtolower(str_replace(' ','',$col['tier']));
      $R=28; $C=2*M_PI*$R; $dash=($prob/100)*$C;
      $si=$p['score_info'];
      $delay=$i*0.07;
      $clsLower=strtolower($cls['label']);
    ?>
    <div class="pred-item" data-class="<?= $cls['label'] ?>" style="animation-delay:<?= $delay ?>s">
      <div class="pred-rank">#<?= $i+1 ?></div>
      <div class="pred-top-row">
        <div class="prob-box">
          <div class="prob-num" style="color:<?= $color ?>"><?= $prob ?>%</div>
          <div class="prob-pct">chance</div>
        </div>
        <div class="pred-meta">
          <div class="pred-college-name"><?= htmlspecialchars($col['college_name']) ?></div>
          <div class="pred-badges">
            <span class="coll-tier <?= $tierCls ?>"><?= $col['tier'] ?></span>
            <span class="class-badge" style="background:<?= $color ?>20;color:<?= $color ?>;border:1px solid <?= $color ?>40"><?= $cls['icon'] ?> <?= $cls['label'] ?></span>
            <?php if(!$p['eligible']): ?>
            <span style="background:rgba(248,113,113,0.1);color:var(--danger);border:1px solid rgba(248,113,113,0.3);padding:3px 10px;border-radius:10px;font-size:0.72rem;font-weight:700">⚠ Exam not primary</span>
            <?php endif; ?>
          </div>
          <div class="bar-outer"><div class="bar-inner" style="width:<?= $prob ?>%;background:<?= $color ?>"></div></div>
        </div>
      </div>
      <div class="pred-rec"><?= htmlspecialchars($p['recommendation']) ?></div>
      <div class="info-row">
        <div class="info-chip"><strong>📍 Location</strong><?= htmlspecialchars($col['location']) ?></div>
        <div class="info-chip"><strong>📋 Board Cutoff</strong><?= $col['cutoff_scores'] ?>%</div>
        <div class="info-chip"><strong>🎓 Entrance Cutoff</strong><?= $col['entrance_cutoff'] ?>%</div>
        <?php
          global $COLLEGE_CUTOFFS;
          $rid=$col['college_id'];
          $realCutoff=$COLLEGE_CUTOFFS[$rid]['exams'][$examName] ?? null;
          if($realCutoff !== null):
        ?>
        <div class="info-chip"><strong>📊 Real <?= htmlspecialchars($examName) ?> Cutoff</strong><?= $realCutoff ?>%</div>
        <?php endif; ?>
      </div>
      <?php if($si): $needed=$si['needed_pct']; $yourPct=floatval($student['entrance_scores']); $gap=round($yourPct-$needed,1); ?>
      <?php if($gap >= 0): ?>
      <div class="score-needed">✅ You are <b><?= $gap ?>%</b> above the score needed for 70%+ chance at <?= htmlspecialchars($col['college_name']) ?> (needed: <b><?= $needed ?>%</b> normalized, you have: <b><?= number_format($yourPct,1) ?>%</b>)</div>
      <?php else: ?>
      <div class="score-needed" style="background:rgba(251,146,60,0.06);border-color:rgba(251,146,60,0.2);color:#fb923c">📈 You need <b><?= abs($gap) ?>%</b> more to reach 70% chance at <?= htmlspecialchars($col['college_name']) ?> (need: <b><?= $needed ?>%</b>, have: <b><?= number_format($yourPct,1) ?>%</b>)</div>
      <?php endif; ?>
      <?php endif; ?>
      <?php if(!$p['eligible']): ?>
      <div class="not-eligible">⚠ <?= htmlspecialchars($col['college_name']) ?> primarily accepts: <?= htmlspecialchars(implode(', ', $COLLEGE_CUTOFFS[$rid]['accepted_exams'] ?? ['Check website'])) ?>. Your <?= htmlspecialchars($examName) ?> score may not be directly accepted.</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php $db->close(); ?>
<script>
const COLLEGE_EXAMS = <?= json_encode(array_map(fn($c)=>['id'=>$c['college_id'],'accepted'=>$COLLEGE_CUTOFFS[$c['college_id']]['accepted_exams']??[]],$colleges)) ?>;
let currentTier='all';
function toggleCard(label){const cb=label.querySelector('input[type=checkbox]');setTimeout(()=>label.classList.toggle('selected',cb.checked),0)}
function filterCols(tier,btn){
  if(btn){document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');currentTier=tier;}
  else{tier=currentTier;}
  const q=document.getElementById('search_col').value.toLowerCase();
  document.querySelectorAll('.college-check').forEach(c=>{
    const tm=(tier==='all'||c.dataset.tier===tier);
    const sm=!q||c.querySelector('.coll-name').innerText.toLowerCase().includes(q);
    c.style.display=(tm&&sm)?'flex':'none';
  });
}
function selectAll(){document.querySelectorAll('.college-check').forEach(c=>{if(c.style.display!=='none'){c.querySelector('input').checked=true;c.classList.add('selected');}})}
function clearAll(){document.querySelectorAll('.college-check').forEach(c=>{c.querySelector('input').checked=false;c.classList.remove('selected');})}
function showLoader(){setTimeout(()=>{document.getElementById('loader').classList.add('show');document.getElementById('runBtn').disabled=true;},50)}
function updateExamMax(){
  const sel=document.getElementById('exam_select');
  const max=sel.options[sel.selectedIndex].getAttribute('data-max');
  const inp=document.getElementById('exam_raw');
  document.getElementById('exam_suffix').innerText='/ '+max;
  inp.setAttribute('max',max);
}
function updatePreview(){
  const b=parseFloat(document.getElementById('input_board').value)||0;
  const r=parseFloat(document.getElementById('exam_raw').value)||0;
  const en=document.getElementById('exam_select').value;
  const max=EXAMS[en]||100;
  const norm=Math.min(100,(r/max)*100);
  document.getElementById('prev_board').innerText=b.toFixed(1)+'%';
  document.getElementById('prev_exam').innerText=en+' ('+r+'/'+max+')';
  document.getElementById('prev_norm').innerText=norm.toFixed(1)+'%';
}
function updateExamHighlights(){
  const en=document.getElementById('exam_select').value;
  COLLEGE_EXAMS.forEach(c=>{
    const pills=document.getElementById('pills_'+c.id);
    if(!pills)return;
    pills.querySelectorAll('.exam-pill').forEach(p=>{
      if(p.innerText.trim()===en){p.classList.add('match');}else{p.classList.remove('match');}
    });
  });
}
function filterResults(cls,btn){
  document.querySelectorAll('.cat-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.pred-item').forEach(el=>{
    el.style.display=(cls==='all'||el.dataset.class===cls)?'block':'none';
  });
}
document.addEventListener('DOMContentLoaded',()=>{
  if(document.getElementById('exam_select').value){updateExamMax();updatePreview();updateExamHighlights();}
});
</script>
</body>
</html>
