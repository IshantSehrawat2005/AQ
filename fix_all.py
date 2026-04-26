import re
import os

def process_file(filepath, info_block, is_index=False):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Move PHP block from body to top
    php_match = re.search(r'<\?php\nrequire_once .*?\?>', content, re.DOTALL)
    if php_match:
        php_block = php_match.group(0)
        content = content.replace(php_block, '')
        content = php_block + '\n' + content

    # Replace Syne with Inter
    content = content.replace('Syne', 'Inter')

    # Add info block
    if is_index:
        if 'How It Works' not in content:
            content = content.replace('</div>\n</div>\n\n<footer>', '</div>\n</div>\n\n' + info_block + '\n<footer>')
    else:
        if info_block and info_block not in content:
            content = content.replace('<div class="page-sub">', '<div class="page-sub">', 1)
            content = content.replace('<div class="page-sub">', '<div class="page-sub">', 1) # dummy
            sub_match = re.search(r'(<div class="page-sub">.*?</div>)', content)
            if sub_match:
                content = content.replace(sub_match.group(1), sub_match.group(1) + '\n\n' + info_block)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

info_index = """<div class="features" style="border-top: none; background: var(--bg); padding: 40px 48px; border-bottom: 1px solid var(--border);">
  <div style="max-width: 800px; margin: 0 auto; text-align: center;">
    <h2 style="font-family: 'Inter', sans-serif; font-size: 2rem; margin-bottom: 16px;">How It Works</h2>
    <p style="color: var(--muted); font-size: 1rem; line-height: 1.6; margin-bottom: 32px;">Our prediction engine uses a sophisticated algorithm trained on years of historical admission data. It evaluates your board marks, entrance exam scores, and preferred college cutoffs to give you a realistic admission probability.</p>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; text-align: left;">
      <div style="background: var(--surface); padding: 24px; border-radius: 16px; border: 1px solid var(--border);">
        <div style="color: var(--accent); font-weight: bold; font-size: 1.2rem; margin-bottom: 8px;">1. Profile Setup</div>
        <div style="color: var(--muted); font-size: 0.9rem;">Enter your academic details securely into our system.</div>
      </div>
      <div style="background: var(--surface); padding: 24px; border-radius: 16px; border: 1px solid var(--border);">
        <div style="color: var(--accent2); font-weight: bold; font-size: 1.2rem; margin-bottom: 8px;">2. AI Analysis</div>
        <div style="color: var(--muted); font-size: 0.9rem;">We compare your profile against historical cutoffs and trends.</div>
      </div>
      <div style="background: var(--surface); padding: 24px; border-radius: 16px; border: 1px solid var(--border);">
        <div style="color: var(--accent3); font-weight: bold; font-size: 1.2rem; margin-bottom: 8px;">3. Get Results</div>
        <div style="color: var(--muted); font-size: 0.9rem;">Instantly view your admission chances and tailored recommendations.</div>
      </div>
    </div>
  </div>
</div>"""

info_dashboard = """  <div style="background: rgba(110, 231, 183, 0.05); border: 1px solid rgba(110, 231, 183, 0.2); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">💡</div>
    <div>
      <div style="font-weight: 700; color: var(--accent); margin-bottom: 4px;">Quick Tip</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Keep your board and entrance scores updated in the Profile section to ensure the AI prediction engine gives you the most accurate results. You can run unlimited predictions to simulate different scoring scenarios!</div>
    </div>
  </div>"""

info_colleges = """  <div style="background: rgba(129, 140, 248, 0.05); border: 1px solid rgba(129, 140, 248, 0.2); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🏛️</div>
    <div>
      <div style="font-weight: 700; color: var(--accent2); margin-bottom: 4px;">About College Tiers</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Colleges are categorized into tiers based on their national ranking, placement records, and academic rigor. Tier 1 colleges typically have the highest cutoffs and emphasize entrance exam scores, while Tier 2 and Tier 3 provide excellent opportunities with more balanced admission criteria.</div>
    </div>
  </div>"""

info_history = """  <div style="background: rgba(251, 146, 60, 0.05); border: 1px solid rgba(251, 146, 60, 0.2); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">📈</div>
    <div>
      <div style="font-weight: 700; color: var(--accent3); margin-bottom: 4px;">Why Track Your History?</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Keeping a log of your actual admission outcomes helps refine our AI model over time. It also allows you to see your progress and make more informed decisions about future applications. Your data is kept private and secure.</div>
    </div>
  </div>"""

info_predict = """  <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🤖</div>
    <div>
      <div style="font-weight: 700; color: var(--text); margin-bottom: 4px;">How the Prediction Engine Works</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Our algorithm calculates your admission probability by analyzing your board marks and entrance scores against the historical cutoffs of your selected colleges. It adjusts the probability based on the college tier, giving a higher weight to entrance exams for top-tier institutions.</div>
    </div>
  </div>"""

info_profile = """  <div style="background: rgba(110, 231, 183, 0.05); border: 1px solid rgba(110, 231, 183, 0.2); border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
    <div style="font-size: 1.5rem; margin-top: 2px;">🛡️</div>
    <div>
      <div style="font-weight: 700; color: var(--accent); margin-bottom: 4px;">Data Privacy & Security</div>
      <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.5;">Your profile information is securely stored in our MySQL database. Passwords are fully encrypted. Make sure your academic details are up-to-date so the prediction engine can give you the best results.</div>
    </div>
  </div>"""


process_file('index.php', info_index, True)
process_file('pages/dashboard.php', info_dashboard)
process_file('pages/colleges.php', info_colleges)
process_file('pages/history.php', info_history)
process_file('pages/predict.php', info_predict)
process_file('pages/profile.php', info_profile)

# Also fix the index.php try catch and css tweaks
with open('index.php', 'r', encoding='utf-8') as f:
    idx = f.read()

# fix try catch
idx = idx.replace('''if ($stmt->execute()) { $success = 'Account created! Please log in.'; }
            else { $error = 'Email already registered.'; }''', '''try {
                if ($stmt->execute()) { $success = 'Account created! Please log in.'; }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error = 'Email already registered. Please sign in.';
                } else {
                    $error = 'Registration failed. Please try again later.';
                }
            }''')

# fix CSS
idx = idx.replace('gap: 80px;', 'gap: 100px;').replace('padding: 80px 48px 60px;', 'padding: 100px 48px 80px;')
idx = idx.replace('font-size: 3.5rem; font-weight: 800;', 'font-size: 3.8rem; font-weight: 700;')
idx = idx.replace('line-height: 1.08; letter-spacing: -2px;', 'line-height: 1.15; letter-spacing: -1px;')
idx = idx.replace('font-size: 1.05rem; line-height: 1.7;\n  margin-bottom: 40px; max-width: 460px;', 'font-size: 1.1rem; line-height: 1.8;\n  margin-bottom: 40px; max-width: 500px;')
idx = idx.replace('.form-card {\n  background: var(--card);\n  border: 1px solid var(--border);\n  border-radius: 20px;\n  padding: 40px;', '.form-card {\n  background: var(--card);\n  border: 1px solid var(--border);\n  border-radius: 20px;\n  padding: 48px 40px;')
idx = idx.replace('width: 100%; padding: 12px 16px;', 'width: 100%; padding: 14px 16px;')
idx = idx.replace('width: 100%; padding: 14px;', 'width: 100%; padding: 16px;').replace("font-family: 'Inter', sans-serif; font-size: 1rem; font-weight: 700;", "font-family: 'Inter', sans-serif; font-size: 1.05rem; font-weight: 700;")

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(idx)

print("Done")
