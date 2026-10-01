<?php
session_start();
require 'config.php';

// Cek apakah sudah login dan role pegawai
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['nama'];

// Ambil riwayat presensi user ini
$stmt = $conn->prepare("SELECT * FROM presensi WHERE user_id = ? ORDER BY id DESC LIMIT 30");
$stmt->bind_param("i", $userId);
$stmt->execute();
$riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cek apakah sudah absen hari ini
$stmtCek = $conn->prepare("SELECT id FROM presensi WHERE user_id = ? AND DATE(waktu) = CURDATE() LIMIT 1");
$stmtCek->bind_param("i", $userId);
$stmtCek->execute();
$sudahAbsen = $stmtCek->get_result()->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Absen - <?= htmlspecialchars($userName) ?> | Presensi GPS</title>
  <meta name="description" content="Halaman absensi pegawai berbasis GPS.">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --bg: #0f172a;
      --card-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --success: #10b981;
      --error: #ef4444;
      --glass-border: rgba(255, 255, 255, 0.08);
    }

    * { box-sizing: border-box; font-family: 'Outfit', sans-serif; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      background-image:
        radial-gradient(circle at 10% 50%, rgba(79, 70, 229, 0.18), transparent 30%),
        radial-gradient(circle at 90% 20%, rgba(16, 185, 129, 0.12), transparent 30%);
      color: var(--text-main);
      min-height: 100vh;
      padding: 30px 20px;
    }

    .container { max-width: 720px; margin: 0 auto; }

    /* Header */
    .top-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;
      animation: fadeInDown 0.6s ease;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 14px rgba(79,70,229,0.4);
    }

    .brand h1 {
      font-size: 1.3rem;
      font-weight: 800;
      background: linear-gradient(to right, #818cf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .user-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--card-bg);
      border: 1px solid var(--glass-border);
      padding: 8px 16px 8px 8px;
      border-radius: 50px;
    }

    .user-chip .avatar {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
    }

    .user-chip .info { line-height: 1.3; }
    .user-chip .info .name { font-size: 13px; font-weight: 700; }
    .user-chip .info .role { font-size: 11px; color: var(--text-muted); }

    .logout-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #fca5a5;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      padding: 6px 12px;
      background: rgba(239,68,68,0.1);
      border-radius: 8px;
      margin-left: 10px;
      transition: all 0.2s;
    }

    .logout-link:hover { background: rgba(239,68,68,0.2); }

    /* Already absen banner */
    .already-banner {
      background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.08));
      border: 1px solid rgba(16,185,129,0.3);
      border-radius: 14px;
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
      animation: fadeInUp 0.6s ease;
    }

    .already-banner .check-icon {
      width: 48px;
      height: 48px;
      background: rgba(16,185,129,0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .already-banner .info .title {
      font-size: 1rem;
      font-weight: 700;
      color: #34d399;
      margin-bottom: 4px;
    }

    .already-banner .info .sub {
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Card */
    .card {
      background: var(--card-bg);
      border-radius: 18px;
      padding: 30px;
      border: 1px solid var(--glass-border);
      box-shadow: 0 12px 40px rgba(0,0,0,0.3);
      margin-bottom: 24px;
      animation: fadeInUp 0.6s ease;
    }

    .card h2 {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group { margin-bottom: 18px; }

    label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    input[type="text"], select {
      width: 100%;
      padding: 13px 16px;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--glass-border);
      border-radius: 10px;
      font-size: 15px;
      color: white;
      transition: all 0.2s;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    input:read-only {
      background: rgba(79, 70, 229, 0.08);
      border-color: rgba(79, 70, 229, 0.2);
      color: #a5b4fc;
      cursor: not-allowed;
    }

    select option { background: var(--card-bg); }

    /* GPS status */
    .gps-status {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      background: rgba(15, 23, 42, 0.5);
      border: 1px solid var(--glass-border);
      border-radius: 10px;
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 18px;
    }

    .gps-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #475569;
      flex-shrink: 0;
      transition: background 0.3s;
    }

    .gps-dot.loading { background: #fcd34d; animation: blink 1s infinite; }
    .gps-dot.ready   { background: #34d399; }
    .gps-dot.error   { background: #ef4444; }

    .btn-absen {
      width: 100%;
      padding: 17px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 800;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: all 0.3s;
      box-shadow: 0 6px 24px rgba(79, 70, 229, 0.5);
    }

    .btn-absen:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 32px rgba(79, 70, 229, 0.7);
    }

    .btn-absen:disabled {
      background: #334155;
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }

    .status-msg {
      margin-top: 16px;
      padding: 14px 18px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      display: none;
      animation: fadeIn 0.4s;
    }

    .success-msg { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .error-msg   { background: rgba(239,68,68,0.1);  color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }

    /* Riwayat */
    .table-responsive { overflow-x: auto; }

    table { width: 100%; border-collapse: separate; border-spacing: 0; }

    th, td {
      padding: 12px 16px;
      text-align: left;
      font-size: 13px;
      border-bottom: 1px solid var(--glass-border);
    }

    th {
      background: rgba(15, 23, 42, 0.7);
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 1px;
    }

    th:first-child { border-top-left-radius: 8px; }
    th:last-child  { border-top-right-radius: 8px; }
    tr:hover { background: rgba(255,255,255,0.025); }

    .badge {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }

    .badge-Hadir { background: rgba(16,185,129,0.2); color: #34d399; }
    .badge-WFH   { background: rgba(56,189,248,0.2); color: #7dd3fc; }
    .badge-Izin  { background: rgba(251,191,36,0.2); color: #fcd34d; }
    .badge-Sakit { background: rgba(239,68,68,0.2);  color: #fca5a5; }
    .badge-Dinas { background: rgba(168,85,247,0.2); color: #c084fc; }

    .map-link {
      color: #38bdf8;
      text-decoration: none;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      transition: color 0.2s;
    }

    .map-link:hover { color: #7dd3fc; }

    .empty-msg {
      text-align: center;
      padding: 30px;
      color: var(--text-muted);
      font-size: 13px;
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }
  </style>
</head>
<body>

<div class="container">

  <!-- Header -->
  <div class="top-header">
    <div class="brand">
      <div class="brand-icon">📍</div>
      <h1>Presensi GPS</h1>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <div class="user-chip">
        <div class="avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
        <div class="info">
          <div class="name"><?= htmlspecialchars($userName) ?></div>
          <div class="role">Pegawai</div>
        </div>
      </div>
      <a href="logout.php" class="logout-link">🚪 Keluar</a>
    </div>
  </div>

  <!-- Sudah absen hari ini? -->
  <?php if ($sudahAbsen): ?>
  <div class="already-banner">
    <div class="check-icon">✅</div>
    <div class="info">
      <div class="title">Kamu sudah absen hari ini!</div>
      <div class="sub">Presensi hari ini sudah tercatat. Sampai jumpa besok! 🎉</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Form Absen -->
  <div class="card">
    <h2>🕐 Form Absensi</h2>

    <div class="form-group">
      <label>Nama Lengkap</label>
      <input type="text" id="namaField" value="<?= htmlspecialchars($userName) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Status Kehadiran</label>
      <select id="statusAbsen">
        <option value="Hadir">🟢 Hadir</option>
        <option value="WFH / Remote">🏠 WFH / Remote</option>
        <option value="Dinas Luar">🚗 Dinas Luar</option>
        <option value="Izin">📝 Izin</option>
        <option value="Sakit">🤒 Sakit</option>
      </select>
    </div>

    <div class="gps-status">
      <span class="gps-dot" id="gpsDot"></span>
      <span id="gpsText">Menunggu akses lokasi...</span>
    </div>

    <button class="btn-absen" id="btnAbsen" <?= $sudahAbsen ? 'disabled' : '' ?>>
      <?= $sudahAbsen ? '✅ Sudah Absen Hari Ini' : '📍 Absen Sekarang' ?>
    </button>

    <div class="status-msg" id="statusMsg"></div>
  </div>

  <!-- Riwayat Presensi -->
  <div class="card">
    <h2>📋 Riwayat Presensi Saya (30 Terakhir)</h2>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Waktu</th>
            <th>Status</th>
            <th>Lokasi</th>
          </tr>
        </thead>
        <tbody id="tabelRiwayat">
          <?php if (empty($riwayat)): ?>
          <tr><td colspan="4" class="empty-msg">Belum ada riwayat presensi.</td></tr>
          <?php else: $i = 1; foreach ($riwayat as $r): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td style="color:var(--text-muted);"><?= htmlspecialchars($r['waktu']) ?></td>
            <td>
              <?php
                $bc = 'badge';
                if (str_contains($r['status'], 'Hadir')) $bc .= ' badge-Hadir';
                elseif (str_contains($r['status'], 'WFH'))  $bc .= ' badge-WFH';
                elseif (str_contains($r['status'], 'Izin')) $bc .= ' badge-Izin';
                elseif (str_contains($r['status'], 'Sakit')) $bc .= ' badge-Sakit';
                elseif (str_contains($r['status'], 'Dinas')) $bc .= ' badge-Dinas';
              ?>
              <span class="<?= $bc ?>"><?= htmlspecialchars($r['status']) ?></span>
            </td>
            <td>
              <a href="https://www.google.com/maps?q=<?= htmlspecialchars($r['lokasi']) ?>" target="_blank" class="map-link">
                📍 <?= htmlspecialchars($r['lokasi']) ?>
              </a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  const USER_ID  = <?= $userId ?>;
  const sudahAbsen = <?= $sudahAbsen ? 'true' : 'false' ?>;

  const btnAbsen  = document.getElementById('btnAbsen');
  const statusMsg = document.getElementById('statusMsg');
  const gpsDot    = document.getElementById('gpsDot');
  const gpsText   = document.getElementById('gpsText');

  let userLat = null, userLng = null;

  function setGps(state, text) {
    gpsDot.className = 'gps-dot ' + state;
    gpsText.textContent = text;
  }

  // Ambil lokasi saat halaman load
  if (!sudahAbsen && navigator.geolocation) {
    setGps('loading', 'Mendapatkan lokasi GPS...');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        userLat = pos.coords.latitude.toFixed(5);
        userLng = pos.coords.longitude.toFixed(5);
        setGps('ready', `Lokasi: ${userLat}, ${userLng} ✓`);
      },
      (err) => {
        if (err.code === err.PERMISSION_DENIED) {
          setGps('error', 'Izin lokasi ditolak! Harap izinkan akses GPS di browser.');
        } else {
          setGps('error', 'Gagal mendapatkan lokasi GPS.');
        }
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  } else if (!navigator.geolocation) {
    setGps('error', 'Browser tidak mendukung Geolocation.');
  }

  function showMsg(text, isError = false) {
    statusMsg.style.display = 'block';
    statusMsg.className = 'status-msg ' + (isError ? 'error-msg' : 'success-msg');
    statusMsg.innerHTML = text;
  }

  btnAbsen.addEventListener('click', async () => {
    if (sudahAbsen) return;

    if (!userLat || !userLng) {
      showMsg('⚠️ Lokasi GPS belum tersedia. Harap izinkan akses lokasi.', true);
      return;
    }

    const nama    = document.getElementById('namaField').value;
    const status  = document.getElementById('statusAbsen').value;
    const lokasi  = `${userLat}, ${userLng}`;

    btnAbsen.disabled = true;
    btnAbsen.textContent = '💾 Menyimpan...';
    statusMsg.style.display = 'none';

    try {
      const res = await fetch('api.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nama, status, lokasi, user_id: USER_ID })
      });
      const result = await res.json();

      if (result.success) {
        showMsg(`✅ Presensi berhasil! Lokasi: (${lokasi}). Halaman akan dimuat ulang...`);
        btnAbsen.textContent = '✅ Absen Berhasil!';
        setTimeout(() => location.reload(), 2000);
      } else {
        showMsg(`❌ Gagal: ${result.message}`, true);
        btnAbsen.disabled = false;
        btnAbsen.textContent = '📍 Absen Sekarang';
      }
    } catch (e) {
      showMsg('❌ Gagal menghubungi server.', true);
      btnAbsen.disabled = false;
      btnAbsen.textContent = '📍 Absen Sekarang';
    }
  });
</script>

</body>
</html>
