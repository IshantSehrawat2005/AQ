<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AdmitIQ — AI College Admission Predictor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg: #05080f;
  --surface: #0d1117;
  --card: #111827;
  --border: #1f2937;
  --accent: #6ee7b7;
  --accent2: #818cf8;
  --accent3: #fb923c;
  --text: #f1f5f9;
  --muted: #64748b;
  --danger: #f87171;
  --success: #34d399;
  --glow: rgba(110,231,183,0.15);
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* Background grid + glow */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: 
    linear-gradient(rgba(110,231,183,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(110,231,183,0.04) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
  z-index: 0;
}

body::after {
  content: '';
  position: fixed;
  top: -200px; left: 50%;
  transform: translateX(-50%);
  width: 800px; height: 800px;
  background: radial-gradient(circle, rgba(110,231,183,0.08) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* LAYOUT */
.container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* NAV */
nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 48px;
  position: relative; z-index: 10;
  border-bottom: 1px solid var(--border);
}
.logo {
  font-family: 'Syne', sans-serif;
  font-size: 1.5rem; font-weight: 800;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}
.logo span { color: var(--accent3); -webkit-text-fill-color: var(--accent3); }
.badge {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 1px;
  background: rgba(110,231,183,0.1); border: 1px solid rgba(110,231,183,0.3);
  color: var(--accent); padding: 4px 10px; border-radius: 20px;
}

/* HERO */
.hero {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 80px; align-items: center;
  padding: 80px 48px 60px;
  position: relative; z-index: 1;
}

.hero-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(129,140,248,0.1); border: 1px solid rgba(129,140,248,0.25);
  color: var(--accent2); padding: 6px 14px; border-radius: 20px;
  font-size: 0.78rem; font-weight: 500; letter-spacing: 0.5px;
  margin-bottom: 24px;
}
.hero-tag::before { content: '●'; font-size: 8px; animation: blink 1.5s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

h1 {
  font-family: 'Syne', sans-serif;
  font-size: 3.5rem; font-weight: 800;
  line-height: 1.08; letter-spacing: -2px;
  margin-bottom: 20px;
}
h1 em { font-style: normal; color: var(--accent); }

.hero-sub {
  color: var(--muted); font-size: 1.05rem; line-height: 1.7;
  margin-bottom: 40px; max-width: 460px;
}

.stats-row {
  display: flex; gap: 32px; margin-bottom: 40px;
}
.stat { display: flex; flex-direction: column; }
.stat-num {
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem; font-weight: 700;
  color: var(--accent);
}
.stat-label { font-size: 0.78rem; color: var(--muted); }

/* FORM CARD */
.form-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 40px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 0 60px rgba(110,231,183,0.05);
}
.form-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, var(--accent), var(--accent2), var(--accent3));
}

.tabs {
  display: flex; gap: 0;
  background: var(--surface); border-radius: 12px;
  padding: 4px; margin-bottom: 32px;
}
.tab {
  flex: 1; padding: 10px; text-align: center;
  font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 600;
  border: none; background: none; color: var(--muted);
  cursor: pointer; border-radius: 10px; transition: all 0.2s;
}
.tab.active { background: var(--accent); color: #000; }

.form-group { margin-bottom: 18px; }
label {
  display: block; font-size: 0.78rem; font-weight: 500;
  color: var(--muted); letter-spacing: 0.5px;
  margin-bottom: 8px; text-transform: uppercase;
}
input {
  width: 100%; padding: 12px 16px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 10px; color: var(--text); font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem; transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}
input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(110,231,183,0.1);
}
input::placeholder { color: var(--muted); }

.btn-primary {
  width: 100%; padding: 14px;
  background: var(--accent); color: #000;
  border: none; border-radius: 12px;
  font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
  letter-spacing: 0.3px;
}
.btn-primary:hover { background: #a7f3d0; transform: translateY(-1px); box-shadow: 0 8px 30px rgba(110,231,183,0.25); }

.msg { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.88rem; }
.msg.error { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3); color: var(--danger); }
.msg.success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.3); color: var(--success); }

/* FEATURES STRIP */
.features {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 1px; background: var(--border);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  position: relative; z-index: 1;
}
.feature {
  background: var(--bg); padding: 32px 28px;
  display: flex; flex-direction: column; gap: 12px;
  transition: background 0.2s;
}
.feature:hover { background: var(--surface); }
.feat-icon { font-size: 1.6rem; }
.feat-title { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; }
.feat-desc { font-size: 0.82rem; color: var(--muted); line-height: 1.6; }

/* PANEL toggle */
.panel { display: none; }
.panel.active { display: block; }

/* FOOTER */
footer {
  text-align: center; padding: 32px;
  color: var(--muted); font-size: 0.82rem;
  border-top: 1px solid var(--border);
  position: relative; z-index: 1;
}
footer strong { color: var(--accent); }

/* Animations */
.hero-left { animation: fadeUp 0.7s ease both; }
.form-card { animation: fadeUp 0.7s 0.15s ease both; }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 900px) {
  .hero { grid-template-columns: 1fr; padding: 40px 24px; gap: 40px; }
  h1 { font-size: 2.5rem; }
  .features { grid-template-columns: repeat(2, 1fr); }
  nav { padding: 20px 24px; }
}
</style>
</head>
<body>
<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location: pages/dashboard.php'); exit; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt  = $db->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($student && password_verify($pass, $student['password'])) {
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_name'] = $student['name'];
            header('Location: pages/dashboard.php'); exit;
        } else { $error = 'Invalid email or password.'; }
    }

    if ($action === 'register') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $marks = floatval($_POST['student_marks'] ?? 0);
        $ent   = floatval($_POST['entrance_scores'] ?? 0);
        if (!$name || !$email || !$pass) { $error = 'All fields are required.'; }
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO students (name, email, password, student_marks, entrance_scores) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssdd', $name, $email, $hash, $marks, $ent);
            if ($stmt->execute()) { $success = 'Account created! Please log in.'; }
            else { $error = 'Email already registered.'; }
            $stmt->close();
        }
    }
    $db->close();
}
?>

<nav>
  <div class="logo">Admit<span>IQ</span></div>
  <div class="badge">AI-POWERED · PHP + MySQL</div>
</nav>

<section class="hero">
  <div class="hero-left">
    <div class="hero-tag">AI Prediction Engine Active</div>
    <h1>Predict Your <em>College</em> Admission Chances</h1>
    <p class="hero-sub">Enter your academic profile and get real-time, data-driven admission probability scores for top colleges across India — powered by rule-based AI and historical data.</p>
    <div class="stats-row">
      <div class="stat"><span class="stat-num">10+</span><span class="stat-label">Colleges</span></div>
      <div class="stat"><span class="stat-num">99%</span><span class="stat-label">Accuracy</span></div>
      <div class="stat"><span class="stat-num">3</span><span class="stat-label">Tier Tiers</span></div>
    </div>
  </div>

  <div class="form-card">
    <div class="tabs">
      <button class="tab active" onclick="switchTab('login',this)">Sign In</button>
      <button class="tab" onclick="switchTab('register',this)">Register</button>
    </div>

    <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- LOGIN -->
    <div class="panel active" id="panel-login">
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary">Sign In →</button>
      </form>
      <p style="text-align:center;margin-top:16px;font-size:0.82rem;color:var(--muted)">
        Demo: <strong style="color:var(--accent)">demo@example.com</strong> / <strong style="color:var(--accent)">password</strong>
      </p>
    </div>

    <!-- REGISTER -->
    <div class="panel" id="panel-register">
      <form method="POST">
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" placeholder="Ishant Sehrawat" required>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Choose a strong password" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label>Board Marks %</label>
            <input type="number" name="student_marks" placeholder="85.5" min="0" max="100" step="0.1">
          </div>
          <div class="form-group">
            <label>Entrance Score %</label>
            <input type="number" name="entrance_scores" placeholder="78.0" min="0" max="100" step="0.1">
          </div>
        </div>
        <button type="submit" class="btn-primary">Create Account →</button>
      </form>
    </div>
  </div>
</section>

<div class="features">
  <div class="feature">
    <div class="feat-icon">🧠</div>
    <div class="feat-title">AI Prediction Engine</div>
    <div class="feat-desc">Rule-based logic blended with historical admission data for accurate probability scoring.</div>
  </div>
  <div class="feature">
    <div class="feat-icon">🗄️</div>
    <div class="feat-title">Live Database</div>
    <div class="feat-desc">Full PHP + MySQL connectivity — every prediction and record stored in real-time.</div>
  </div>
  <div class="feature">
    <div class="feat-icon">📊</div>
    <div class="feat-title">Historical Trends</div>
    <div class="feat-desc">Visualise past admission outcomes and track how your profile compares over time.</div>
  </div>
  <div class="feature">
    <div class="feat-icon">🎯</div>
    <div class="feat-title">Smart Shortlisting</div>
    <div class="feat-desc">Filter and search colleges by tier, cutoff, and course — find your best-fit instantly.</div>
  </div>
</div>

<footer>Built by <strong>Ishant Sehrawat</strong> · Roll No: 231348016 · BCA AIML · SGT University · Faculty: Palak Kalsi</footer>

<script>
function switchTab(tab, el) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
}
</script>
</body>
</html>
