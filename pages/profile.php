<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$studentId = $_SESSION['student_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action']??'';
    if ($action==='update_profile') {
        global $EXAMS;
        $name  = trim($_POST['name']??'');
        $marks = floatval($_POST['student_marks']);
        $prefs = trim($_POST['preferences']??'');
        $exam_name = $_POST['exam_name'] ?? 'General/Other';
        $exam_raw  = floatval($_POST['exam_raw_score'] ?? 0);
        
        $exam_total = $EXAMS[$exam_name] ?? 100;
        $ent = ($exam_raw / $exam_total) * 100;
        if ($ent > 100) $ent = 100;

        $stmt  = $db->prepare("UPDATE students SET name=?, student_marks=?, entrance_scores=?, exam_name=?, exam_raw_score=?, preferences=? WHERE student_id=?");
        $stmt->bind_param('sddsdsi', $name, $marks, $ent, $exam_name, $exam_raw, $prefs, $studentId);
        $stmt->execute(); $stmt->close();
        $_SESSION['student_name'] = $name;
        $msg = '<div class="msg success">✓ Profile updated!</div>';
    }
    if ($action==='change_password') {
        $cur  = $_POST['current_password']??'';
        $new  = $_POST['new_password']??'';
        $stmt = $db->prepare("SELECT password FROM students WHERE student_id=?");
        $stmt->bind_param('i',$studentId); $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (password_verify($cur,$row['password'])) {
            $hash = password_hash($new,PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE students SET password=? WHERE student_id=?");
            $stmt->bind_param('si',$hash,$studentId); $stmt->execute(); $stmt->close();
            $msg = '<div class="msg success">✓ Password changed successfully!</div>';
        } else {
            $msg = '<div class="msg error">Current password is incorrect.</div>';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM students WHERE student_id=?");
$stmt->bind_param('i',$studentId); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();
$initials = strtoupper(substr($student['name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — AdmitIQ</title>
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

.sidebar { width:240px; min-height:100vh; background:rgba(19, 17, 39, 0.8); backdrop-filter: blur(20px); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; left:0; top:0; bottom:0; z-index:100; padding:28px 0; }
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
.main-wrapper { margin-left:240px; flex:1; display: flex; justify-content: center; position:relative; z-index:1; }
.main { padding:40px 48px; width: 100%; max-width:800px; }
.page-title { font-family:'Inter',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; text-align: center; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:36px; text-align: center; }
.card { background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); border:1px solid var(--border); border-radius:20px; padding:32px; margin-bottom:24px; position:relative; overflow:hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--accent),var(--accent2),var(--accent3)); }
.card-title { font-family:'Inter',sans-serif; font-size:1.1rem; font-weight:700; margin-bottom:20px; }
.avatar-big { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2),var(--accent3)); display:flex; align-items:center; justify-content:center; font-family:'Inter',sans-serif; font-weight:800; font-size:2rem; color:#fff; margin-bottom:16px; box-shadow: 0 8px 20px rgba(157, 78, 221, 0.3); }
.profile-name { font-family:'Inter',sans-serif; font-size:1.5rem; font-weight:700; }
.profile-email { color:var(--muted); font-size:0.9rem; margin-top:4px; }
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
input, select { width:100%; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:border-color 0.2s, box-shadow 0.2s; }
input:focus, select:focus { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
select option { background:var(--bg); color:var(--text); }
.input-wrapper { display:flex; align-items:center; background:var(--surface); border:1px solid var(--border); border-radius:10px; overflow:hidden; transition:border-color 0.2s; }
.input-wrapper:focus-within { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
.input-wrapper input { border:none; border-radius:0; background:transparent; width:100%; box-shadow:none; }
.input-suffix { padding:0 14px; color:var(--muted); font-size:0.9rem; font-family:'DM Sans',sans-serif; background:rgba(255,255,255,0.02); border-left:1px solid var(--border); white-space:nowrap; }

.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { margin-bottom:14px; }
.btn-primary { padding:12px 24px; border-radius:10px; border:none; background:linear-gradient(135deg, var(--accent2), var(--accent3)); color:#fff; font-family:'Inter',sans-serif; font-weight:700; cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 15px rgba(157, 78, 221, 0.2); }
.btn-primary:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(157, 78, 221, 0.4); }
.msg { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.88rem; }
.msg.success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:var(--success); }
.msg.error { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--danger); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">AQ</div>
  <nav>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="predict.php"   class="nav-item"><span class="nav-icon">🧠</span> Run Prediction</a>
    <a href="colleges.php"  class="nav-item"><span class="nav-icon">🏛️</span> Browse Colleges</a>
    <a href="history.php"   class="nav-item"><span class="nav-icon">📋</span> My History</a>
    <a href="profile.php"   class="nav-item active"><span class="nav-icon">👤</span> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>
</aside>
<div class="main-wrapper">
<main class="main">
  <div class="page-title">👤 My Profile</div>
  <div class="page-sub">Manage your academic information and account settings</div>

  <div style="background: rgba(157, 78, 221, 0.05); border: 1px solid rgba(157, 78, 221, 0.2); border-radius: 16px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🛡️</div>
    <div>
      <div style="font-weight: 700; color: var(--accent); margin-bottom: 4px;">Data Privacy & Security</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Your profile information is securely stored in our MySQL database. Passwords are fully encrypted. Make sure your academic details are up-to-date so the prediction engine can give you the best results.</div>
    </div>
  </div>
  <?= $msg ?>

  <div class="card" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
    <div class="avatar-big"><?= $initials ?></div>
    <div class="profile-name"><?= htmlspecialchars($student['name']) ?></div>
    <div class="profile-email"><?= htmlspecialchars($student['email']) ?></div>
  </div>

  <div class="card">
    <div class="card-title">Academic Information</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_profile">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Target Course</label>
          <select name="preferences" required>
            <option value="" disabled <?= empty($student['preferences']) ? 'selected' : '' ?>>Select Course</option>
            <?php global $COURSES; foreach ($COURSES as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= ($student['preferences'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Board Marks %</label>
          <input type="number" name="student_marks" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Entrance Exam</label>
          <select name="exam_name" id="exam_select" onchange="updateExamMax()" required>
            <option value="" disabled <?= empty($student['exam_name']) ? 'selected' : '' ?>>Select Exam</option>
            <?php global $EXAMS; foreach ($EXAMS as $ex => $max): ?>
              <option value="<?= htmlspecialchars($ex) ?>" data-max="<?= $max ?>" <?= ($student['exam_name'] ?? '') === $ex ? 'selected' : '' ?>><?= htmlspecialchars($ex) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Exam Raw Score</label>
          <div class="input-wrapper">
            <input type="number" name="exam_raw_score" id="exam_raw" value="<?= isset($student['exam_raw_score']) ? $student['exam_raw_score'] : '' ?>" min="0" step="1" required>
            <div class="input-suffix" id="exam_suffix">/ 100</div>
          </div>
        </div>
      </div>
      <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Save Changes</button>
    </form>
  </div>

  <div class="card">
    <div class="card-title">Change Password</div>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Update Password</button>
    </form>
  </div>
</main>
</div>
<?php $db->close(); ?>

<script>
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
// Init on load
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('exam_select').value) updateExamMax();
});
</script>

</body>
</html>
