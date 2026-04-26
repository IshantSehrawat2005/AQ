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

$predictions = [];
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    global $EXAMS;
    $marks = floatval($_POST['student_marks'] ?? $student['student_marks']);
    $exam_name = $_POST['exam_name'] ?? $student['exam_name'] ?? 'General/Other';
    $exam_raw = floatval($_POST['exam_raw_score'] ?? $student['exam_raw_score'] ?? 0);
    $course = $_POST['preferences'] ?? $student['preferences'] ?? '';
    
    $exam_total = $EXAMS[$exam_name] ?? 100;
    $ent = ($exam_raw / $exam_total) * 100;
    if ($ent > 100) $ent = 100;

    $sel   = $_POST['college_ids'] ?? [];
    if (empty($sel)) { $error = 'Please select at least one college.'; }
    else {
        // Update student scores
        $stmt = $db->prepare("UPDATE students SET student_marks=?, entrance_scores=?, exam_name=?, exam_raw_score=?, preferences=? WHERE student_id=?");
        $stmt->bind_param('ddsdsi', $marks, $ent, $exam_name, $exam_raw, $course, $studentId); 
        $stmt->execute(); $stmt->close();
        $student['student_marks'] = $marks;
        $student['entrance_scores'] = $ent;
        $student['exam_name'] = $exam_name;
        $student['exam_raw_score'] = $exam_raw;
        $student['preferences'] = $course;

        foreach ($sel as $cid) {
            $cid = intval($cid);
            $stmt = $db->prepare("SELECT * FROM colleges WHERE college_id=?");
            $stmt->bind_param('i',$cid); $stmt->execute();
            $col = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$col) continue;
            $prob = calculateAdmissionProbability($marks,$ent,$col['cutoff_scores'],$col['entrance_cutoff'],$db,$cid, $col['tier']);
            $rec  = getRecommendation($prob,$col['college_name'],$col['tier']);
            savePrediction($db,$studentId,$cid,$prob,$rec);
            $predictions[] = ['college'=>$col,'probability'=>$prob,'recommendation'=>$rec];
        }
        usort($predictions, fn($a,$b)=>$b['probability']<=>$a['probability']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Run Prediction — AdmitIQ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Inter:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #090914;
  --surface: #131127;
  --card: #1c1836;
  --border: rgba(255, 255, 255, 0.1);
  --accent: #d8b4e2;
  --accent2: #9d4edd;
  --accent3: #ff7eb3;
  --text: #f1f5f9;
  --muted: #8b85a3;
  --danger: #ff4d6d;
  --success: #06d6a0;
}
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

/* Background glow */
body::before {
  content: '';
  position: fixed;
  top: -20%; left: -10%;
  width: 50vw; height: 50vw;
  background: radial-gradient(circle, rgba(157, 78, 221, 0.08) 0%, transparent 60%);
  pointer-events: none;
  z-index: 0;
}
.sidebar {
  width:240px; min-height:100vh; background:rgba(19, 17, 39, 0.8); backdrop-filter: blur(20px);
  border-right:1px solid var(--border); display:flex; flex-direction:column;
  position:fixed; left:0; top:0; bottom:0; z-index:100; padding:28px 0;
}
.sidebar-logo {
  font-family:'Dancing Script', cursive; font-size:3rem; font-weight:700; letter-spacing:2px;
  background:linear-gradient(135deg,var(--accent),var(--accent2),var(--accent3));
  -webkit-background-clip:text; -webkit-text-fill-color:transparent;
  padding: 0 24px; margin-bottom: 32px; line-height:1; margin-top: -10px;
}
.nav-item { display:flex; align-items:center; gap:12px; padding:12px 24px; color:var(--muted); text-decoration:none; font-size:0.9rem; font-weight:500; transition:all 0.2s; border-left:3px solid transparent; }
.nav-item:hover { color:var(--text); background:rgba(255,255,255,0.03); }
.nav-item.active { color:var(--accent); border-left-color:var(--accent); background:rgba(110,231,183,0.05); }
.nav-icon { font-size:1.1rem; width:20px; }
.sidebar-footer { margin-top:auto; padding:20px 24px; border-top:1px solid var(--border); }
.logout-btn { display:block; text-align:center; padding:8px; margin-top:10px; background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.2); color:var(--danger); border-radius:8px; text-decoration:none; font-size:0.8rem; }
.main { margin-left:240px; flex:1; padding:40px 48px; position:relative; z-index:1; }
.page-title { font-family:'Inter',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:36px; }
.card { background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); border:1px solid var(--border); border-radius:20px; padding:28px; margin-bottom:24px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.card-title { font-family:'Inter',sans-serif; font-size:1rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.dot { width:8px; height:8px; border-radius:50%; background:var(--accent); }

/* COLLEGE FILTER */
.filter-bar { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
.search-input {
  flex:1; min-width:200px; padding:12px 18px;
  background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(12px); border:1px solid var(--border); border-radius:12px;
  color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.95rem; outline:none;
  transition:border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
.filter-btn { padding:10px 18px; border-radius:20px; border:1px solid var(--border); background:none; color:var(--muted); font-size:0.82rem; cursor:pointer; transition:all 0.2s; font-family:'DM Sans',sans-serif; }
.filter-btn.active, .filter-btn:hover { border-color:var(--accent); color:var(--accent); background:rgba(110,231,183,0.08); }

/* COLLEGE GRID */
.college-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
.college-check {
  position:relative; background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(12px);
  border:2px solid var(--border); border-radius:12px;
  padding:16px; cursor:pointer; transition:all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  display:flex; flex-direction:column; gap:6px;
}
.college-check:hover { border-color:var(--accent2); transform:translateY(-2px); box-shadow: 0 4px 15px rgba(157, 78, 221, 0.1); }
.college-check.selected { border-color:var(--accent2); background:rgba(157, 78, 221, 0.08); }
.college-check input[type=checkbox] { position:absolute; top:14px; right:14px; width:18px; height:18px; accent-color:var(--accent); cursor:pointer; }
.coll-name { font-family:'Inter',sans-serif; font-weight:700; font-size:0.9rem; padding-right:28px; }
.coll-loc { font-size:0.78rem; color:var(--muted); }
.coll-cutoff { font-size:0.78rem; margin-top:4px; }
.coll-tier { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:600; }
.tier1 { background:rgba(129,140,248,0.1); color:var(--accent2); }
.tier2 { background:rgba(110,231,183,0.1); color:var(--accent); }
.tier3 { background:rgba(251,146,60,0.1); color:var(--accent3); }

/* RUN BUTTON */
.run-btn {
  display:flex; align-items:center; justify-content:center; gap:10px;
  width:100%; padding:16px;
  background:rgba(28, 24, 54, 0.2); backdrop-filter: blur(12px);
  color:#fff; border:1px solid rgba(157, 78, 221, 0.5); border-radius:12px;
  font-family:'Inter',sans-serif; font-size:1.05rem; font-weight:800;
  cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1); margin-top:10px; box-shadow: 0 4px 15px rgba(157, 78, 221, 0.1);
}
.run-btn:hover { background:rgba(157, 78, 221, 0.15); border-color:var(--accent2); transform:translateY(-2px); box-shadow:0 8px 25px rgba(157, 78, 221, 0.4); }
.run-btn:disabled { opacity:0.5; cursor:not-allowed; transform:none; }

/* RESULTS */
.results-section { display:none; }
.results-section.visible { display:block; }
.pred-list { display:flex; flex-direction:column; gap:12px; }
.pred-item {
  display:flex; align-items:flex-start; gap:16px;
  padding:20px; background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(12px); border-radius:14px;
  border:1px solid var(--border); animation:fadeUp 0.4s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(15px)} to{opacity:1;transform:translateY(0)} }
.prob-text {
  flex-shrink:0; display:flex; flex-direction:column; align-items:center; justify-content:center;
  min-width: 90px; padding-right: 16px; border-right: 1px solid var(--border);
}
.prob-num { font-family:'Inter',sans-serif; font-weight:800; font-size:2.2rem; text-align:center; line-height: 1; }
.prob-num small { display:block; font-size:0.7rem; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-top:6px; }
.pred-body { flex:1; }
.pred-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.pred-college { font-family:'Inter',sans-serif; font-weight:700; font-size:1rem; }
.pred-rec { font-size:0.83rem; color:var(--muted); line-height:1.6; margin-top:6px; }
.pred-detail { display:flex; gap:16px; margin-top:10px; }
.pd-item { font-size:0.75rem; color:var(--muted); }
.pd-item strong { color:var(--text); }
.bar-outer { height:4px; background:var(--border); border-radius:2px; margin-top:8px; overflow:hidden; }
.bar-inner { height:100%; border-radius:2px; transition:width 1s ease; }

.msg { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.88rem; }
.msg.error { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--danger); }

.loader { display:none; text-align:center; padding:30px; color:var(--muted); }
.loader.show { display:block; }
.spinner { display:inline-block; width:30px; height:30px; border:3px solid var(--border); border-top-color:var(--accent); border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:10px; }
@keyframes spin { to{transform:rotate(360deg)} }

input[type=number] { width:100%; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:border-color 0.2s; }
input[type=number]:focus, select:focus { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
.score-inputs { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
.profile-bar { background:rgba(157, 78, 221, 0.05); border:1px solid rgba(157, 78, 221, 0.15); border-radius:16px; padding:18px 24px; margin-bottom:20px; display:flex; gap:30px; align-items:center; flex-wrap:wrap; }
.pb-item { font-size:0.85rem; color:var(--muted); }
.pb-item span { font-family:'Inter',sans-serif; font-weight:700; font-size:1.1rem; color:var(--accent); margin-left:6px; }
.input-wrapper { display:flex; align-items:center; background:var(--surface); border:1px solid var(--border); border-radius:10px; overflow:hidden; transition:border-color 0.2s; }
.input-wrapper:focus-within { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
.input-wrapper input { border:none; border-radius:0; background:transparent; width:100%; box-shadow:none; }
.input-suffix { padding:0 14px; color:var(--muted); font-size:0.9rem; font-family:'DM Sans',sans-serif; background:rgba(255,255,255,0.02); border-left:1px solid var(--border); white-space:nowrap; }
select { width:100%; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:border-color 0.2s, box-shadow 0.2s; }
select option { background:var(--bg); color:var(--text); }
</style>
</head>
<body>
<script>
// Make exams object available to JS for real-time calculation
const EXAMS = <?= json_encode($EXAMS) ?>;
</script>


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
  <div class="page-sub">Select colleges and run the AI engine to see your admission probabilities</div>

  <div style="background: rgba(157, 78, 221, 0.05); border: 1px solid rgba(157, 78, 221, 0.2); border-radius: 16px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🤖</div>
    <div>
      <div style="font-weight: 700; color: var(--accent); margin-bottom: 4px;">How the Prediction Engine Works</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Our algorithm calculates your admission probability by analyzing your board marks and entrance scores against the historical cutoffs of your selected colleges. It adjusts the probability based on the college tier, giving a higher weight to entrance exams for top-tier institutions.</div>
    </div>
  </div>

  <?php if($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" id="predForm">
    <!-- SCORES -->
    <div class="card">
      <div class="card-title"><div class="dot"></div> Your Academic Scores</div>
      <div class="profile-bar">
        <div class="pb-item">Board Marks:<span id="disp_board"><?= $student['student_marks'] ?>%</span></div>
        <div class="pb-item">Exam:<span id="disp_exam"><?= htmlspecialchars($student['exam_name'] ?? 'None') ?></span></div>
        <div class="pb-item">Raw Score:<span id="disp_raw"><?= $student['exam_raw_score'] ?? 0 ?></span></div>
        <div class="pb-item">Normalized:<span id="disp_norm"><?= number_format($student['entrance_scores'], 1) ?>%</span></div>
      </div>
      <div style="font-size:0.78rem;color:var(--muted); margin-bottom: 20px;">You can adjust scores below to simulate different scenarios</div>
      <div class="score-inputs">
        <div>
          <label>Target Course</label>
          <select name="preferences" required>
            <?php global $COURSES; foreach ($COURSES as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= ($student['preferences'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Board Marks %</label>
          <div class="input-wrapper">
            <input type="number" name="student_marks" id="input_board" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1" required oninput="updateLivePreview()">
            <div class="input-suffix">/ 100</div>
          </div>
        </div>
        <div>
          <label>Entrance Exam</label>
          <select name="exam_name" id="exam_select" onchange="updateExamMax(); updateLivePreview();" required>
            <?php global $EXAMS; foreach ($EXAMS as $ex => $max): ?>
              <option value="<?= htmlspecialchars($ex) ?>" data-max="<?= $max ?>" <?= ($student['exam_name'] ?? '') === $ex ? 'selected' : '' ?>><?= htmlspecialchars($ex) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Exam Raw Score</label>
          <div class="input-wrapper">
            <input type="number" name="exam_raw_score" id="exam_raw" value="<?= isset($student['exam_raw_score']) ? $student['exam_raw_score'] : 0 ?>" min="0" step="1" required oninput="updateLivePreview()">
            <div class="input-suffix" id="exam_suffix">/ 100</div>
          </div>
        </div>
      </div>
    </div>

    <!-- COLLEGE SELECTION -->
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent2)"></div> Select Colleges to Predict</div>
      <div class="filter-bar">
        <input type="text" id="search_college" placeholder="Search college name..." class="search-input" oninput="filterColleges(currentTier, null)">
        <button type="button" class="filter-btn active" onclick="filterColleges('all',this)">All</button>
        <button type="button" class="filter-btn" onclick="filterColleges('Tier 1',this)">Tier 1</button>
        <button type="button" class="filter-btn" onclick="filterColleges('Tier 2',this)">Tier 2</button>
        <button type="button" class="filter-btn" onclick="filterColleges('Tier 3',this)">Tier 3</button>
        <button type="button" class="filter-btn" onclick="selectAll()">Select All</button>
        <button type="button" class="filter-btn" onclick="clearAll()">Clear</button>
      </div>
      <div class="college-grid" id="collegeGrid">
        <?php foreach($colleges as $c): 
          $tierCls = strtolower(str_replace(' ','',$c['tier']));
          $exams = isset($c['accepted_exams']) ? $c['accepted_exams'] : 'JEE Main';
          // Filter logic in PHP for initial load - ALWAYS SHOW
          $display = 'flex';
        ?>
        <label class="college-check" data-tier="<?= $c['tier'] ?>" data-exams="<?= strtolower($exams) ?>" data-courses="<?= strtolower($c['course_list']) ?>" onclick="toggleCard(this)" style="display: <?= $display ?>;">
          <input type="checkbox" name="college_ids[]" value="<?= $c['college_id'] ?>">
          <div class="coll-name"><?= htmlspecialchars($c['college_name']) ?></div>
          <div class="coll-loc">📍 <?= htmlspecialchars($c['location']) ?></div>
          <div class="coll-cutoff">Cutoff: <strong><?= $c['cutoff_scores'] ?>%</strong> &nbsp; Entrance: <strong><?= $c['entrance_cutoff'] ?>%</strong></div>
          <span class="coll-tier <?= $tierCls ?>"><?= $c['tier'] ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="run-btn" id="runBtn" onclick="showLoader()">
      <span>⚡</span> Run AI Prediction Engine
    </button>
    <div class="loader" id="loader"><div class="spinner"></div><div>Analyzing your profile against historical data...</div></div>
  </form>

  <!-- RESULTS -->
  <?php if (!empty($predictions)): ?>
  <div class="results-section visible" style="margin-top:32px">
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent3)"></div> Prediction Results — <?= count($predictions) ?> College<?= count($predictions)>1?'s':'' ?> Analyzed</div>
      <div class="pred-list">
      <?php foreach($predictions as $i=>$p):
        $prob = $p['probability'];
        $col  = $p['college'];
        $color = $prob>=70 ? '#34d399' : ($prob>=40 ? '#fb923c' : '#f87171');
        $r = 32; $circ = 2*pi()*$r; $dash = ($prob/100)*$circ;
        $tierCls = strtolower(str_replace(' ','',$col['tier']));
        $delay = $i*0.08;
      ?>
        <div class="pred-item" style="animation-delay:<?= $delay ?>s">
          <div class="prob-text">
            <div class="prob-num" style="color: <?=$color?>"><?=$prob?>%<small style="color: var(--muted)">chance</small></div>
          </div>
          <div class="pred-body">
            <div class="pred-top">
              <div class="pred-college"><?= htmlspecialchars($col['college_name']) ?></div>
              <span class="coll-tier <?=$tierCls?>"><?= $col['tier'] ?></span>
            </div>
            <div class="bar-outer"><div class="bar-inner" style="width:<?=$prob?>%;background:<?=$color?>"></div></div>
            <div class="pred-rec"><?= htmlspecialchars($p['recommendation']) ?></div>
            <div class="pred-detail">
              <div class="pd-item">📍 <strong><?= htmlspecialchars($col['location']) ?></strong></div>
              <div class="pd-item">Cutoff: <strong><?= $col['cutoff_scores'] ?>%</strong></div>
              <div class="pd-item">Entrance: <strong><?= $col['entrance_cutoff'] ?>%</strong></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php $db->close(); ?>
<script>
let currentTier = 'all';
function toggleCard(label) {
  const cb = label.querySelector('input[type=checkbox]');
  setTimeout(() => label.classList.toggle('selected', cb.checked), 0);
}
function filterColleges(tier, btn) {
  if (btn) {
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    currentTier = tier;
  } else {
    tier = currentTier;
  }
  const searchQ = document.getElementById('search_college').value.toLowerCase();
  
  document.querySelectorAll('.college-check').forEach(c=>{
    const name = c.querySelector('.coll-name').innerText.toLowerCase();
    const tierMatch = (tier === 'all' || c.dataset.tier === tier);
    const searchMatch = !searchQ || name.includes(searchQ);
    c.style.display = (tierMatch && searchMatch) ? 'flex' : 'none';
  });
}
function selectAll() {
  document.querySelectorAll('.college-check').forEach(c=>{
    if(c.style.display!=='none'){
      c.querySelector('input').checked=true;
      c.classList.add('selected');
    }
  });
}
function clearAll() {
  document.querySelectorAll('.college-check').forEach(c=>{
    c.querySelector('input').checked=false;
    c.classList.remove('selected');
  });
}
function showLoader() {
  setTimeout(()=>{
    document.getElementById('loader').classList.add('show');
    document.getElementById('runBtn').disabled=true;
  },50);
}
function updateExamMax() {
  const sel = document.getElementById('exam_select');
  const max = sel.options[sel.selectedIndex].getAttribute('data-max');
  const input = document.getElementById('exam_raw');
  const suffix = document.getElementById('exam_suffix');
  if (max) {
    input.setAttribute('max', max);
    suffix.innerText = "/ " + max;
  }
}

function updateLivePreview() {
  const boardInput = document.getElementById('input_board').value;
  const rawInput = document.getElementById('exam_raw').value;
  const examName = document.getElementById('exam_select').value;
  
  const boardStr = boardInput ? parseFloat(boardInput).toFixed(2) + '%' : '0.00%';
  document.getElementById('disp_board').innerText = boardStr;
  
  document.getElementById('disp_exam').innerText = examName;
  document.getElementById('disp_raw').innerText = rawInput ? rawInput : '0';
  
  const max = EXAMS[examName] || 100;
  let norm = (parseFloat(rawInput || 0) / max) * 100;
  if (norm > 100) norm = 100;
  document.getElementById('disp_norm').innerText = norm.toFixed(1) + '%';
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('exam_select').value) updateExamMax();
});
</script>
</body>
</html>
