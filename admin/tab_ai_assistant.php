<?php
if (!$isLoggedIn) exit;

// ──────────────────────────────────────────────────────────────
//  HANDLE SAVE CUSTOM CSS (dari AI atau manual)
// ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Simpan Custom CSS ke file
    if ($_POST['action'] === 'save_custom_css') {
        $css = $_POST['custom_css'] ?? '';
        $cssFile = __DIR__ . '/../css/ai_custom.css';
        file_put_contents($cssFile, $css);
        header("Location: index.php?tab=ai_assistant&saved=css");
        exit;
    }

    // Simpan Custom HTML Inject (contoh: banner, notice, dll)
    if ($_POST['action'] === 'save_custom_html') {
        $html = $_POST['custom_html'] ?? '';
        $htmlFile = __DIR__ . '/../ai_inject.html';
        file_put_contents($htmlFile, $html);
        header("Location: index.php?tab=ai_assistant&saved=html");
        exit;
    }

    // Reset CSS
    if ($_POST['action'] === 'reset_css') {
        $cssFile = __DIR__ . '/../css/ai_custom.css';
        file_put_contents($cssFile, '/* AI Custom CSS — kosong */');
        header("Location: index.php?tab=ai_assistant&saved=reset");
        exit;
    }
}

// Baca CSS yang tersimpan saat ini
$cssFile    = __DIR__ . '/../css/ai_custom.css';
$htmlFile   = __DIR__ . '/../ai_inject.html';
$savedCss   = file_exists($cssFile) ? file_get_contents($cssFile) : '/* Belum ada custom CSS */';
$savedHtml  = file_exists($htmlFile) ? file_get_contents($htmlFile) : '';
$savedNotif = $_GET['saved'] ?? '';
?>

<div class="page-body">

  <?php if ($savedNotif): ?>
  <div style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.3);color:#34d399;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:0.88rem;">
    ✅ <?= $savedNotif === 'css' ? 'Custom CSS berhasil disimpan dan langsung aktif di halaman customer.' : ($savedNotif === 'html' ? 'Custom HTML inject berhasil disimpan.' : 'CSS direset ke default.') ?>
  </div>
  <?php endif; ?>

  <!-- ── HEADER CARD ── -->
  <div class="table-card" style="margin-bottom:24px;">
    <div style="padding:24px;display:flex;align-items:center;gap:16px;">
      <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#8B5CF6,#EC4899);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">✨</div>
      <div>
        <div class="card-title" style="margin:0;">AI Assistant (Gemini)</div>
        <div style="color:var(--muted);font-size:0.83rem;margin-top:3px;">Gunakan Gemini untuk mengedit tampilan, konten, dan CSS halaman customer secara bebas.</div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- ── KIRI: CHAT GEMINI ── -->
    <div>
      <div class="table-card" style="height:100%;">
        <div style="padding:20px 24px 0;">
          <div class="card-title">💬 Chat dengan Gemini</div>
          <p style="color:var(--muted);font-size:0.8rem;margin-top:6px;">Minta Gemini untuk membuat atau memodifikasi CSS/HTML halaman customer. Hasilnya bisa langsung kamu terapkan.</p>
        </div>

        <!-- Chat History -->
        <div id="aiChatHistory" style="padding:16px 24px;max-height:380px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;min-height:200px;">
          <div class="ai-msg ai-msg-bot">
            <div class="ai-avatar">✨</div>
            <div class="ai-bubble">
              Halo! Aku Gemini. Kamu bisa minta aku untuk:<br>
              • Ubah warna tema halaman customer<br>
              • Modifikasi font, ukuran, padding section<br>
              • Tambahkan animasi atau efek visual<br>
              • Buat banner promo / notice khusus<br>
              • Sembunyikan/tampilkan elemen tertentu<br><br>
              Contoh: <em>"Ubah warna tombol gold menjadi biru tua"</em>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div style="padding:16px 24px 20px;border-top:1px solid var(--border);">
          <div style="display:flex;gap:10px;align-items:flex-end;">
            <textarea id="aiChatInput" placeholder="Contoh: Tambahkan banner promo Ramadan berwarna hijau di bagian atas halaman..." rows="3"
              style="flex:1;background:var(--bg2);border:1px solid var(--border);color:var(--cream);padding:12px;border-radius:8px;font-family:inherit;font-size:0.85rem;resize:none;line-height:1.5;"></textarea>
            <button onclick="sendToGemini()" id="aiBtnSend"
              style="background:linear-gradient(135deg,#8B5CF6,#EC4899);color:#fff;border:none;padding:12px 20px;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.85rem;white-space:nowrap;align-self:stretch;">
              Kirim →
            </button>
          </div>
          <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
            <button class="ai-quick-btn" onclick="setQuick('Ubah warna tombol utama (btn-gold) menjadi biru gelap #1a3a6b dan teksnya putih')">🎨 Ganti warna tombol</button>
            <button class="ai-quick-btn" onclick="setQuick('Tambahkan banner notice berwarna emas di bagian paling atas halaman sebelum navbar yang bertuliskan: \"🎉 PROMO AKHIR BULAN: Diskon 20% untuk semua menu kopi!\"')">📢 Banner promo</button>
            <button class="ai-quick-btn" onclick="setQuick('Ubah font semua heading (h1, h2, h3) menjadi font sans-serif modern, hapus font Cormorant Garamond')">🔤 Ganti font heading</button>
            <button class="ai-quick-btn" onclick="setQuick('Sembunyikan section produk unggulan (#products) dari halaman customer')">🫥 Sembunyikan section</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── KANAN: HASIL & TERAPKAN ── -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Output CSS dari Gemini -->
      <div class="table-card">
        <div style="padding:20px 24px 0;display:flex;justify-content:space-between;align-items:center;">
          <div>
            <div class="card-title">🎨 Custom CSS (Aktif di Customer Page)</div>
            <p style="color:var(--muted);font-size:0.78rem;margin-top:4px;">CSS ini otomatis di-<em>inject</em> ke halaman customer. Edit langsung atau gunakan hasil Gemini.</p>
          </div>
          <div style="display:flex;gap:8px;">
            <button onclick="previewChanges()" style="background:var(--surface2);border:1px solid var(--border);color:var(--cream2);padding:7px 14px;border-radius:6px;cursor:pointer;font-size:0.78rem;">👁 Preview</button>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Reset CSS ke kosong?');">
              <input type="hidden" name="action" value="reset_css">
              <button type="submit" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:7px 14px;border-radius:6px;cursor:pointer;font-size:0.78rem;">🗑 Reset</button>
            </form>
          </div>
        </div>
        <div style="padding:16px 24px 20px;">
          <form method="POST">
            <input type="hidden" name="action" value="save_custom_css">
            <textarea name="custom_css" id="cssEditor"
              style="width:100%;min-height:220px;background:#0a0603;border:1px solid var(--border);color:#a8e6a3;padding:14px;border-radius:8px;font-family:'JetBrains Mono',monospace;font-size:0.78rem;line-height:1.6;resize:vertical;"
              spellcheck="false"><?= htmlspecialchars($savedCss) ?></textarea>
            <button type="submit" style="margin-top:10px;background:var(--gold);color:#0a0703;border:none;padding:10px 24px;border-radius:6px;font-weight:700;cursor:pointer;font-size:0.85rem;width:100%;">
              💾 Simpan & Terapkan ke Customer Page
            </button>
          </form>
        </div>
      </div>

      <!-- HTML Inject -->
      <div class="table-card">
        <div style="padding:20px 24px 0;">
          <div class="card-title">📄 HTML Inject (Banner / Notice)</div>
          <p style="color:var(--muted);font-size:0.78rem;margin-top:4px;">HTML ini akan muncul di bagian paling atas halaman customer (setelah &lt;body&gt;).</p>
        </div>
        <div style="padding:16px 24px 20px;">
          <form method="POST">
            <input type="hidden" name="action" value="save_custom_html">
            <textarea name="custom_html" id="htmlEditor"
              style="width:100%;min-height:100px;background:#0a0603;border:1px solid var(--border);color:#f0d090;padding:14px;border-radius:8px;font-family:'JetBrains Mono',monospace;font-size:0.78rem;line-height:1.6;resize:vertical;"
              spellcheck="false"><?= htmlspecialchars($savedHtml) ?></textarea>
            <button type="submit" style="margin-top:10px;background:var(--surface2);border:1px solid var(--border);color:var(--cream);padding:10px 24px;border-radius:6px;font-weight:600;cursor:pointer;font-size:0.85rem;width:100%;">
              💾 Simpan HTML Inject
            </button>
          </form>
        </div>
      </div>

    </div>
  </div><!-- /grid -->

</div><!-- /page-body -->

<style>
.ai-msg { display:flex; gap:10px; align-items:flex-start; }
.ai-msg-user { flex-direction:row-reverse; }
.ai-avatar { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0; }
.ai-msg-bot .ai-avatar { background:linear-gradient(135deg,#8B5CF6,#EC4899); }
.ai-msg-user .ai-avatar { background:var(--gold-dim);color:var(--gold);font-weight:700;font-size:0.85rem; }
.ai-bubble { background:var(--surface2);border:1px solid var(--border);padding:10px 14px;border-radius:12px;font-size:0.83rem;line-height:1.6;color:var(--cream2);max-width:85%; }
.ai-msg-user .ai-bubble { background:rgba(212,165,90,0.07);border-color:rgba(212,165,90,0.2);text-align:right; }
.ai-bubble code { background:rgba(0,0,0,0.4);padding:1px 5px;border-radius:3px;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#a8e6a3; }
.ai-bubble pre { background:rgba(0,0,0,0.4);padding:10px;border-radius:6px;margin-top:8px;overflow-x:auto;font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:#a8e6a3;white-space:pre-wrap; }
.ai-quick-btn { background:var(--surface2);border:1px solid var(--border);color:var(--cream2);padding:5px 10px;border-radius:20px;cursor:pointer;font-size:0.72rem;transition:all 0.2s; }
.ai-quick-btn:hover { border-color:var(--gold);color:var(--gold); }
.ai-typing { display:flex;align-items:center;gap:6px; }
.ai-typing span { width:6px;height:6px;border-radius:50%;background:#8B5CF6;animation:aiDot 1.2s infinite; }
.ai-typing span:nth-child(2){animation-delay:.2s}
.ai-typing span:nth-child(3){animation-delay:.4s}
@keyframes aiDot { 0%,80%,100%{opacity:.2;transform:scale(0.8)} 40%{opacity:1;transform:scale(1)} }
</style>

<script>
// Riwayat chat untuk konteks multi-turn
const aiHistory = [
  {
    role: 'user',
    parts: [{ text: `Kamu adalah AI assistant untuk admin panel website kafe "Satu Seduh" di Medan. 
Website customer page menggunakan PHP + CSS (dark brown/gold theme). 
Tugas utamamu: ketika admin minta modifikasi tampilan, kamu harus menghasilkan CSS atau HTML yang bisa langsung ditempel ke editor.
Format jawabanmu:
1. Penjelasan singkat apa yang kamu lakukan (1-2 kalimat)
2. Jika ada CSS: tulis di dalam blok \`\`\`css\n...\n\`\`\`
3. Jika ada HTML: tulis di dalam blok \`\`\`html\n...\n\`\`\`
4. Selalu akhiri dengan tombol: [TERAPKAN CSS] atau [TERAPKAN HTML] (di dalam teks, bukan HTML asli, hanya sebagai penanda)
Variabel CSS yang ada: --gold: #d4a55a; --gold2: #e8c07a; --bg: #080502; --cream: #f0e6d3; --muted: #7a6545;
Class penting: .btn-gold, .fnb-card, .hero-inner, .nav-item, .sec-title, #home, #fnb, #reservasi, #products, .navbar` }]
  },
  {
    role: 'model',
    parts: [{ text: 'Siap! Aku mengerti struktur website Satu Seduh dan CSS variables yang tersedia. Silakan minta aku untuk memodifikasi tampilan apa saja.' }]
  }
];

function appendMsg(role, html) {
  const hist = document.getElementById('aiChatHistory');
  const div = document.createElement('div');
  div.className = 'ai-msg ' + (role === 'user' ? 'ai-msg-user' : 'ai-msg-bot');
  div.innerHTML = `
    <div class="ai-avatar">${role === 'user' ? 'A' : '✨'}</div>
    <div class="ai-bubble">${html}</div>
  `;
  hist.appendChild(div);
  hist.scrollTop = hist.scrollHeight;
  return div;
}

function setQuick(txt) {
  document.getElementById('aiChatInput').value = txt;
  document.getElementById('aiChatInput').focus();
}

function formatResponse(text) {
  // Ekstrak CSS block
  let html = text;
  
  // Format code blocks
  html = html.replace(/```css\n?([\s\S]*?)```/g, (_, code) => {
    const trimmed = code.trim();
    window._lastCss = trimmed; // simpan untuk tombol terapkan
    return `<pre>${escHtml(trimmed)}</pre>
      <button onclick="applyCssFromAI()" style="background:linear-gradient(135deg,#8B5CF6,#EC4899);color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer;font-size:0.78rem;font-weight:600;margin-top:4px;">
        ✨ Terapkan CSS Ini
      </button>`;
  });
  html = html.replace(/```html\n?([\s\S]*?)```/g, (_, code) => {
    const trimmed = code.trim();
    window._lastHtml = trimmed;
    return `<pre>${escHtml(trimmed)}</pre>
      <button onclick="applyHtmlFromAI()" style="background:var(--gold);color:#0a0703;border:none;padding:7px 16px;border-radius:6px;cursor:pointer;font-size:0.78rem;font-weight:600;margin-top:4px;">
        📄 Terapkan HTML Ini
      </button>`;
  });
  html = html.replace(/```[\w]*\n?([\s\S]*?)```/g, '<pre>$1</pre>');
  
  // Inline code
  html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
  
  // Newlines
  html = html.replace(/\n/g, '<br>');
  
  return html;
}

function escHtml(t) {
  return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function applyCssFromAI() {
  if (!window._lastCss) return;
  const editor = document.getElementById('cssEditor');
  // Append CSS baru (bukan timpa) agar tidak hilang yang lama
  const existing = editor.value.trim();
  const marker = '/* ── AI Generated: ' + new Date().toLocaleString('id-ID') + ' ── */';
  editor.value = existing + '\n\n' + marker + '\n' + window._lastCss;
  editor.scrollTop = editor.scrollHeight;
  showToastAdmin('CSS ditambahkan ke editor! Klik "Simpan & Terapkan" untuk mengaktifkan.');
}

function applyHtmlFromAI() {
  if (!window._lastHtml) return;
  document.getElementById('htmlEditor').value = window._lastHtml;
  showToastAdmin('HTML inject ditambahkan ke editor! Klik "Simpan HTML Inject" untuk mengaktifkan.');
}

function showToastAdmin(msg) {
  let t = document.getElementById('adminToast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'adminToast';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1a1409;border:1px solid var(--gold);color:var(--cream);padding:12px 20px;border-radius:8px;font-size:0.82rem;z-index:9999;max-width:340px;transition:opacity 0.3s;';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.opacity = '0'; }, 3500);
}

function previewChanges() {
  window.open('../index.php', '_blank');
}

async function sendToGemini() {
  const input = document.getElementById('aiChatInput');
  const btn = document.getElementById('aiBtnSend');
  const msg = input.value.trim();
  if (!msg) return;

  // Tampilkan pesan user
  appendMsg('user', escHtml(msg));
  input.value = '';
  btn.disabled = true;
  btn.textContent = '...';

  // Tambahkan ke history
  aiHistory.push({ role: 'user', parts: [{ text: msg }] });

  // Tampilkan indikator typing
  const typingDiv = appendMsg('bot', '<div class="ai-typing"><span></span><span></span><span></span></div>');

  try {
    const res = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=<?= defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '' ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contents: aiHistory,
        generationConfig: {
          temperature: 0.7,
          maxOutputTokens: 2048,
        }
      })
    });

    const data = await res.json();
    
    if (data.error) {
      throw new Error(data.error.message || 'Gemini API error');
    }

    const replyText = data.candidates?.[0]?.content?.parts?.[0]?.text || 'Tidak ada respons dari Gemini.';
    
    // Simpan ke history
    aiHistory.push({ role: 'model', parts: [{ text: replyText }] });

    // Ganti typing indicator dengan respons asli
    typingDiv.querySelector('.ai-bubble').innerHTML = formatResponse(replyText);

  } catch (err) {
    typingDiv.querySelector('.ai-bubble').innerHTML = `
      <span style="color:#ef4444;">⚠️ Error: ${escHtml(err.message)}</span><br>
      <span style="font-size:0.75rem;color:var(--muted);">Pastikan GEMINI_API_KEY sudah diset di config.php</span>`;
  }

  btn.disabled = false;
  btn.textContent = 'Kirim →';
  const hist = document.getElementById('aiChatHistory');
  hist.scrollTop = hist.scrollHeight;
}

// Kirim dengan Enter (Shift+Enter untuk newline)
document.getElementById('aiChatInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendToGemini();
  }
});
</script>
