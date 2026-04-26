<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — AdmitIQ</title>
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
.main { margin-left:240px; flex:1; padding:40px 48px; max-width:900px; }
.page-title { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:36px; }
.card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:28px; margin-bottom:24px; position:relative; overflow:hidden; }
.card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.card-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; margin-bottom:20px; }
.avatar-big { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2)); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:800; font-size:2rem; color:#000; margin-bottom:16px; }
.profile-name { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:700; }
.profile-email { color:var(--muted); font-size:0.9rem; margin-top:4px; }
label { display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); margin-bottom:6px; }
input { width:100%; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:border-color 0.2s; }
input:focus { border-color:var(--accent); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { margin-bottom:14px; }
.btn-primary { padding:12px 24px; border-radius:10px; border:none; background:var(--accent); color:#000; font-family:'Syne',sans-serif; font-weight:700; cursor:pointer; transition:all 0.2s; }
.btn-primary:hover { background:#a7f3d0; transform:translateY(-1px); }
.msg { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.88rem; }
.msg.success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:var(--success); }
.msg.error { background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--danger); }
.db-badge { display:inline-flex; align-items:center; gap:6px; font-size:0.78rem; color:var(--success); background:rgba(52,211,153,0.08); border:1px solid rgba(52,211,153,0.2); padding:4px 12px; border-radius:20px; margin-top:12px; }
.db-dot { width:6px; height:6px; border-radius:50%; background:var(--success); animation:blink 1.5s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
</style>
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$studentId = $_SESSION['student_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action']??'';
    if ($action==='update_profile') {
        $name  = trim($_POST['name']??'');
        $marks = floatval($_POST['student_marks']);
        $ent   = floatval($_POST['entrance_scores']);
        $prefs = trim($_POST['preferences']??'');
        $stmt  = $db->prepare("UPDATE students SET name=?,student_marks=?,entrance_scores=?,preferences=? WHERE student_id=?");
        $stmt->bind_param('sddsi',$name,$marks,$ent,$prefs,$studentId);
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
<aside class="sidebar">
  <div class="sidebar-logo">Admit<span>IQ</span></div>
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
<main class="main">
  <div class="page-title">👤 My Profile</div>
  <div class="page-sub">Manage your academic information and account settings</div>
  <?= $msg ?>

  <div class="card">
    <div class="avatar-big"><?= $initials ?></div>
    <div class="profile-name"><?= htmlspecialchars($student['name']) ?></div>
    <div class="profile-email"><?= htmlspecialchars($student['email']) ?></div>
    <div class="db-badge"><div class="db-dot"></div> Synced to MySQL Database</div>
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
          <label>Board Marks %</label>
          <input type="number" name="student_marks" value="<?= $student['student_marks'] ?>" min="0" max="100" step="0.1">
        </div>
        <div class="form-group">
          <label>Entrance Score %</label>
          <input type="number" name="entrance_scores" value="<?= $student['entrance_scores'] ?>" min="0" max="100" step="0.1">
        </div>
      </div>
      <div class="form-group">
        <label>Course Preferences (comma separated)</label>
        <input type="text" name="preferences" value="<?= htmlspecialchars($student['preferences']??'') ?>" placeholder="CSE, AIML, ECE">
      </div>
      <button type="submit" class="btn-primary">Save Changes</button>
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
      <button type="submit" class="btn-primary">Update Password</button>
    </form>
  </div>
</main>
<?php $db->close(); ?>
</body>
</html>
