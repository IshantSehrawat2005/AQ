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
        global $EXAMS;
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $marks = floatval($_POST['student_marks'] ?? 0);
        
        $exam_name = $_POST['exam_name'] ?? 'General/Other';
        $exam_raw  = floatval($_POST['exam_raw_score'] ?? 0);
        $course    = $_POST['course'] ?? '';
        
        $exam_total = $EXAMS[$exam_name] ?? 100;
        $ent = ($exam_raw / $exam_total) * 100;
        if ($ent > 100) $ent = 100;

        if (!$name || !$email || !$pass) { $error = 'All fields are required.'; }
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO students (name, email, password, student_marks, entrance_scores, exam_name, exam_raw_score, preferences) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssdddds', $name, $email, $hash, $marks, $ent, $exam_name, $exam_raw, $course);
            try {
                if ($stmt->execute()) { $success = 'Account created! Please log in.'; }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error = 'Email already registered. Please sign in.';
                } else {
                    $error = 'Registration failed. Please try again later.';
                }
            }
            $stmt->close();
        }
    }
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AdmitIQ — AI College Admission Predictor</title>
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
  --glow: rgba(157, 78, 221, 0.15);
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
    linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
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
  background: radial-gradient(circle, var(--glow) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* LAYOUT */
.container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* NAV */
nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 32px 48px;
  position: absolute; top: 0; left: 0; right: 0; z-index: 100;
  background: transparent;
}
.logo {
  font-family: 'Dancing Script', cursive;
  font-size: 3rem; font-weight: 700; letter-spacing: 2px;
  background: linear-gradient(135deg, var(--accent), var(--accent2), var(--accent3));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  line-height: 1; margin-top: -5px;
}

/* HERO */
.hero {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 100px; align-items: center;
  padding: 140px 48px 80px; /* Increased top padding to account for absolute nav */
  position: relative; z-index: 1;
}
h1 {
  font-family: 'Inter', sans-serif;
  font-size: 3.8rem; font-weight: 700;
  line-height: 1.15; letter-spacing: -1px;
  margin-bottom: 20px;
}
h1 em { font-style: normal; color: var(--accent); }

.hero-sub {
  color: var(--muted); font-size: 1.1rem; line-height: 1.8;
  margin-bottom: 40px; max-width: 500px;
}

.stats-row {
  display: flex; gap: 32px; margin-bottom: 40px;
}
.stat { display: flex; flex-direction: column; }
.stat-num {
  font-family: 'Inter', sans-serif;
  font-size: 1.8rem; font-weight: 700;
  color: var(--accent);
}
.stat-label { font-size: 0.78rem; color: var(--muted); }

.form-card {
  background: rgba(17, 24, 39, 0.4);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 48px 40px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 80px rgba(0,0,0,0.4), inset 0 0 0 1px rgba(255,255,255,0.05);
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
  font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600;
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
input, select {
  width: 100%; padding: 14px 16px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px; color: var(--text); font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem; transition: all 0.2s;
  outline: none;
}
input:focus, select:focus {
  border-color: var(--accent);
  background: rgba(255,255,255,0.05);
  box-shadow: 0 0 0 3px rgba(110,231,183,0.1);
}
input::placeholder { color: var(--muted); }
select option { background: var(--bg); color: var(--text); }

.btn-primary {
  width: 100%; padding: 16px;
  background: linear-gradient(135deg, var(--accent2), var(--accent3)); color: #fff;
  border: none; border-radius: 12px;
  font-family: 'Inter', sans-serif; font-size: 1.05rem; font-weight: 700;
  cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: 0.3px;
  box-shadow: 0 4px 15px rgba(157, 78, 221, 0.2);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(157, 78, 221, 0.4); }

.msg { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.88rem; }
.msg.error { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3); color: var(--danger); }
.msg.success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.3); color: var(--success); }

/* FEATURES STRIP */
.features {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 20px; 
  padding: 40px 48px;
  position: relative; z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
}
.feature {
  background: rgba(17, 24, 39, 0.4);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  padding: 32px 28px;
  display: flex; flex-direction: column; gap: 12px;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.feature:hover { 
  background: rgba(255,255,255,0.06);
  transform: translateY(-8px);
  box-shadow: 0 15px 40px rgba(157, 78, 221, 0.2);
  border-color: rgba(255,255,255,0.15);
}
.feat-icon { font-size: 2rem; margin-bottom: 8px; }
.feat-title { font-family: 'Inter', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--text); }
.feat-desc { font-size: 0.85rem; color: var(--muted); line-height: 1.6; }

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


<nav>
  <div class="logo">AQ</div>
</nav>

<section class="hero">
  <div class="hero-left">
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
            <label>Target Course</label>
            <select name="course" required>
              <option value="" disabled selected>Select Course</option>
              <?php global $COURSES; foreach ($COURSES as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Board Marks %</label>
            <input type="number" name="student_marks" placeholder="85.5" min="0" max="100" step="0.1" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label>Entrance Exam</label>
            <select name="exam_name" id="exam_select" onchange="updateExamMax()" required>
              <option value="" disabled selected>Select Exam</option>
              <?php global $EXAMS; foreach ($EXAMS as $ex => $max): ?>
                <option value="<?= htmlspecialchars($ex) ?>" data-max="<?= $max ?>"><?= htmlspecialchars($ex) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Exam Raw Score</label>
            <input type="number" name="exam_raw_score" id="exam_raw" placeholder="Score" min="0" step="1" required>
            <div id="exam_hint" style="font-size:0.7rem; color:var(--muted); margin-top:4px; text-align:right;"></div>
          </div>
        </div>
        <button type="submit" class="btn-primary" style="margin-top: 10px;">Create Account →</button>
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

<div class="how-it-works-section" style="border-top: 1px solid rgba(255,255,255,0.05); background: rgba(5,8,15,0.6); padding: 60px 48px; border-bottom: 1px solid rgba(255,255,255,0.05);">
  <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
    <h2 style="font-family: 'Inter', sans-serif; font-size: 2.2rem; margin-bottom: 16px; background: linear-gradient(135deg, var(--text), var(--muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">How It Works</h2>
    <p style="color: var(--muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 40px; max-width: 700px; margin-left: auto; margin-right: auto;">Our prediction engine uses a sophisticated algorithm trained on years of historical admission data. It evaluates your board marks, entrance exam scores, and preferred college cutoffs to give you a realistic admission probability.</p>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; text-align: left;">
      <div style="background: rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); padding: 32px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 30px rgba(0,0,0,0.2);" onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 15px 40px rgba(157, 78, 221, 0.2)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';">
        <div style="color: var(--accent); font-weight: 800; font-family: 'Inter', sans-serif; font-size: 1.4rem; margin-bottom: 12px;">1. Profile Setup</div>
        <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6;">Enter your academic details securely into our system, including your latest exam scores and course preferences.</div>
      </div>
      <div style="background: rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); padding: 32px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 30px rgba(0,0,0,0.2);" onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 15px 40px rgba(157, 78, 221, 0.2)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';">
        <div style="color: var(--accent2); font-weight: 800; font-family: 'Inter', sans-serif; font-size: 1.4rem; margin-bottom: 12px;">2. AI Analysis</div>
        <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6;">We compare your profile against historical cutoffs and trends, normalizing your scores against college requirements.</div>
      </div>
      <div style="background: rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); padding: 32px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 30px rgba(0,0,0,0.2);" onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 15px 40px rgba(157, 78, 221, 0.2)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';">
        <div style="color: var(--accent3); font-weight: 800; font-family: 'Inter', sans-serif; font-size: 1.4rem; margin-bottom: 12px;">3. Get Results</div>
        <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6;">Instantly view your admission chances and tailored recommendations for top-tier and backup colleges.</div>
      </div>
    </div>
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

function updateExamMax() {
  const sel = document.getElementById('exam_select');
  const max = sel.options[sel.selectedIndex].getAttribute('data-max');
  const input = document.getElementById('exam_raw');
  const hint = document.getElementById('exam_hint');
  if (max) {
    input.setAttribute('max', max);
    input.placeholder = "Out of " + max;
    hint.innerText = "Max marks: " + max;
  }
}
</script>
</body>
</html>
