<?php
session_start();
require 'config.php';

// Cek apakah sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminNama = $_SESSION['nama'];

// Handle AJAX-like actions via POST
$action = $_GET['action'] ?? '';

if ($action === 'tambah_pegawai' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'pegawai';

    if ($nama && $username && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $username, $hash, $role);
        if ($stmt->execute()) {
            $msg = ['type' => 'success', 'text' => "Akun '$username' berhasil dibuat!"];
        } else {
            $msg = ['type' => 'error', 'text' => 'Username sudah digunakan atau terjadi kesalahan.'];
        }
    } else {
        $msg = ['type' => 'error', 'text' => 'Semua field wajib diisi!'];
    }
}

if ($action === 'hapus_user' && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $uid");
        $msg = ['type' => 'success', 'text' => 'Akun berhasil dihapus.'];
    } else {
        $msg = ['type' => 'error', 'text' => 'Tidak bisa menghapus akun sendiri!'];
    }
}

// Ambil semua user
$users = $conn->query("SELECT id, nama, username, role, created_at FROM users ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil semua presensi
$presensiList = $conn->query("SELECT * FROM presensi ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Statistik
$totalPresensi = count($presensiList);
$totalPegawai  = count(array_filter($users, fn($u) => $u['role'] === 'pegawai'));
$hariIni = $conn->query("SELECT COUNT(*) as c FROM presensi WHERE DATE(waktu) = CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Sistem Presensi GPS</title>
  <meta name="description" content="Panel admin untuk manajemen presensi GPS dan akun pegawai.">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --bg: #0f172a;
      --sidebar-bg: #0d1526;
      --card-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --success: #10b981;
      --error: #ef4444;
      --warning: #f59e0b;
      --glass-border: rgba(255, 255, 255, 0.08);
    }

    * { box-sizing: border-box; font-family: 'Outfit', sans-serif; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      min-height: 100vh;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--glass-border);
      display: flex;
      flex-direction: column;
      padding: 28px 0;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 100;
    }

    .sidebar-logo {
      padding: 0 24px 28px;
      border-bottom: 1px solid var(--glass-border);
      margin-bottom: 20px;
    }

    .sidebar-logo .icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 12px;
      box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
    }

    .sidebar-logo h2 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-main);
    }

    .sidebar-logo p {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .nav-section {
      padding: 0 12px;
      margin-bottom: 8px;
    }

    .nav-label {
      font-size: 11px;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 0 12px;
      margin-bottom: 6px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 16px;
      border-radius: 10px;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 14px;
      font-weight: 600;
      transition: all 0.2s;
      margin-bottom: 2px;
      text-decoration: none;
      border: none;
      background: none;
      width: 100%;
    }

    .nav-item:hover {
      background: rgba(255,255,255,0.06);
      color: var(--text-main);
    }

    .nav-item.active {
      background: rgba(79, 70, 229, 0.2);
      color: #818cf8;
      border: 1px solid rgba(79, 70, 229, 0.25);
    }

    .nav-item .dot {
      width: 8px;
      height: 8px;
      background: var(--success);
      border-radius: 50%;
      margin-left: auto;
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 16px 24px;
      border-top: 1px solid var(--glass-border);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
    }

    .avatar {
      width: 38px;
      height: 38px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
    }

    .user-info .name { font-size: 14px; font-weight: 600; }
    .user-info .role-badge {
      font-size: 11px;
      color: #818cf8;
      background: rgba(79,70,229,0.15);
      padding: 2px 8px;
      border-radius: 20px;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      padding: 10px 14px;
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
      border-radius: 10px;
      color: #fca5a5;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      justify-content: center;
    }

    .logout-btn:hover {
      background: rgba(239, 68, 68, 0.2);
      color: #ff8080;
    }

    /* Main content */
    .main {
      margin-left: 260px;
      flex: 1;
      padding: 32px;
      min-height: 100vh;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;
    }

    .topbar h1 {
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(to right, #818cf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .topbar .date {
      font-size: 14px;
      color: var(--text-muted);
      background: var(--card-bg);
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid var(--glass-border);
    }

    /* Stats cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 28px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 16px;
      padding: 24px;
      border: 1px solid var(--glass-border);
      transition: transform 0.2s;
      animation: fadeInUp 0.5s ease-out;
    }

    .stat-card:hover { transform: translateY(-3px); }

    .stat-card .label {
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }

    .stat-card .value {
      font-size: 2.4rem;
      font-weight: 800;
      margin-bottom: 4px;
    }

    .stat-card .sub { font-size: 12px; color: var(--text-muted); }

    .stat-card.blue .value { color: #818cf8; }
    .stat-card.green .value { color: #34d399; }
    .stat-card.yellow .value { color: #fcd34d; }

    /* Tab system */
    .tabs {
      display: flex;
      gap: 4px;
      background: var(--card-bg);
      padding: 4px;
      border-radius: 12px;
      border: 1px solid var(--glass-border);
      margin-bottom: 24px;
      width: fit-content;
    }

    .tab-btn {
      padding: 10px 24px;
      border: none;
      background: none;
      color: var(--text-muted);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border-radius: 9px;
      transition: all 0.2s;
    }

    .tab-btn.active {
      background: linear-gradient(135deg, var(--primary), #818cf8);
      color: white;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }

    /* Tab panels */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeIn 0.3s; }

    /* Cards */
    .card {
      background: var(--card-bg);
      border-radius: 16px;
      padding: 28px;
      border: 1px solid var(--glass-border);
      margin-bottom: 24px;
    }

    .card h3 {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Form */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: 12px 14px;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--glass-border);
      border-radius: 10px;
      font-size: 14px;
      color: white;
      transition: all 0.2s;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    select option { background: var(--card-bg); }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), #818cf8);
      color: white;
      box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
    }

    .btn-danger {
      background: rgba(239, 68, 68, 0.15);
      color: #fca5a5;
      border: 1px solid rgba(239, 68, 68, 0.25);
      padding: 6px 14px;
      font-size: 12px;
    }

    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.25);
    }

    .btn-export {
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .btn-export:hover {
      background: rgba(16, 185, 129, 0.25);
    }

    /* Alert */
    .alert {
      padding: 14px 18px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeIn 0.3s;
    }

    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; }
    .alert-error   { background: rgba(239, 68, 68, 0.1);  border: 1px solid rgba(239, 68, 68, 0.2);  color: #fca5a5; }

    /* Table */
    .table-responsive { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    th, td {
      padding: 13px 16px;
      text-align: left;
      font-size: 14px;
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

    th:first-child { border-top-left-radius: 10px; }
    th:last-child  { border-top-right-radius: 10px; }

    tr:hover { background: rgba(255,255,255,0.025); }

    .badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .badge-admin   { background: rgba(79,70,229,0.2); color: #818cf8; }
    .badge-pegawai { background: rgba(16,185,129,0.2); color: #34d399; }
    .badge-Hadir   { background: rgba(16,185,129,0.2); color: #34d399; }
    .badge-WFH     { background: rgba(56,189,248,0.2); color: #7dd3fc; }
    .badge-Izin    { background: rgba(251,191,36,0.2); color: #fcd34d; }
    .badge-Sakit   { background: rgba(239,68,68,0.2);  color: #fca5a5; }
    .badge-Dinas   { background: rgba(168,85,247,0.2); color: #c084fc; }

    .map-link {
      color: #38bdf8;
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: color 0.2s;
    }

    .map-link:hover { color: #7dd3fc; }

    .empty-state {
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
      font-size: 14px;
    }

    .toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .search-input {
      padding: 9px 14px 9px 38px;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--glass-border);
      border-radius: 8px;
      color: white;
      font-size: 13px;
      width: 240px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 10px center;
    }

    .search-input:focus { outline: none; border-color: var(--primary); }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    @media (max-width: 900px) {
      .sidebar { display: none; }
      .main { margin-left: 0; padding: 20px; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .form-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="icon">📍</div>
    <h2>Presensi GPS</h2>
    <p>Admin Panel</p>
  </div>

  <div class="nav-section">
    <div class="nav-label">Menu</div>
    <button class="nav-item active" onclick="switchTab('presensi', this)">
      📋 Data Presensi <span class="dot"></span>
    </button>
    <button class="nav-item" onclick="switchTab('manajemen', this)">
      👥 Manajemen Akun
    </button>
  </div>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar"><?= strtoupper(substr($adminNama, 0, 1)) ?></div>
      <div>
        <div class="name"><?= htmlspecialchars($adminNama) ?></div>
        <span class="role-badge">Administrator</span>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">🚪 Keluar</a>
  </div>
</nav>

<!-- Main Content -->
<main class="main">
  <div class="topbar">
    <h1>Dashboard Admin</h1>
    <span class="date" id="currentDate"></span>
  </div>

  <?php if (isset($msg)): ?>
  <div class="alert alert-<?= $msg['type'] ?>">
    <?= $msg['type'] === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($msg['text']) ?>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="label">Total Presensi</div>
      <div class="value"><?= $totalPresensi ?></div>
      <div class="sub">Semua waktu</div>
    </div>
    <div class="stat-card green">
      <div class="label">Presensi Hari Ini</div>
      <div class="value"><?= $hariIni ?></div>
      <div class="sub">Tanggal <?= date('d/m/Y') ?></div>
    </div>
    <div class="stat-card yellow">
      <div class="label">Total Pegawai</div>
      <div class="value"><?= $totalPegawai ?></div>
      <div class="sub">Akun aktif</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" id="tab-presensi" onclick="switchTab('presensi', this)">📋 Data Presensi</button>
    <button class="tab-btn" id="tab-manajemen" onclick="switchTab('manajemen', this)">👥 Manajemen Akun</button>
  </div>

  <!-- Tab: Presensi -->
  <div class="tab-panel active" id="panel-presensi">
    <div class="card">
      <div class="toolbar">
        <h3>📋 Riwayat Semua Presensi</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
          <input type="text" class="search-input" id="searchPresensi" placeholder="Cari nama..." onkeyup="filterTable('tabelPresensi', this.value)">
          <a href="dl.php" class="btn btn-export">⬇️ Export Excel</a>
          <button class="btn btn-danger" onclick="clearAll()">🗑️ Hapus Semua</button>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Waktu</th>
              <th>Nama</th>
              <th>Status</th>
              <th>Lokasi</th>
            </tr>
          </thead>
          <tbody id="tabelPresensi">
            <?php if (empty($presensiList)): ?>
            <tr><td colspan="5" class="empty-state">Belum ada data presensi.</td></tr>
            <?php else: $i = 1; foreach ($presensiList as $p): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars($p['waktu']) ?></td>
              <td style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></td>
              <td>
                <?php
                  $badgeClass = 'badge';
                  if (str_contains($p['status'], 'Hadir')) $badgeClass .= ' badge-Hadir';
                  elseif (str_contains($p['status'], 'WFH'))  $badgeClass .= ' badge-WFH';
                  elseif (str_contains($p['status'], 'Izin')) $badgeClass .= ' badge-Izin';
                  elseif (str_contains($p['status'], 'Sakit')) $badgeClass .= ' badge-Sakit';
                  elseif (str_contains($p['status'], 'Dinas')) $badgeClass .= ' badge-Dinas';
                ?>
                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($p['status']) ?></span>
              </td>
              <td>
                <a href="https://www.google.com/maps?q=<?= htmlspecialchars($p['lokasi']) ?>" target="_blank" class="map-link">
                  📍 <?= htmlspecialchars($p['lokasi']) ?>
                </a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab: Manajemen Akun -->
  <div class="tab-panel" id="panel-manajemen">
    <!-- Form Tambah Akun -->
    <div class="card">
      <h3>➕ Tambah Akun Baru</h3>
      <form method="POST" action="admin.php?action=tambah_pegawai">
        <div class="form-grid">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" placeholder="Nama pegawai" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Username login" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Minimal 6 karakter" required>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="pegawai">Pegawai</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">➕ Tambah Akun</button>
      </form>
    </div>

    <!-- Daftar Akun -->
    <div class="card">
      <h3>👥 Daftar Semua Akun</h3>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Username</th>
              <th>Role</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" class="empty-state">Tidak ada akun.</td></tr>
            <?php else: $i = 1; foreach ($users as $u): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td style="font-weight:600;"><?= htmlspecialchars($u['nama']) ?></td>
              <td><code style="background:rgba(255,255,255,0.07);padding:3px 8px;border-radius:5px;font-size:13px;"><?= htmlspecialchars($u['username']) ?></code></td>
              <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars($u['created_at']) ?></td>
              <td>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <a href="admin.php?action=hapus_user&id=<?= $u['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Hapus akun <?= htmlspecialchars($u['username']) ?>?')">
                  🗑️ Hapus
                </a>
                <?php else: ?>
                <span style="font-size:12px;color:#475569;">Akun aktif</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</main>

<script>
  // Tampilkan tanggal hari ini
  const now = new Date();
  document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Tab switching
  function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));

    document.getElementById('panel-' + name).classList.add('active');
    document.getElementById('tab-' + name)?.classList.add('active');
    if (btn) btn.classList.add('active');
  }

  // Filter tabel
  function filterTable(tableId, query) {
    const rows = document.querySelectorAll('#' + tableId + ' tr');
    query = query.toLowerCase();
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(query) ? '' : 'none';
    });
  }

  // Clear all data
  async function clearAll() {
    if (!confirm('Yakin mau MENGHAPUS SEMUA DATA PRESENSI? Tindakan ini tidak bisa dibatalkan!')) return;
    const res = await fetch('api.php?action=clear');
    const result = await res.json();
    if (result.success) {
      location.reload();
    } else {
      alert('Gagal menghapus data.');
    }
  }

  // Check URL hash for tab
  if (window.location.hash === '#manajemen') {
    switchTab('manajemen', document.getElementById('tab-manajemen'));
  }
</script>

</body>
</html>
