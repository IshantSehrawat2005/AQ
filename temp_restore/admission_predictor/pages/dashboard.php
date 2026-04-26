<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — AdmitIQ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #05080f; --surface: #0d1117; --card: #111827;
  --border: #1f2937; --accent: #6ee7b7; --accent2: #818cf8;
  --accent3: #fb923c; --text: #f1f5f9; --muted: #64748b;
  --danger: #f87171; --success: #34d399;
}
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

/* SIDEBAR */
.sidebar {
  width: 240px; min-height: 100vh;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  position: fixed; left: 0; top: 0; bottom: 0; z-index: 100;
  padding: 28px 0;
}
.sidebar-logo {
  font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  -webkit-background-clip:text; -webkit-text-fill-color:transparent;
  padding: 0 24px; margin-bottom: 32px;
}
.sidebar-logo span { -webkit-text-fill-color:var(--accent3); }
.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 24px; color: var(--muted);
  text-decoration: none; font-size: 0.9rem; font-weight: 500;
  transition: all 0.2s; border-left: 3px solid transparent;
  cursor: pointer; border: none; background: none; width: 100%; text-align: left;
}
.nav-item:hover { color: var(--text); background: rgba(255,255,255,0.03); }
.nav-item.active { color: var(--accent); border-left-color: var(--accent); background: rgba(110,231,183,0.05); }
.nav-icon { font-size: 1.1rem; width: 20px; }
.sidebar-footer {
  margin-top: auto; padding: 20px 24px;
  border-top: 1px solid var(--border);
}
.user-chip {
  display: flex; align-items: center; gap: 10px;
  background: var(--card); border-radius: 12px; padding: 10px 14px;
}
.user-av {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-family:'Syne',sans-serif; font-weight:700; font-size:0.85rem; color:#000;
}
.user-info { flex:1; overflow:hidden; }
.user-name { font-size:0.82rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role { font-size:0.72rem; color:var(--muted); }
.logout-btn {
  display:block; text-align:center; padding:8px; margin-top:10px;
  background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.2);
  color:var(--danger); border-radius:8px; text-decoration:none;
  font-size:0.8rem; transition:all 0.2s;
}
.logout-btn:hover { background:rgba(248,113,113,0.2); }

/* MAIN */
.main { margin-left: 240px; flex:1; padding: 40px 48px; }

/* TOPBAR */
.topbar {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom: 40px;
}
.page-title { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-top:4px; }

/* STAT CARDS */
.stat-grid {
  display: grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:32px;
}
.stat-card {
  background:var(--card); border:1px solid var(--border);
  border-radius:16px; padding:24px; position:relative; overflow:hidden;
  transition: transform 0.2s, border-color 0.2s;
}
.stat-card:hover { transform:translateY(-2px); border-color:rgba(110,231,183,0.2); }
.stat-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:2px;
}
.stat-card:nth-child(1)::before { background:var(--accent); }
.stat-card:nth-child(2)::before { background:var(--accent2); }
.stat-card:nth-child(3)::before { background:var(--accent3); }
.stat-card:nth-child(4)::before { background:var(--danger); }
.sc-label { font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
.sc-val { font-family:'Syne',sans-serif; font-size:2.2rem; font-weight:800; }
.sc-sub { font-size:0.78rem; color:var(--muted); margin-top:4px; }

/* GRID */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.full-col { margin-bottom:20px; }

/* CARD */
.card {
  background:var(--card); border:1px solid var(--border);
  border-radius:16px; padding:28px; position:relative;
}
.card-title {
  font-family:'Syne',sans-serif; font-size:1rem; font-weight:700;
  margin-bottom:20px; display:flex; align-items:center; gap:10px;
}
.card-title .dot { width:8px; height:8px; border-radius:50%; background:var(--accent); }

/* PROFILE FORM */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { margin-bottom:14px; }
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
input, textarea {
  width:100%; padding:10px 14px;
  background:var(--surface); border:1px solid var(--border);
  border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif;
  font-size:0.9rem; transition:border-color 0.2s, box-shadow 0.2s; outline:none;
}
input:focus, textarea:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(110,231,183,0.1); }
.btn {
  padding:10px 20px; border-radius:10px; border:none;
  font-family:'Syne',sans-serif; font-weight:600; cursor:pointer;
  transition:all 0.2s; font-size:0.88rem;
}
.btn-primary { background:var(--accent); color:#000; }
.btn-primary:hover { background:#a7f3d0; transform:translateY(-1px); }
.btn-secondary { background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--text); }
.btn-secondary:hover { background:rgba(255,255,255,0.08); }

/* SCORE METER */
.meter-wrap { margin-bottom:12px; }
.meter-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.meter-label { font-size:0.85rem; }
.meter-val { font-family:'Syne',sans-serif; font-weight:700; font-size:0.85rem; }
.meter-bar { height:6px; background:var(--border); border-radius:3px; overflow:hidden; }
.meter-fill { height:100%; border-radius:3px; transition:width 1s ease; }

/* PREDICTION RESULTS */
.pred-list { display:flex; flex-direction:column; gap:12px; }
.pred-item {
  display:flex; align-items:center; gap:16px;
  padding:16px; background:var(--surface); border-radius:12px;
  border:1px solid var(--border); transition:border-color 0.2s;
}
.pred-item:hover { border-color:rgba(110,231,183,0.2); }
.prob-circle {
  width:56px; height:56px; border-radius:50%;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
  font-family:'Syne',sans-serif; font-weight:800; font-size:0.85rem;
  border:2px solid;
}
.prob-high { border-color:var(--success); color:var(--success); background:rgba(52,211,153,0.08); }
.prob-med  { border-color:var(--accent3); color:var(--accent3); background:rgba(251,146,60,0.08); }
.prob-low  { border-color:var(--danger);  color:var(--danger);  background:rgba(248,113,113,0.08); }
.pred-college { font-family:'Syne',sans-serif; font-weight:700; font-size:0.95rem; margin-bottom:4px; }
.pred-rec { font-size:0.8rem; color:var(--muted); line-height:1.5; }
.pred-badge {
  margin-left:auto; padding:4px 10px; border-radius:20px;
  font-size:0.72rem; font-weight:600; white-space:nowrap;
}
.tier1 { background:rgba(129,140,248,0.1); color:var(--accent2); }
.tier2 { background:rgba(110,231,183,0.1); color:var(--accent); }
.tier3 { background:rgba(251,146,60,0.1);  color:var(--accent3); }

.msg { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.88rem; }
.msg.success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:var(--success); }
.msg.error   { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--danger); }

/* HISTORY TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:0.88rem; }
th { text-align:left; padding:10px 16px; color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); }
td { padding:12px 16px; border-bottom:1px solid rgba(31,41,55,0.5); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:rgba(255,255,255,0.02); }
.outcome-badge { padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
.admitted   { background:rgba(52,211,153,0.1); color:var(--success); }
.rejected   { background:rgba(248,113,113,0.1); color:var(--danger); }
.waitlisted { background:rgba(251,146,60,0.1); color:var(--accent3); }

.empty { text-align:center; padding:40px; color:var(--muted); font-size:0.9rem; }

/* DB STATUS */
.db-status {
  display:inline-flex; align-items:center; gap:6px;
  font-size:0.78rem; color:var(--success);
  background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.2);
  padding:4px 12px; border-radius:20px;
}
.db-dot { width:6px; height:6px; border-radius:50%; background:var(--success); animation:blink 1.5s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
</style>
</head>
<body>
<?php
require_once '../includes/config.php';
require_once '../includes/predictor.php';
requireLogin();

$db = getDB();
$studentId = $_SESSION['student_id'];

// Fetch student data
$stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$msg = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $marks = floatval($_POST['student_marks']);
    $ent   = floatval($_POST['entrance_scores']);
    $prefs = trim($_POST['preferences'] ?? '');
    $stmt = $db->prepare("UPDATE students SET student_marks=?, entrance_scores=?, preferences=? WHERE student_id=?");
    $stmt->bind_param('ddsi', $marks, $ent, $prefs, $studentId);
    $stmt->execute(); $stmt->close();
    $student['student_marks'] = $marks;
    $student['entrance_scores'] = $ent;
    $student['preferences'] = $prefs;
    $msg = '<div class="msg success">✓ Profile updated successfully!</div>';
}

// Fetch all colleges
$colleges = $db->query("SELECT * FROM colleges ORDER BY cutoff_scores DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch predictions
$preds = $db->query("
    SELECT p.*, c.college_name, c.tier, c.location
    FROM predictions p JOIN colleges c ON p.college_id=c.college_id
    WHERE p.student_id=$studentId ORDER BY p.probability_score DESC
")->fetch_all(MYSQLI_ASSOC);

// Fetch admission history
$history = $db->query("
    SELECT ar.*, c.college_name, c.tier
    FROM admission_records ar JOIN colleges c ON ar.college_id=c.college_id
    WHERE ar.student_id=$studentId ORDER BY ar.record_id DESC
")->fetch_all(MYSQLI_ASSOC);

// Stats
$totalPreds = count($preds);
$highChance = count(array_filter($preds, fn($p) => $p['probability_score'] >= 70));
$avgProb    = $totalPreds ? round(array_sum(array_column($preds,'probability_score'))/$totalPreds,1) : 0;
$admitted   = count(array_filter($history, fn($h) => $h['outcome'] === 'Admitted'));

$initials = strtoupper(substr($student['name'],0,1));
?>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">Admit<span>IQ</span></div>
  <nav>
    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="predict.php"   class="nav-item"><span class="nav-icon">🧠</span> Run Prediction</a>
    <a href="colleges.php"  class="nav-item"><span class="nav-icon">🏛️</span> Browse Colleges</a>
    <a href="history.php"   class="nav-item"><span class="nav-icon">📋</span> My History</a>
    <a href="profile.php"   class="nav-item"><span class="nav-icon">👤</span> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-av"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($student['name']) ?></div>
        <div class="user-role">Student</div>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div>
      <div class="page-title">Welcome back, <?= htmlspecialchars(explode(' ',$student['name'])[0]) ?> 👋</div>
      <div class="page-sub">Here's your admission prediction overview</div>
    </div>
    <div class="db-status"><div class="db-dot"></div> MySQL Connected</div>
  </div>

  <?= $msg ?>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="sc-label">Predictions Run</div>
      <div class="sc-val"><?= $totalPreds ?></div>
      <div class="sc-sub">across all colleges</div>
    </div>
    <div class="stat-card">
      <div class="sc-label">High Chance (≥70%)</div>
      <div class="sc-val"><?= $highChance ?></div>
      <div class="sc-sub">strong prospects</div>
    </div>
    <div class="stat-card">
      <div class="sc-label">Average Probability</div>
      <div class="sc-val"><?= $avgProb ?>%</div>
      <div class="sc-sub">overall admission score</div>
    </div>
    <div class="stat-card">
      <div class="sc-label">Historical Admits</div>
      <div class="sc-val"><?= $admitted ?></div>
      <div class="sc-sub">from records</div>
    </div>
  </div>

  <div class="two-col">
    <!-- ACADEMIC PROFILE -->
    <div class="card">
      <div class="card-title"><div class="dot"></div> Academic Profile</div>
      <div class="meter-wrap">
        <div class="meter-top">
          <span class="meter-label">Board Marks</span>
          <span class="meter-val" style="color:var(--accent)"><?= $student['student_marks'] ?>%</span>
        </div>
        <div class="meter-bar"><div class="meter-fill" style="width:<?= $student['student_marks'] ?>%;background:var(--accent)"></div></div>
      </div>
      <div class="meter-wrap" style="margin-top:16px">
        <div class="meter-top">
          <span class="meter-label">Entrance Score</span>
          <span class="meter-val" style="color:var(--accent2)"><?= $student['entrance_scores'] ?>%</span>
        </div>
        <div class="meter-bar"><div class="meter-fill" style="width:<?= $student['entrance_scores'] ?>%;background:var(--accent2)"></div></div>
      </div>
      <form method="POST" style="margin-top:20px">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-row">
          <div class="form-group">
            <label>Board Marks %</label>
            <input type="number" name="student_marks" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1">
          </div>
          <div class="form-group">
            <label>Entrance Score %</label>
            <input type="number" name="entrance_scores" value="<?= $student['entrance_scores'] ?>" min="0" max="100" step="0.1">
          </div>
        </div>
        <div class="form-group">
          <label>Preferred Courses</label>
          <input type="text" name="preferences" value="<?= htmlspecialchars($student['preferences'] ?? '') ?>" placeholder="CSE, AIML, ECE">
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
      </form>
    </div>

    <!-- QUICK PREDICTIONS -->
    <div class="card">
      <div class="card-title"><div class="dot" style="background:var(--accent2)"></div> Top Predictions</div>
      <?php if (empty($preds)): ?>
        <div class="empty">No predictions yet.<br>
          <a href="predict.php" style="color:var(--accent);text-decoration:none;margin-top:8px;display:inline-block">Run your first prediction →</a>
        </div>
      <?php else: ?>
        <div class="pred-list">
        <?php foreach(array_slice($preds,0,4) as $p):
          $prob = $p['probability_score'];
          $cls = $prob >= 70 ? 'prob-high' : ($prob >= 40 ? 'prob-med' : 'prob-low');
          $tierCls = strtolower(str_replace(' ','',$p['tier']));
        ?>
          <div class="pred-item">
            <div class="prob-circle <?= $cls ?>"><?= $prob ?>%</div>
            <div style="flex:1">
              <div class="pred-college"><?= htmlspecialchars($p['college_name']) ?></div>
              <div class="pred-rec"><?= htmlspecialchars($p['location']) ?></div>
            </div>
            <span class="pred-badge <?= $tierCls ?>"><?= $p['tier'] ?></span>
          </div>
        <?php endforeach; ?>
        </div>
        <a href="predict.php" style="display:block;text-align:center;margin-top:16px;color:var(--accent);font-size:0.85rem;text-decoration:none">View all predictions →</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- RECENT HISTORY -->
  <div class="card">
    <div class="card-title"><div class="dot" style="background:var(--accent3)"></div> Admission History</div>
    <?php if (empty($history)): ?>
      <div class="empty">No admission history recorded yet.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>College</th><th>Tier</th><th>Score at Time</th><th>Year</th><th>Outcome</th></tr></thead>
        <tbody>
        <?php foreach($history as $h): ?>
          <tr>
            <td><?= htmlspecialchars($h['college_name']) ?></td>
            <td><span class="pred-badge <?= strtolower(str_replace(' ','',$h['tier'])) ?>"><?= $h['tier'] ?></span></td>
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
