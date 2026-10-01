<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Presensi Web (Fleksibel GPS) - Pro Version</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-hover: #4338ca;
      --bg: #0f172a;
      --card-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --success: #10b981;
      --error: #ef4444;
      --glass-border: rgba(255, 255, 255, 0.1);
    }
    
    * { 
      box-sizing: border-box; 
      font-family: 'Outfit', sans-serif; 
      margin: 0; 
      padding: 0; 
    }

    body { 
      background: var(--bg);
      background-image: radial-gradient(circle at 15% 50%, rgba(79, 70, 229, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(16, 185, 129, 0.15), transparent 25%);
      color: var(--text-main); 
      padding: 40px 20px; 
      min-height: 100vh;
    }

    .container { 
      max-width: 900px; 
      margin: 0 auto; 
    }

    h1 { 
      text-align: center; 
      margin-bottom: 30px; 
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(to right, #818cf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: fadeInDown 0.8s ease-out;
    }

    .card { 
      background: var(--card-bg); 
      border-radius: 16px; 
      padding: 30px; 
      box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
      margin-bottom: 30px;
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(10px);
      animation: fadeInUp 0.8s ease-out;
    }

    .info-box { 
      background: rgba(79, 70, 229, 0.1); 
      border-left: 4px solid var(--primary); 
      padding: 16px; 
      font-size: 15px; 
      margin-bottom: 25px; 
      border-radius: 8px; 
      color: #e0e7ff; 
      line-height: 1.6;
    }

    .form-group { margin-bottom: 20px; }
    
    label { 
      display: block; 
      margin-bottom: 8px; 
      font-weight: 600; 
      color: var(--text-muted); 
      font-size: 14px;
    }

    input, select { 
      width: 100%; 
      padding: 14px 16px; 
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--glass-border); 
      border-radius: 10px; 
      font-size: 16px; 
      color: white;
      transition: all 0.3s ease;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    select option { background: var(--card-bg); color: white; }

    button { 
      background: linear-gradient(135deg, var(--primary), #6366f1);
      color: white; 
      padding: 16px; 
      border: none; 
      border-radius: 10px; 
      cursor: pointer; 
      font-size: 16px; 
      width: 100%; 
      font-weight: 700; 
      letter-spacing: 1px;
      transition: all 0.3s ease; 
      text-transform: uppercase;
      box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
    }

    button:hover { 
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
    }

    button:active {
      transform: translateY(1px);
    }

    button:disabled { 
      background: #475569; 
      cursor: not-allowed; 
      box-shadow: none;
      transform: none;
    }

    .status-msg { 
      margin-top: 20px; 
      padding: 16px; 
      border-radius: 10px; 
      font-size: 15px; 
      text-align: center; 
      display: none;
      font-weight: 600;
      animation: fadeIn 0.5s;
    }
    
    .success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
    .error { background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); }

    .table-responsive {
      overflow-x: auto;
    }

    table { 
      width: 100%; 
      border-collapse: separate; 
      border-spacing: 0;
      margin-top: 15px; 
    }

    th, td { 
      padding: 16px; 
      text-align: left; 
      font-size: 15px; 
      border-bottom: 1px solid var(--glass-border);
    }

    th { 
      background: rgba(15, 23, 42, 0.6);
      color: var(--text-muted); 
      font-weight: 600;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 1px;
    }
    
    th:first-child { border-top-left-radius: 10px; }
    th:last-child { border-top-right-radius: 10px; }

    tr { transition: background 0.2s; }
    tr:hover { background: rgba(255, 255, 255, 0.03); }

    .clear-btn { 
      background: linear-gradient(135deg, #ef4444, #f43f5e);
      margin-top: 25px; 
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    }
    
    .clear-btn:hover {
      box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
    }

    a.map-link {
      color: #38bdf8;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: color 0.2s;
    }

    a.map-link:hover { color: #7dd3fc; }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      background: rgba(255,255,255,0.1);
    }

    .badge-Hadir { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .badge-WFH { background: rgba(56, 189, 248, 0.2); color: #7dd3fc; }
    .badge-Izin { background: rgba(251, 191, 36, 0.2); color: #fcd34d; }
    
  </style>
</head>
<body>

<div class="container">
  <h1>Presensi Online Pro</h1>

  <div class="card">
    <div class="info-box">
      <strong>Info Target:</strong> Lat <span id="targetLat"></span>, Lng <span id="targetLng"></span><br>
      <span style="opacity: 0.8; font-size: 13px; display: block; margin-top: 5px;">Presensi dapat dilakukan dari mana saja, titik lokasi kamu akan tercatat otomatis ke dalam Database Server.</span>
    </div>

    <form id="geoPresensiForm">
      <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" placeholder="Masukkan nama kamu" required>
      </div>

      <div class="form-group">
        <label for="status">Status Kehadiran</label>
        <select id="status">
          <option value="Hadir">Hadir</option>
          <option value="WFH / Remote">WFH / Remote</option>
          <option value="Dinas Luar">Dinas Luar</option>
          <option value="Izin">Izin</option>
          <option value="Sakit">Sakit</option>
        </select>
      </div>

      <button type="button" id="btnAbsen">Absen Sekarang</button>
    </form>

    <div id="statusMsg" class="status-msg"></div>
  </div>

  <div class="card">
    <h2 style="margin-bottom: 20px; font-weight: 600; font-size: 1.5rem;">Riwayat Presensi GPS</h2>
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
        <tbody id="tabelPresensi"></tbody>
      </table>
    </div>
    <button class="clear-btn" id="resetBtn">Kosongkan Database</button>
  </div>
</div>

<script>
  const TARGET_LAT = -6.370528842587126;
  const TARGET_LNG = 106.74547088215962;

  document.getElementById('targetLat').textContent = TARGET_LAT;
  document.getElementById('targetLng').textContent = TARGET_LNG;

  const btnAbsen = document.getElementById('btnAbsen');
  const statusMsg = document.getElementById('statusMsg');
  const tabel = document.getElementById('tabelPresensi');
  const resetBtn = document.getElementById('resetBtn');

  function showMessage(text, isError = false) {
    statusMsg.style.display = 'block';
    statusMsg.className = 'status-msg ' + (isError ? 'error' : 'success');
    statusMsg.innerHTML = text;
  }

  function getBadgeClass(status) {
    if(status.includes('Hadir')) return 'badge-Hadir';
    if(status.includes('WFH')) return 'badge-WFH';
    if(status.includes('Izin') || status.includes('Sakit')) return 'badge-Izin';
    return '';
  }

  // Load Data dari Database
  async function loadData() {
    try {
      const res = await fetch('api.php?action=get');
      const data = await res.json();
      
      tabel.innerHTML = '';
      
      if (data.length === 0) {
        tabel.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 30px; color: #64748b;">Belum ada data presensi di Database.</td></tr>';
        return;
      }

      data.forEach((item, index) => {
        const googleMapsUrl = `https://www.google.com/maps?q=${item.lokasi}`;
        const row = `
          <tr>
            <td>${index + 1}</td>
            <td style="color: #94a3b8; font-size: 13px;">${item.waktu}</td>
            <td style="font-weight: 600;">${item.nama}</td>
            <td><span class="badge ${getBadgeClass(item.status)}">${item.status}</span></td>
            <td>
              <a href="${googleMapsUrl}" target="_blank" class="map-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Buka Peta
              </a>
            </td>
          </tr>
        `;
        tabel.innerHTML += row;
      });
    } catch (err) {
      console.error(err);
      tabel.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#ef4444;">Gagal memuat data dari server.</td></tr>';
    }
  }

  btnAbsen.addEventListener('click', () => {
    const nama = document.getElementById('nama').value.trim();
    if (!nama) {
      showMessage('Nama wajib diisi!', true);
      return;
    }

    if (!navigator.geolocation) {
      showMessage('Browser kamu gak mendukung Geolocation API.', true);
      return;
    }

    btnAbsen.disabled = true;
    btnAbsen.textContent = '📍 Mendapatkan lokasi...';
    statusMsg.style.display = 'none';

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const userLat = position.coords.latitude.toFixed(5);
        const userLng = position.coords.longitude.toFixed(5);
        const status = document.getElementById('status').value;
        const koordinatStr = `${userLat}, ${userLng}`;
        
        btnAbsen.textContent = '💾 Menyimpan ke Database...';

        try {
          const res = await fetch('api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nama, status, lokasi: koordinatStr })
          });
          const result = await res.json();
          
          if(result.success) {
            showMessage(`Presensi Berhasil Disimpan ke Database! Lokasi: (${koordinatStr})`);
            document.getElementById('nama').value = '';
            loadData();
          } else {
            showMessage(`Gagal menyimpan: ${result.message}`, true);
          }
        } catch(err) {
          showMessage('Terjadi kesalahan saat menghubungi server.', true);
        }

        btnAbsen.disabled = false;
        btnAbsen.textContent = 'Absen Sekarang';
      },
      (error) => {
        btnAbsen.disabled = false;
        btnAbsen.textContent = 'Absen Sekarang';
        let errMsg = 'Gagal mengambil lokasi.';
        if (error.code === error.PERMISSION_DENIED) {
          errMsg = 'Izin lokasi ditolak! Harap izinkan akses lokasi di browser.';
        }
        showMessage(errMsg, true);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });

  resetBtn.addEventListener('click', async () => {
    if (confirm('Yakin mau MENGHAPUS SEMUA DATA dari Database?')) {
      const res = await fetch('api.php?action=clear');
      const result = await res.json();
      if(result.success) {
        loadData();
        statusMsg.style.display = 'none';
      } else {
        alert('Gagal menghapus data.');
      }
    }
  });

  // Load awal
  loadData();
</script>

</body>
</html>
