<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Run Prediction — AdmitIQ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:#05080f; --surface:#0d1117; --card:#111827;
  --border:#1f2937; --accent:#6ee7b7; --accent2:#818cf8;
  --accent3:#fb923c; --text:#f1f5f9; --muted:#64748b;
  --danger:#f87171; --success:#34d399;
}
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
.sidebar {
  width:240px; min-height:100vh; background:var(--surface);
  border-right:1px solid var(--border); display:flex; flex-direction:column;
  position:fixed; left:0; top:0; bottom:0; z-index:100; padding:28px 0;
}
.sidebar-logo { font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; background:linear-gradient(135deg,var(--accent),var(--accent2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; padding:0 24px; margin-bottom:32px; }
.sidebar-logo span { -webkit-text-fill-color:var(--accent3); }
.nav-item { display:flex; align-items:center; gap:12px; padding:12px 24px; color:var(--muted); text-decoration:none; font-size:0.9rem; font-weight:500; transition:all 0.2s; border-left:3px solid transparent; }
.nav-item:hover { color:var(--text); background:rgba(255,255,255,0.03); }
.nav-item.active { color:var(--accent); border-left-color:var(--accent); background:rgba(110,231,183,0.05); }
.nav-icon { font-size:1.1rem; width:20px; }
.sidebar-footer { margin-top:auto; padding:20px 24px; border-top:1px solid var(--border); }
.logout-btn { display:block; text-align:center; padding:8px; margin-top:10px; background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.2); color:var(--danger); border-radius:8px; text-decoration:none; font-size:0.8rem; }
.main { margin-left:240px; flex:1; padding:40px 48px; }
.page-title { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:36px; }
.card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:28px; margin-bottom:24px; }
.card-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.dot { width:8px; height:8px; border-radius:50%; background:var(--accent); }

/* COLLEGE FILTER */
.filter-bar { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.filter-btn { padding:7px 16px; border-radius:20px; border:1px solid var(--border); background:none; color:var(--muted); font-size:0.82rem; cursor:pointer; transition:all 0.2s; font-family:'DM Sans',sans-serif; }
.filter-btn.active, .filter-btn:hover { border-color:var(--accent); color:var(--accent); background:rgba(110,231,183,0.08); }

/* COLLEGE GRID */
.college-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
.college-check {
  position:relative; background:var(--surface);
  border:2px solid var(--border); border-radius:12px;
  padding:16px; cursor:pointer; transition:all 0.2s;
  display:flex; flex-direction:column; gap:6px;
}
.college-check:hover { border-color:rgba(110,231,183,0.3); }
.college-check.selected { border-color:var(--accent); background:rgba(110,231,183,0.05); }
.college-check input[type=checkbox] { position:absolute; top:14px; right:14px; width:18px; height:18px; accent-color:var(--accent); cursor:pointer; }
.coll-name { font-family:'Syne',sans-serif; font-weight:700; font-size:0.9rem; padding-right:28px; }
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
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  color:#000; border:none; border-radius:12px;
  font-family:'Syne',sans-serif; font-size:1.05rem; font-weight:800;
  cursor:pointer; transition:all 0.2s; margin-top:10px;
}
.run-btn:hover { transform:translateY(-2px); box-shadow:0 12px 40px rgba(110,231,183,0.2); }
.run-btn:disabled { opacity:0.5; cursor:not-allowed; transform:none; }

/* RESULTS */
.results-section { display:none; }
.results-section.visible { display:block; }
.pred-list { display:flex; flex-direction:column; gap:12px; }
.pred-item {
  display:flex; align-items:flex-start; gap:16px;
  padding:20px; background:var(--surface); border-radius:14px;
  border:1px solid var(--border); animation:fadeUp 0.4s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(15px)} to{opacity:1;transform:translateY(0)} }
.prob-ring {
  width:72px; height:72px; flex-shrink:0;
  position:relative; display:flex; align-items:center; justify-content:center;
}
.prob-ring svg { position:absolute; transform:rotate(-90deg); }
.prob-num { font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; text-align:center; }
.prob-num small { display:block; font-size:0.6rem; font-weight:400; color:var(--muted); }
.pred-body { flex:1; }
.pred-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.pred-college { font-family:'Syne',sans-serif; font-weight:700; font-size:1rem; }
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
input[type=number]:focus { border-color:var(--accent); }
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
.score-inputs { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px; }
.profile-bar { background:rgba(110,231,183,0.05); border:1px solid rgba(110,231,183,0.15); border-radius:12px; padding:14px 18px; margin-bottom:20px; display:flex; gap:24px; align-items:center; }
.pb-item { font-size:0.82rem; }
.pb-item span { font-family:'Syne',sans-serif; font-weight:700; font-size:1rem; color:var(--accent); }
</style>
</head>
<body>
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
    $marks = floatval($_POST['student_marks'] ?? $student['student_marks']);
    $ent   = floatval($_POST['entrance_scores'] ?? $student['entrance_scores']);
    $sel   = $_POST['college_ids'] ?? [];
    if (empty($sel)) { $error = 'Please select at least one college.'; }
    else {
        // Update student scores
        $stmt = $db->prepare("UPDATE students SET student_marks=?, entrance_scores=? WHERE student_id=?");
        $stmt->bind_param('ddi',$marks,$ent,$studentId); $stmt->execute(); $stmt->close();
        $student['student_marks'] = $marks;
        $student['entrance_scores'] = $ent;

        foreach ($sel as $cid) {
            $cid = intval($cid);
            $stmt = $db->prepare("SELECT * FROM colleges WHERE college_id=?");
            $stmt->bind_param('i',$cid); $stmt->execute();
            $col = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$col) continue;
            $prob = calculateAdmissionProbability($marks,$ent,$col['cutoff_scores'],$col['entrance_cutoff'],$db,$cid);
            $rec  = getRecommendation($prob,$col['college_name'],$col['tier']);
            savePrediction($db,$studentId,$cid,$prob,$rec);
            $predictions[] = ['college'=>$col,'probability'=>$prob,'recommendation'=>$rec];
        }
        usort($predictions, fn($a,$b)=>$b['probability']<=>$a['probability']);
    }
}
?>

<aside class="sidebar">
  <div class="sidebar-logo">Admit<span>IQ</span></div>
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

  <?php if($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" id="predForm">
    <!-- SCORES -->
    <div class="card">
      <div class="card-title"><div class="dot"></div> Your Academic Scores</div>
      <div class="profile-bar">
        <div class="pb-item">Current Board Marks: <span><?= $student['student_marks'] ?>%</span></div>
        <div class="pb-item">Current Entrance: <span><?= $student['entrance_scores'] ?>%</span></div>
        <div style="font-size:0.78rem;color:var(--muted)">You can adjust scores below to simulate different scenarios</div>
      </div>
      <div class="score-inputs">
        <div>
          <label>Board Marks %</label>
          <input type="number" name="student_marks" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1" required>
        </div>
        <div>
          <label>Entrance Score %</label>
          <input type="number" name="entrance_scores" value="<?= $student['entrance_scores'] ?>" min="0" max="100" step="0.1" required>
        </div>
      </div>
    </div>

    <!-- COLLEGE SELECTION -->
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent2)"></div> Select Colleges to Predict</div>
      <div class="filter-bar">
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
        ?>
        <label class="college-check" data-tier="<?= $c['tier'] ?>" onclick="toggleCard(this)">
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
          <div class="prob-ring">
            <svg width="72" height="72" viewBox="0 0 72 72">
              <circle cx="36" cy="36" r="<?=$r?>" fill="none" stroke="var(--border)" stroke-width="5"/>
              <circle cx="36" cy="36" r="<?=$r?>" fill="none" stroke="<?=$color?>"
                stroke-width="5" stroke-linecap="round"
                stroke-dasharray="<?=$dash?> <?=$circ?>" />
            </svg>
            <div class="prob-num"><?=$prob?>%<small>chance</small></div>
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
function toggleCard(label) {
  const cb = label.querySelector('input[type=checkbox]');
  setTimeout(() => label.classList.toggle('selected', cb.checked), 0);
}
function filterColleges(tier, btn) {
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.college-check').forEach(c=>{
    c.style.display = (tier==='all'||c.dataset.tier===tier)?'':'none';
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
</script>
</body>
</html>
