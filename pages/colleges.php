<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$colleges = $db->query("SELECT * FROM colleges ORDER BY tier, cutoff_scores DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Colleges — AdmitIQ</title>
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
.main { margin-left:240px; flex:1; padding:40px 48px; position:relative; z-index:1; }
.page-title { font-family:'Inter',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; }
.page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:36px; }

/* SEARCH */
.search-bar { display:flex; gap:12px; margin-bottom:24px; align-items:center; flex-wrap:wrap; }
.search-input {
  flex:1; min-width:200px; padding:12px 18px;
  background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(12px); border:1px solid var(--border); border-radius:12px;
  color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.95rem; outline:none;
  transition:border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(157, 78, 221, 0.2); }
.filter-btn { padding:10px 18px; border-radius:20px; border:1px solid var(--border); background:none; color:var(--muted); font-size:0.82rem; cursor:pointer; transition:all 0.2s; font-family:'DM Sans',sans-serif; }
.filter-btn.active { border-color:var(--accent); color:var(--accent); background:rgba(110,231,183,0.08); }

/* COLLEGE CARDS */
.colleges-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.coll-card {
  background:rgba(28, 24, 54, 0.4); backdrop-filter: blur(24px); border:1px solid var(--border); border-radius:16px;
  padding:24px; transition:all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position:relative; overflow:hidden;
}
.coll-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.coll-card[data-tier="Tier 1"]::before { background:linear-gradient(90deg,var(--accent2),var(--accent3)); }
.coll-card[data-tier="Tier 2"]::before { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.coll-card[data-tier="Tier 3"]::before { background:linear-gradient(90deg,var(--accent3),var(--danger)); }
.coll-card:hover { transform:translateY(-6px); border-color:rgba(157, 78, 221, 0.3); box-shadow:0 12px 40px rgba(157, 78, 221, 0.15); }
.coll-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
.coll-name { font-family:'Inter',sans-serif; font-weight:700; font-size:1rem; flex:1; }
.tier-badge { padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:600; white-space:nowrap; }
.tier1 { background:rgba(129,140,248,0.1); color:var(--accent2); }
.tier2 { background:rgba(110,231,248,0.1); color:var(--accent); }
.tier3 { background:rgba(251,146,60,0.1); color:var(--accent3); }
.coll-loc { font-size:0.82rem; color:var(--muted); margin-bottom:14px; }
.coll-stats { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px; }
.coll-stat { background:var(--surface); border-radius:8px; padding:10px; }
.cs-label { font-size:0.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; }
.cs-val { font-family:'Inter',sans-serif; font-weight:700; font-size:1rem; color:var(--accent); margin-top:2px; }
.courses { font-size:0.78rem; color:var(--muted); line-height:1.5; }
.courses strong { color:var(--text); }
.cta-link {
  display:block; text-align:center; padding:10px; margin-top:14px;
  background:rgba(110,231,183,0.08); border:1px solid rgba(110,231,183,0.2);
  border-radius:8px; color:var(--accent); text-decoration:none;
  font-size:0.82rem; font-weight:600; transition:all 0.2s;
}
.cta-link:hover { background:rgba(110,231,183,0.15); }
.no-results { text-align:center; padding:60px; color:var(--muted); font-size:0.9rem; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">AQ</div>
  <nav>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="predict.php"   class="nav-item"><span class="nav-icon">🧠</span> Run Prediction</a>
    <a href="colleges.php"  class="nav-item active"><span class="nav-icon">🏛️</span> Browse Colleges</a>
    <a href="history.php"   class="nav-item"><span class="nav-icon">📋</span> My History</a>
    <a href="profile.php"   class="nav-item"><span class="nav-icon">👤</span> My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>
</aside>
<main class="main">
  <div class="page-title">🏛️ Browse Colleges</div>
  <div class="page-sub"><?= count($colleges) ?> colleges in our database — explore cutoffs, courses and locations</div>

  <div style="background: rgba(157, 78, 221, 0.05); border: 1px solid rgba(157, 78, 221, 0.2); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🏛️</div>
    <div>
      <div style="font-weight: 700; color: var(--accent2); margin-bottom: 4px;">About College Tiers</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Colleges are categorized into tiers based on their national ranking, placement records, and academic rigor. Tier 1 colleges typically have the highest cutoffs and emphasize entrance exam scores, while Tier 2 and Tier 3 provide excellent opportunities with more balanced admission criteria.</div>
    </div>
  </div>

  <div class="search-bar">
    <input type="text" class="search-input" id="search" placeholder="Search colleges, locations, courses..." oninput="filterCards()">
    <button class="filter-btn active" onclick="setTier('all',this)">All Tiers</button>
    <button class="filter-btn" onclick="setTier('Tier 1',this)">Tier 1</button>
    <button class="filter-btn" onclick="setTier('Tier 2',this)">Tier 2</button>
    <button class="filter-btn" onclick="setTier('Tier 3',this)">Tier 3</button>
  </div>

  <div class="colleges-grid" id="grid">
  <?php foreach($colleges as $c):
    $tierCls = strtolower(str_replace(' ','',$c['tier']));
    $exams = isset($c['accepted_exams']) ? $c['accepted_exams'] : 'JEE Main';
  ?>
    <div class="coll-card" data-tier="<?= $c['tier'] ?>" data-name="<?= strtolower($c['college_name']) ?>" data-loc="<?= strtolower($c['location']) ?>" data-courses="<?= strtolower($c['course_list']) ?>" data-exams="<?= strtolower($exams) ?>">
      <div class="coll-header">
        <div class="coll-name"><?= htmlspecialchars($c['college_name']) ?></div>
        <span class="tier-badge <?= $tierCls ?>"><?= $c['tier'] ?></span>
      </div>
      <div class="coll-loc">📍 <?= htmlspecialchars($c['location']) ?></div>
      <div class="coll-stats">
        <div class="coll-stat">
          <div class="cs-label">Board Cutoff</div>
          <div class="cs-val"><?= $c['cutoff_scores'] ?>%</div>
        </div>
        <div class="coll-stat">
          <div class="cs-label">Entrance Cutoff</div>
          <div class="cs-val"><?= $c['entrance_cutoff'] ?>%</div>
        </div>
      </div>
      <div class="courses"><strong>Courses:</strong> <?= htmlspecialchars($c['course_list']) ?></div>
      <div class="courses" style="margin-top:4px;"><strong>Accepted Exams:</strong> <span style="color:var(--accent);"><?= htmlspecialchars($exams) ?></span></div>
      <a href="predict.php" class="cta-link">Check My Chances →</a>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="no-results" id="noResults" style="display:none">No colleges match your search.</div>
</main>
<?php $db->close(); ?>
<script>
let currentTier = 'all';
function setTier(tier, btn) {
  currentTier = tier;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  filterCards();
}
function filterCards() {
  const q = document.getElementById('search').value.toLowerCase();
  let visible = 0;
  document.querySelectorAll('.coll-card').forEach(c=>{
    const tierMatch = currentTier==='all' || c.dataset.tier===currentTier;
    const textMatch = !q || c.dataset.name.includes(q) || c.dataset.loc.includes(q) || c.dataset.courses.includes(q) || c.dataset.exams.includes(q);
    c.style.display = (tierMatch&&textMatch)?'':'none';
    if(tierMatch&&textMatch) visible++;
  });
  document.getElementById('noResults').style.display = visible?'none':'block';
}
</script>
</body>
</html>
