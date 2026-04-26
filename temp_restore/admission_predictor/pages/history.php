<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My History — AdmitIQ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --bg:#05080f; --surface:#0d1117; --card:#111827; --border:#1f2937; --accent:#6ee7b7; --accent2:#818cf8; --accent3:#fb923c; --text:#f1f5f9; --muted:#64748b; --danger:#f87171; --success:#34d399; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
.sidebar { width:240px; min-height:100vh; background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; left:0; top:0; bottom:0; z-index:100; padding:28px 0; }
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
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }

/* DONUT CHART */
.donut-wrap { display:flex; align-items:center; gap:28px; }
.donut-legend { display:flex; flex-direction:column; gap:12px; }
.leg-item { display:flex; align-items:center; gap:10px; font-size:0.85rem; }
.leg-dot { width:10px; height:10px; border-radius:50%; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:0.88rem; }
th { text-align:left; padding:10px 16px; color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); }
td { padding:12px 16px; border-bottom:1px solid rgba(31,41,55,0.5); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:rgba(255,255,255,0.02); }
.outcome-badge { padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
.admitted { background:rgba(52,211,153,0.1); color:var(--success); }
.rejected { background:rgba(248,113,113,0.1); color:var(--danger); }
.waitlisted { background:rgba(251,146,60,0.1); color:var(--accent3); }
.tier-badge { padding:3px 8px; border-radius:10px; font-size:0.7rem; font-weight:600; }
.tier1 { background:rgba(129,140,248,0.1); color:var(--accent2); }
.tier2 { background:rgba(110,231,183,0.1); color:var(--accent); }
.tier3 { background:rgba(251,146,60,0.1); color:var(--accent3); }

/* ADD RECORD FORM */
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
select, input { width:100%; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:border-color 0.2s; }
select:focus, input:focus { border-color:var(--accent); }
select option { background:var(--card); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { margin-bottom:14px; }
.btn-primary { padding:10px 22px; border-radius:10px; border:none; background:var(--accent); color:#000; font-family:'Syne',sans-serif; font-weight:700; cursor:pointer; transition:all 0.2s; font-size:0.88rem; }
.btn-primary:hover { background:#a7f3d0; }
.empty { text-align:center; padding:40px; color:var(--muted); }
.msg { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.88rem; }
.msg.success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:var(--success); }
</style>
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$studentId = $_SESSION['student_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_record') {
    $cid  = intval($_POST['college_id']);
    $out  = $_POST['outcome'];
    $scr  = floatval($_POST['previous_scores']);
    $yr   = intval($_POST['year']);
    $stmt = $db->prepare("INSERT INTO admission_records (student_id,college_id,outcome,previous_scores,year) VALUES(?,?,?,?,?)");
    $stmt->bind_param('iisdi',$studentId,$cid,$out,$scr,$yr);
    $stmt->execute(); $stmt->close();
    $msg = '<div class="msg success">✓ Admission record added successfully!</div>';
}

$history  = $db->query("SELECT ar.*,c.college_name,c.tier FROM admission_records ar JOIN colleges c ON ar.college_id=c.college_id WHERE ar.student_id=$studentId ORDER BY ar.year DESC, ar.record_id DESC")->fetch_all(MYSQLI_ASSOC);
$colleges = $db->query("SELECT * FROM colleges ORDER BY college_name")->fetch_all(MYSQLI_ASSOC);

$admitted   = count(array_filter($history,fn($h)=>$h['outcome']==='Admitted'));
$rejected   = count(array_filter($history,fn($h)=>$h['outcome']==='Rejected'));
$waitlisted = count(array_filter($history,fn($h)=>$h['outcome']==='Waitlisted'));
$total = count($history);
?>
<aside class="sidebar">
  <div class="sidebar-logo">Admit<span>IQ</span></div>
  <nav>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="predict.php"   class="nav-item"><span class="nav-icon">🧠</span> Run Prediction</a>
    <a href="colleges.php"  class="nav-item"><span class="nav-icon">🏛️</span> Browse Colleges</a>
    <a href="history.php"   class="nav-item active"><span class="nav-icon">📋</span> My History</a>
    <a href="profile.php"   class="nav-item"><span class="nav-icon">👤</span> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>
</aside>
<main class="main">
  <div class="page-title">📋 Admission History</div>
  <div class="page-sub">Track your application outcomes and historical trends</div>
  <?= $msg ?>

  <div class="two-col">
    <!-- DONUT CHART -->
    <div class="card">
      <div class="card-title"><div class="dot"></div> Outcome Overview</div>
      <?php if($total>0):
        $aP = $total ? round($admitted/$total*100) : 0;
        $rP = $total ? round($rejected/$total*100) : 0;
        $wP = 100-$aP-$rP;
        // SVG donut
        $r=70; $cx=90; $cy=90; $c=2*pi()*$r;
        $aD=$admitted/$total*$c; $rD=$rejected/$total*$c; $wD=$waitlisted/$total*$c;
        $aO=0; $rO=$aD; $wO=$aD+$rD;
      ?>
      <div class="donut-wrap">
        <svg width="180" height="180" viewBox="0 0 180 180">
          <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="var(--border)" stroke-width="18"/>
          <?php if($admitted>0): ?>
          <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="#34d399" stroke-width="18"
            stroke-dasharray="<?=$aD?> <?=$c?>" stroke-dashoffset="<?='-'.$aO?>" transform="rotate(-90 <?=$cx?> <?=$cy?>)"/>
          <?php endif; if($rejected>0): ?>
          <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="#f87171" stroke-width="18"
            stroke-dasharray="<?=$rD?> <?=$c?>" stroke-dashoffset="<?='-'.$rO?>" transform="rotate(-90 <?=$cx?> <?=$cy?>)"/>
          <?php endif; if($waitlisted>0): ?>
          <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="#fb923c" stroke-width="18"
            stroke-dasharray="<?=$wD?> <?=$c?>" stroke-dashoffset="<?='-'.$wO?>" transform="rotate(-90 <?=$cx?> <?=$cy?>)"/>
          <?php endif; ?>
          <text x="<?=$cx?>" y="<?=$cy?>" text-anchor="middle" dy="6" fill="white" font-family="Syne,sans-serif" font-size="20" font-weight="800"><?=$total?></text>
          <text x="<?=$cx?>" y="<?=$cy?>" text-anchor="middle" dy="22" fill="#64748b" font-family="DM Sans,sans-serif" font-size="11">Applications</text>
        </svg>
        <div class="donut-legend">
          <div class="leg-item"><div class="leg-dot" style="background:#34d399"></div> Admitted (<?=$admitted?>)</div>
          <div class="leg-item"><div class="leg-dot" style="background:#f87171"></div> Rejected (<?=$rejected?>)</div>
          <div class="leg-item"><div class="leg-dot" style="background:#fb923c"></div> Waitlisted (<?=$waitlisted?>)</div>
        </div>
      </div>
      <?php else: ?>
        <div class="empty">No records yet — add your first below.</div>
      <?php endif; ?>
    </div>

    <!-- ADD RECORD -->
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent2)"></div> Add Admission Record</div>
      <form method="POST">
        <input type="hidden" name="action" value="add_record">
        <div class="form-group">
          <label>College</label>
          <select name="college_id" required>
            <option value="">Select college...</option>
            <?php foreach($colleges as $c): ?>
              <option value="<?= $c['college_id'] ?>"><?= htmlspecialchars($c['college_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Outcome</label>
            <select name="outcome" required>
              <option value="Admitted">Admitted</option>
              <option value="Rejected">Rejected</option>
              <option value="Waitlisted">Waitlisted</option>
            </select>
          </div>
          <div class="form-group">
            <label>Score at Time %</label>
            <input type="number" name="previous_scores" min="0" max="100" step="0.1" placeholder="85.0">
          </div>
        </div>
        <div class="form-group">
          <label>Year</label>
          <input type="number" name="year" value="2024" min="2010" max="2030">
        </div>
        <button type="submit" class="btn-primary">Add Record →</button>
      </form>
    </div>
  </div>

  <!-- HISTORY TABLE -->
  <div class="card">
    <div class="card-title"><div class="dot" style="background:var(--accent3)"></div> All Records</div>
    <?php if(empty($history)): ?>
      <div class="empty">No admission history yet. Add records above.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>College</th><th>Tier</th><th>Score</th><th>Year</th><th>Outcome</th></tr></thead>
        <tbody>
        <?php foreach($history as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['college_name']) ?></td>
          <td><span class="tier-badge <?= strtolower(str_replace(' ','',$h['tier'])) ?>"><?= $h['tier'] ?></span></td>
          <td><?= $h['previous_scores'] ?>%</td>
          <td><?= $h['year'] ?></td>
          <td><span class="outcome-badge <?= strtolower($h['outcome']) ?>"><?= $h['outcome'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php $db->close(); ?>
</body>
</html>
