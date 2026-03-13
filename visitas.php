<?php
require_once __DIR__ . '/db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_salida'])) {
    $id         = (int)($_POST['registro_id'] ?? 0);
    $horaSalida = trim($_POST['hora_salida'] ?? '');
    if ($id > 0 && preg_match('/^\d{2}:\d{2}$/', $horaSalida)) {
        $stmt = $pdo->prepare("UPDATE registros SET hora_salida = :h WHERE id = :id");
        $stmt->execute([':h' => $horaSalida, ':id' => $id]);
    }
    header('Location: visitas.php?updated=1');
    exit;
}

$registros    = $pdo->query("SELECT * FROM registros ORDER BY creado_en DESC")->fetchAll();
$totalVisitas = $pdo->query("SELECT COUNT(*) FROM registros WHERE tipo='visita'")->fetchColumn();
$totalProv    = $pdo->query("SELECT COUNT(*) FROM registros WHERE tipo='proveedor'")->fetchColumn();
$totalActivos = $pdo->query("SELECT COUNT(*) FROM registros WHERE hora_salida IS NULL")->fetchColumn();
$total        = count($registros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial — La Antigua Cementera</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0d1b2e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Cementera">
<link rel="apple-touch-icon" href="crs_metalico.png">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --azul:       #0d1b2e;
    --azul2:      #112240;
    --azul3:      #1a3356;
    --azul-borde: #1e3a5f;
    --acento:     #2e7dd1;
    --acento2:    #4a9eff;
    --blanco:     #f0f4f8;
    --gris:       #8fa3b8;
    --negro:      #060e18;
    --verde:      #22c55e;
    --rojo:       #ef4444;
    --dorado:     #c9a84c;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    min-height: 100vh;
    font-family: 'Barlow', sans-serif;
    color: var(--blanco);
    position: relative;
    overflow-x: hidden;
  }

  .bg-wrap { position: fixed; inset: 0; z-index: 0; }
  .bg-wrap::before {
    content: '';
    position: absolute; inset: 0;
    background: url('fondo.jpg') center center / cover no-repeat;
    filter: brightness(0.25) saturate(0.3);
  }
  .bg-wrap::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(6,14,24,0.92) 0%, rgba(13,27,46,0.88) 100%);
  }

  .bg-lines {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image: repeating-linear-gradient(
      90deg, transparent, transparent 80px,
      rgba(46,125,209,0.03) 80px, rgba(46,125,209,0.03) 81px
    );
  }

  /* ── HEADER ── */
  .header {
    position: sticky; top: 0; z-index: 100;
    background: rgba(6,14,24,0.92);
    border-bottom: 2px solid var(--dorado);
    backdrop-filter: blur(16px);
    padding: 0 40px; height: 70px;
    display: flex; align-items: center; justify-content: space-between;
  }

  .logo { display: flex; align-items: center; gap: 16px; }

  .logo-escudo {
    width: 44px; height: 44px;
    border: 2px solid var(--dorado); border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; background: rgba(201,168,76,0.08); flex-shrink: 0;
  }
  .logo-escudo img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }

  .logo-texto h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 22px; letter-spacing: 2px; color: var(--blanco); line-height: 1;
  }
  .logo-texto span {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--dorado); display: block; margin-top: 2px;
  }

  .status-dot {
    display: flex; align-items: center; gap: 7px;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--verde);
  }
  .status-dot::before {
    content: ''; width: 8px; height: 8px; border-radius: 50%;
    background: var(--verde); box-shadow: 0 0 8px var(--verde);
    animation: blink 2s ease-in-out infinite;
  }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

  .btn-back {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 13px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase;
    color: var(--negro); background: var(--dorado);
    border: none; padding: 10px 22px; border-radius: 3px;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px; transition: all .25s;
  }
  .btn-back:hover { background: #e0bc5a; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,168,76,.35); }

  /* ── CONTAINER ── */
  .container { position: relative; z-index: 1; max-width: 1100px; margin: 40px auto; padding: 0 24px 60px; }

  .page-eyebrow {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px; letter-spacing: 4px; text-transform: uppercase;
    color: var(--dorado); margin-bottom: 6px;
    display: flex; align-items: center; gap: 10px;
  }
  .page-eyebrow::before { content: ''; width: 24px; height: 1px; background: var(--dorado); }

  .page-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 42px; letter-spacing: 3px; color: var(--blanco); line-height: 1; margin-bottom: 30px;
  }

  /* ── STATS ── */
  .stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 28px; }

  .stat-card {
    background: rgba(13,27,46,0.82);
    border: 1px solid var(--azul-borde);
    border-top: 2px solid var(--dorado);
    border-radius: 4px; padding: 18px 20px; text-align: center;
    backdrop-filter: blur(12px); transition: transform .2s;
  }
  .stat-card:hover { transform: translateY(-3px); }

  .stat-num {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 40px; letter-spacing: 2px; line-height: 1; margin-bottom: 4px;
  }
  .stat-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: var(--gris);
  }
  .stat-card:nth-child(1) .stat-num { color: var(--blanco); }
  .stat-card:nth-child(2) .stat-num { color: var(--acento2); }
  .stat-card:nth-child(3) .stat-num { color: var(--dorado); }
  .stat-card:nth-child(4) .stat-num { color: var(--verde); }

  /* ── FILTERS ── */
  .filters { display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap; align-items: center; }

  .filter-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
    padding: 8px 18px; border-radius: 3px;
    border: 1px solid var(--azul-borde);
    background: rgba(13,27,46,0.7); color: var(--gris);
    cursor: pointer; transition: all .2s;
  }
  .filter-btn.active, .filter-btn:hover {
    border-color: var(--acento); color: var(--acento2);
    background: rgba(46,125,209,0.1);
  }

  .search-box {
    font-family: 'Barlow', sans-serif; font-size: 14px;
    background: rgba(6,14,24,0.7); border: 1px solid var(--azul-borde);
    border-radius: 3px; padding: 9px 16px; color: var(--blanco);
    outline: none; width: 260px; transition: border-color .2s; margin-left: auto;
  }
  .search-box::placeholder { color: rgba(143,163,184,0.4); }
  .search-box:focus { border-color: var(--acento); }

  /* ── EMPTY ── */
  .empty-state { text-align: center; padding: 80px 20px; color: var(--gris); }
  .empty-state .icon { font-size: 48px; margin-bottom: 16px; opacity: .5; }
  .empty-state p { font-family: 'Barlow Condensed', sans-serif; font-size: 16px; letter-spacing: 1px; }

  /* ── CARDS ── */
  .card {
    background: rgba(13,27,46,0.82);
    border: 1px solid var(--azul-borde);
    border-left: 3px solid var(--azul-borde);
    border-radius: 4px; margin-bottom: 14px;
    overflow: hidden; backdrop-filter: blur(12px);
    transition: transform .2s, box-shadow .2s, border-left-color .2s;
    animation: slideIn .3s ease;
  }
  @keyframes slideIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
  .card:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(0,0,0,.4); }
  .card[data-tipo="visita"]    { border-left-color: var(--acento); }
  .card[data-tipo="proveedor"] { border-left-color: var(--dorado); }

  .card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 22px; border-bottom: 1px solid var(--azul-borde);
    background: rgba(6,14,24,0.4);
  }
  .card-header-left { display: flex; align-items: center; gap: 14px; }

  .avatar {
    width: 42px; height: 42px; border-radius: 3px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0; border: 1px solid var(--azul-borde);
  }
  .avatar-visita    { background: rgba(46,125,209,0.12); }
  .avatar-proveedor { background: rgba(201,168,76,0.1); }

  .card-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 18px; font-weight: 600; letter-spacing: .5px;
    color: var(--blanco); margin-bottom: 2px;
  }
  .card-sub { font-size: 12px; color: var(--gris); font-family: 'Barlow', sans-serif; }

  .badge-tipo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    padding: 4px 14px; border-radius: 2px;
  }
  .badge-visita    { background: rgba(46,125,209,0.15); color: var(--acento2); border: 1px solid rgba(46,125,209,0.3); }
  .badge-proveedor { background: rgba(201,168,76,0.12); color: var(--dorado);  border: 1px solid rgba(201,168,76,0.3); }

  .badge-activo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
    background: rgba(34,197,94,0.12); color: var(--verde);
    border: 1px solid rgba(34,197,94,0.25);
    padding: 3px 10px; border-radius: 2px; margin-left: 8px;
  }
  .badge-salida {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
    background: rgba(143,163,184,0.08); color: var(--gris);
    border: 1px solid rgba(143,163,184,0.15);
    padding: 3px 10px; border-radius: 2px; margin-left: 8px;
  }

  /* ── CARD BODY ── */
  .card-body {
    padding: 18px 22px;
    display: grid; grid-template-columns: repeat(auto-fill, minmax(155px,1fr)); gap: 16px;
  }

  .info-item label {
    display: block;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
    color: var(--dorado); margin-bottom: 4px; opacity: .8;
  }
  .info-item span { font-size: 14px; color: var(--blanco); font-weight: 400; }
  .no-data { color: var(--gris) !important; font-style: italic; }

  .ine-thumb {
    width: 80px; height: 52px; object-fit: cover;
    border-radius: 3px; border: 1px solid var(--azul-borde);
    cursor: pointer; transition: transform .2s;
  }
  .ine-thumb:hover { transform: scale(1.08); border-color: var(--acento); }

  /* ── CARD FOOTER ── */
  .card-footer {
    padding: 14px 22px; border-top: 1px solid var(--azul-borde);
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    background: rgba(6,14,24,0.3);
  }

  .salida-form { display: flex; align-items: center; gap: 10px; flex: 1; }

  .salida-form label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--gris); white-space: nowrap;
  }

  .salida-input {
    background: rgba(6,14,24,0.7); border: 1px solid var(--azul-borde);
    border-radius: 3px; padding: 8px 14px;
    color: var(--blanco); font-family: 'Barlow', sans-serif; font-size: 14px;
    outline: none; transition: border-color .2s;
  }
  .salida-input:focus { border-color: var(--verde); }

  .btn-salida {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 13px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    background: var(--verde); border: none; border-radius: 3px;
    padding: 9px 22px; color: #fff; cursor: pointer;
    transition: all .25s; white-space: nowrap;
  }
  .btn-salida:hover { background: #16a34a; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(34,197,94,.3); }

  .hora-salida-display {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 16px;
    background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);
    border-radius: 3px; color: var(--verde);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 14px; letter-spacing: 1px;
  }

  /* ── MODAL INE ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.9); z-index: 1000;
    align-items: center; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal-img { max-width: 90vw; max-height: 85vh; border-radius: 4px; box-shadow: 0 30px 80px rgba(0,0,0,.8); }
  .modal-close {
    position: absolute; top: 20px; right: 24px;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 20px; width: 42px; height: 42px;
    border-radius: 3px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: background .2s;
  }
  .modal-close:hover { background: rgba(255,255,255,0.2); }

  /* ── TOAST ── */
  .toast {
    display: none; position: fixed; bottom: 30px; right: 30px;
    background: var(--verde); color: #fff;
    padding: 13px 24px; border-radius: 3px;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 14px; letter-spacing: 1px; font-weight: 600;
    z-index: 200; box-shadow: 0 8px 24px rgba(34,197,94,.4);
    animation: slideIn .3s ease;
  }

  /* ── RESPONSIVE ── */
  @media(max-width:640px){
    .header { padding: 0 18px; }
    .status-dot { display: none; }
    .container { padding: 0 16px 40px; }
    .stats { grid-template-columns: 1fr 1fr; }
    .card-body { grid-template-columns: 1fr 1fr; }
    .search-box { width: 100%; margin-left: 0; }
    .salida-form { flex-wrap: wrap; }
    .page-title { font-size: 32px; }
  }
</style>
</head>
<body>

<div class="bg-wrap"></div>
<div class="bg-lines"></div>

<header class="header">
  <div class="logo">
    <div class="logo-escudo"><img src="crs_metalico.png" alt="Logo La Antigua Cementera"></div>
    <div class="logo-texto">
      <h1>La Antigua Cementera</h1>
      <span>Historial de Accesos</span>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:20px">
    <div class="status-dot">Sistema activo</div>
    <a href="index.html" class="btn-back">← Nuevo Registro</a>
  </div>
</header>

<div class="container">
  <div class="page-eyebrow">Registros del sistema</div>
  <h2 class="page-title">Historial de Visitas</h2>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total registros</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalVisitas ?></div>
      <div class="stat-label">Visitas</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalProv ?></div>
      <div class="stat-label">Proveedores</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalActivos ?></div>
      <div class="stat-label">Dentro ahora</div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="filters">
    <button class="filter-btn active" onclick="filtrar('todos',this)">Todos (<?= $total ?>)</button>
    <button class="filter-btn" onclick="filtrar('visita',this)">Visitas (<?= $totalVisitas ?>)</button>
    <button class="filter-btn" onclick="filtrar('proveedor',this)">Proveedores (<?= $totalProv ?>)</button>
    <button class="filter-btn" onclick="filtrar('activos',this)">Dentro (<?= $totalActivos ?>)</button>
    <input type="text" class="search-box" id="searchInput"
           placeholder="Buscar nombre, casa, empresa..." oninput="aplicarFiltros()">
  </div>

  <!-- Lista -->
  <div id="listaRegistros">
    <?php if (empty($registros)): ?>
      <div class="empty-state">
        <div class="icon">📭</div>
        <p>No hay registros aún.<br>Crea el primero desde el formulario.</p>
      </div>
    <?php else: ?>
      <?php foreach ($registros as $reg): ?>
      <?php
        $esProveedor = $reg['tipo'] === 'proveedor';
        $tieneSalida = !empty($reg['hora_salida']);
        $nombre      = htmlspecialchars($reg['nombre'] ?? '');
        $empresa     = htmlspecialchars($reg['empresa'] ?? '');
        $fechaFmt    = $reg['fecha'] ? date('d/m/Y', strtotime($reg['fecha'])) : '—';
        $horaIn      = $reg['hora_ingreso'] ? substr($reg['hora_ingreso'], 0, 5) : '—';
        $horaSal     = $tieneSalida ? substr($reg['hora_salida'], 0, 5) : '';
      ?>
      <div class="card"
           data-tipo="<?= htmlspecialchars($reg['tipo']) ?>"
           data-activo="<?= $tieneSalida ? 'no' : 'si' ?>"
           data-search="<?= strtolower(htmlspecialchars($nombre.' '.$empresa.' '.($reg['casa_visita']??'').' '.($reg['residente']??''))) ?>">

        <div class="card-header">
          <div class="card-header-left">
            <div class="avatar avatar-<?= htmlspecialchars($reg['tipo']) ?>">
              <?= $esProveedor ? '🔧' : '👤' ?>
            </div>
            <div>
              <div class="card-name">
                <?= $nombre ?>
                <?php if ($tieneSalida): ?>
                  <span class="badge-salida">Salida: <?= $horaSal ?></span>
                <?php else: ?>
                  <span class="badge-activo">● Dentro</span>
                <?php endif; ?>
              </div>
              <div class="card-sub">
                <?= $esProveedor && $empresa ? $empresa.' · ' : '' ?>
                Registro #<?= $reg['id'] ?> · <?= $fechaFmt ?>
              </div>
            </div>
          </div>
          <span class="badge-tipo badge-<?= htmlspecialchars($reg['tipo']) ?>">
            <?= $esProveedor ? 'Proveedor' : 'Visita' ?>
          </span>
        </div>

        <div class="card-body">
          <div class="info-item">
            <label>Fecha</label>
            <span><?= $fechaFmt ?></span>
          </div>
          <div class="info-item">
            <label>Hora ingreso</label>
            <span><?= $horaIn ?></span>
          </div>
          <div class="info-item">
            <label>Casa</label>
            <span><?= $reg['casa_visita'] ? htmlspecialchars($reg['casa_visita']) : '<span class="no-data">—</span>' ?></span>
          </div>
          <div class="info-item">
            <label>Residente</label>
            <span><?= $reg['residente'] ? htmlspecialchars($reg['residente']) : '<span class="no-data">—</span>' ?></span>
          </div>
          <div class="info-item">
            <label>Motivo</label>
            <span><?= $reg['motivo'] ? htmlspecialchars($reg['motivo']) : '<span class="no-data">—</span>' ?></span>
          </div>
          <div class="info-item">
            <label>Vehículo</label>
            <?php if ($reg['vehiculo'] === 'si'): ?>
              <span><?= htmlspecialchars($reg['placas']??'') ?> · <?= htmlspecialchars($reg['modelo_vehiculo']??'') ?></span>
            <?php else: ?>
              <span class="no-data">Sin vehículo</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($reg['ine_foto'])): ?>
          <div class="info-item">
            <label>INE</label>
            <img src="<?= htmlspecialchars($reg['ine_foto']) ?>"
                 class="ine-thumb" alt="INE"
                 onclick="verINE('<?= htmlspecialchars($reg['ine_foto']) ?>')">
          </div>
          <?php endif; ?>
        </div>

        <div class="card-footer">
          <?php if (!$tieneSalida): ?>
          <form class="salida-form" method="POST" action="visitas.php">
            <input type="hidden" name="actualizar_salida" value="1">
            <input type="hidden" name="registro_id" value="<?= (int)$reg['id'] ?>">
            <label>Hora de salida</label>
            <input type="time" name="hora_salida" class="salida-input" required>
            <button type="submit" class="btn-salida">Registrar Salida</button>
          </form>
          <?php else: ?>
          <div class="hora-salida-display">
            ✓ Salida registrada: <strong><?= $horaSal ?></strong>
          </div>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal INE -->
<div class="modal-overlay" id="ineModal" onclick="cerrarModal()">
  <button class="modal-close" onclick="cerrarModal()">✕</button>
  <img src="" id="ineModalImg" class="modal-img" alt="INE" onclick="event.stopPropagation()">
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="toast" id="toast">✓ Hora de salida registrada</div>
<script>
  document.getElementById('toast').style.display = 'block';
  setTimeout(() => document.getElementById('toast').style.display = 'none', 3500);
</script>
<?php endif; ?>

<script>
  function verINE(src) {
    document.getElementById('ineModalImg').src = src;
    document.getElementById('ineModal').classList.add('open');
  }
  function cerrarModal() {
    document.getElementById('ineModal').classList.remove('open');
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

  let filtroActual = 'todos';

  function filtrar(tipo, btn) {
    filtroActual = tipo;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    aplicarFiltros();
  }

  function aplicarFiltros() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('.card').forEach(card => {
      const tipoMatch =
        filtroActual === 'todos' ||
        (filtroActual === 'activos' && card.dataset.activo === 'si') ||
        card.dataset.tipo === filtroActual;
      const searchMatch = !q || card.dataset.search.includes(q);
      card.style.display = (tipoMatch && searchMatch) ? '' : 'none';
    });
  }

  // Registrar Service Worker (PWA)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js')
        .catch(err => console.warn('SW no registrado:', err));
    });
  }
</script>

</body>
</html>