<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user(); // jadwal boleh dilihat tanpa login
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!is_admin()) forbidden(); // hanya admin boleh mengubah jadwal (dicek di server)
    $act = $_POST['action'] ?? '';
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        q('DELETE FROM schedules WHERE id = ?', [$id]); audit('schedule_delete', "id=$id");
        notify_all('Perubahan jadwal kuliah', 'Sebuah jadwal dihapus.', 'pages/schedules.php', (int)$u['id']);
        flash('ok', 'Jadwal dihapus.'); redirect('pages/schedules.php');
    }
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $f = [
          'course_name' => trim((string)($_POST['course_name'] ?? '')), 'course_code' => trim((string)($_POST['course_code'] ?? '')),
          'lecturer' => trim((string)($_POST['lecturer'] ?? '')), 'day' => (int)($_POST['day_of_week'] ?? 0),
          'start' => (string)($_POST['start_time'] ?? ''), 'end' => (string)($_POST['end_time'] ?? ''),
          'room' => trim((string)($_POST['room'] ?? '')), 'semester' => trim((string)($_POST['semester'] ?? '')), 'notes' => trim((string)($_POST['notes'] ?? '')),
        ];
        $err = null;
        if ($f['course_name'] === '' || mb_strlen($f['course_name']) > 150) $err = 'Nama mata kuliah wajib diisi (maks. 150 karakter).';
        elseif ($f['day'] < 1 || $f['day'] > 7) $err = 'Hari tidak valid.';
        elseif (!preg_match('/^\d{2}:\d{2}$/', $f['start']) || !preg_match('/^\d{2}:\d{2}$/', $f['end'])) $err = 'Format jam tidak valid.';
        elseif ($f['start'] >= $f['end']) $err = 'Jam selesai harus setelah jam mulai.';
        if (!$err && empty($_POST['ignore_conflict'])) {
            $c = q('SELECT course_name FROM schedules WHERE day_of_week = ? AND id <> ? AND start_time < ? AND end_time > ?', [$f['day'], $id, $f['end'] . ':00', $f['start'] . ':00'])->fetch();
            if ($c) $err = 'Bertabrakan dengan "' . $c['course_name'] . '". Centang "Tetap simpan" jika memang disengaja.';
        }
        if ($err) { flash('err', $err); $edit = array_merge(['id' => $id], $_POST); }
        else {
            $vals = [$f['course_name'], $f['course_code'] ?: null, $f['lecturer'] ?: null, $f['day'], $f['start'], $f['end'], $f['room'] ?: null, $f['semester'] ?: null, $f['notes'] ?: null];
            if ($id) { q('UPDATE schedules SET course_name=?, course_code=?, lecturer=?, day_of_week=?, start_time=?, end_time=?, room=?, semester=?, notes=? WHERE id=?', array_merge($vals, [$id])); }
            else { q('INSERT INTO schedules (course_name, course_code, lecturer, day_of_week, start_time, end_time, room, semester, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$u['id']])); }
            audit($id ? 'schedule_update' : 'schedule_create', $f['course_name']);
            notify_all('Perubahan jadwal kuliah', $f['course_name'] . ' - ' . HARI[$f['day']] . ' ' . $f['start'], 'pages/schedules.php', (int)$u['id']);
            flash('ok', 'Jadwal disimpan.'); redirect('pages/schedules.php');
        }
    }
}
if (!$edit && is_admin() && isset($_GET['edit'])) {
    $edit = q('SELECT *, day_of_week, TIME_FORMAT(start_time,"%H:%i") AS start_time, TIME_FORMAT(end_time,"%H:%i") AS end_time FROM schedules WHERE id = ?', [(int)$_GET['edit']])->fetch() ?: null;
}
$day = (int)($_GET['day'] ?? 0);
$sql = 'SELECT * FROM schedules' . ($day >= 1 && $day <= 7 ? ' WHERE day_of_week = ' . $day : '') . ' ORDER BY day_of_week, start_time';
$rows = q($sql)->fetchAll();
$byDay = [];
foreach ($rows as $r) $byDay[(int)$r['day_of_week']][] = $r;

$todayDOW = today_dow();
$todaySched = q('SELECT * FROM schedules WHERE day_of_week = ? ORDER BY start_time', [$todayDOW])->fetchAll();
$totalSchedules = (int)q('SELECT COUNT(*) FROM schedules')->fetchColumn();

$pageTitle = 'Jadwal kuliah'; $active = 'schedules';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-calendar-week"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Jadwal Kuliah Mingguan</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Jadwal resmi perkuliahan Kelas A. Pantau jam mulai, ruangan, dan dosen pengampu.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Hari ini: <strong><?= e(HARI[$todayDOW]) ?></strong></span>
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $totalSchedules ?> Total Mata Kuliah</span>
        <?php if ($day >= 1 && $day <= 7): ?>
          <span class="tag" style="background:rgba(255,255,255,0.3);color:#fff">Filter: <?= e(HARI[$day]) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if (is_admin()): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#form-jadwal"><i class="bi bi-plus-circle"></i> Tambah Jadwal</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap">
  <a class="btn btn-sm <?= $day ? 'btn-ghost' : '' ?>" style="flex:none" href="?">Semua Hari</a>
  <?php foreach (HARI as $n => $h): ?>
    <a class="btn btn-sm <?= $day === $n ? '' : 'btn-ghost' ?>" style="flex:none" href="?day=<?= $n ?>">
      <?= e($h) ?><?= $n === $todayDOW ? ' (Hari Ini)' : '' ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="grid g-desktop-split">
  <!-- Kolom Kiri: Jadwal per Hari -->
  <div>
    <?php if (!$rows): ?>
      <div class="card empty"><i class="bi bi-calendar-x"></i>Belum ada jadwal kuliah<?= $day ? ' untuk hari ' . e(HARI[$day]) : '' ?>.<?= is_admin() ? ' Tambahkan jadwal lewat formulir di samping.' : ' Admin akan menambahkannya segera.' ?></div>
    <?php else: ?>
      <?php foreach ($byDay as $d => $list): ?>
        <div class="card" <?= $d === $todayDOW ? 'style="border-left:4px solid var(--blue);box-shadow:0 4px 12px rgba(37,99,235,0.12)"' : '' ?>>
          <div class="row" style="align-items:center;margin-bottom:12px">
            <h2 style="margin:0;flex:1"><i class="bi bi-calendar-day" style="color:var(--blue);margin-right:6px"></i> <?= e(HARI[$d]) ?></h2>
            <?php if ($d === $todayDOW): ?><span class="tag tag-blue" style="flex:none">Hari ini</span><?php endif; ?>
            <span class="muted" style="font-size:12px"><?= count($list) ?> Matkul</span>
          </div>
          <?php foreach ($list as $s): ?>
            <div class="list-item">
              <div style="min-width:92px;font-size:13px;flex:none">
                <div style="font-weight:700;color:var(--ink)"><?= e(substr($s['start_time'],0,5)) ?></div>
                <div class="muted" style="font-size:11px">s/d <?= e(substr($s['end_time'],0,5)) ?></div>
              </div>
              <div class="grow">
                <strong><?= e($s['course_name']) ?></strong> <?= $s['course_code'] ? '<span class="muted">(' . e($s['course_code']) . ')</span>' : '' ?>
                <div class="muted"><?= e($s['lecturer'] ?: 'Dosen belum diisi') ?> · Ruang <?= e($s['room'] ?: '-') ?><?= $s['semester'] ? ' · Sem. ' . e($s['semester']) : '' ?></div>
                <?php if ($s['notes']): ?><div class="muted" style="margin-top:2px;font-style:italic"><i class="bi bi-info-circle"></i> <?= e($s['notes']) ?></div><?php endif; ?>
              </div>
              <?php if (is_admin()): ?>
                <div class="row" style="flex:none;flex-wrap:nowrap;gap:4px">
                  <a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$s['id'] ?>#form-jadwal">Edit</a>
                  <form method="post" data-confirm="Hapus jadwal ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Kolom Kanan: Kuliah Hari Ini & Info Akademik / Admin Form -->
  <div>
    <!-- Card Kuliah Hari Ini -->
    <div class="card">
      <div class="row" style="align-items:center;margin-bottom:12px">
        <h2 style="margin:0;flex:1"><i class="bi bi-clock-history" style="color:var(--blue);margin-right:6px"></i> Kuliah Hari Ini</h2>
        <span class="tag tag-blue"><?= e(HARI[$todayDOW]) ?></span>
      </div>
      <?php if (!$todaySched): ?>
        <div class="empty" style="padding:20px 10px"><i class="bi bi-cup-hot"></i>Tidak ada perkuliahan hari ini. Manfaatkan waktu untuk belajar mandiri atau tugas!</div>
      <?php else: ?>
        <?php foreach ($todaySched as $ts): ?>
          <div class="list-item">
            <i class="bi bi-book" style="color:var(--blue)"></i>
            <div class="grow">
              <strong><?= e($ts['course_name']) ?></strong>
              <div class="muted"><?= e(substr($ts['start_time'],0,5)) ?> - <?= e(substr($ts['end_time'],0,5)) ?> · Ruang <?= e($ts['room'] ?: '-') ?></div>
              <?php if ($ts['lecturer']): ?><div class="muted" style="font-size:11px"><?= e($ts['lecturer']) ?></div><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (is_admin()): $v = fn($k, $d = '') => e($edit[$k] ?? $d); ?>
      <!-- Form Admin Tambah/Edit Jadwal (Sticky) -->
      <div class="card sticky-card" id="form-jadwal">
        <h2><i class="bi bi-pencil-square" style="color:var(--blue);margin-right:6px"></i> <?= $edit ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <label>Mata Kuliah</label><input name="course_name" required maxlength="150" placeholder="Contoh: Pemrograman Web Lanjut" value="<?= $v('course_name') ?>">
          <div class="row">
            <div><label>Kode (opsional)</label><input name="course_code" maxlength="30" placeholder="IF2024" value="<?= $v('course_code') ?>"></div>
            <div><label>Ruangan</label><input name="room" maxlength="60" placeholder="Lab Komputer 3" value="<?= $v('room') ?>"></div>
          </div>
          <label>Dosen Pengampu</label><input name="lecturer" maxlength="120" placeholder="Nama dosen beserta gelar" value="<?= $v('lecturer') ?>">
          <div class="row">
            <div><label>Hari</label><select name="day_of_week"><?php foreach (HARI as $n => $h): ?><option value="<?= $n ?>" <?= (int)($edit['day_of_week'] ?? 1) === $n ? 'selected' : '' ?>><?= e($h) ?></option><?php endforeach; ?></select></div>
            <div><label>Semester</label><input name="semester" maxlength="30" placeholder="4" value="<?= $v('semester') ?>"></div>
          </div>
          <div class="row">
            <div><label>Jam Mulai</label><input type="time" name="start_time" required value="<?= $v('start_time') ?>"></div>
            <div><label>Jam Selesai</label><input type="time" name="end_time" required value="<?= $v('end_time') ?>"></div>
          </div>
          <label>Catatan Tambahan (opsional)</label><input name="notes" maxlength="255" placeholder="Contoh: Bawa laptop & modul praktikum" value="<?= $v('notes') ?>">
          <label style="margin-top:8px"><input type="checkbox" name="ignore_conflict" value="1"> Tetap simpan walau bertabrakan waktu</label>
          <p style="margin-top:14px"><button class="btn" type="submit"><i class="bi bi-check-lg"></i> Simpan Jadwal</button> <?php if ($edit): ?><a class="btn btn-ghost" href="?">Batal</a><?php endif; ?></p>
        </form>
      </div>
    <?php endif; ?>

    <!-- Panduan & Info Perkuliahan -->
    <div class="card">
      <h2><i class="bi bi-info-circle" style="color:var(--blue);margin-right:6px"></i> Informasi Perkuliahan</h2>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-alarm" style="color:var(--blue)"></i>
        <div class="grow">
          <strong>Toleransi Keterlambatan</strong>
          <div class="muted">Maksimal 15 menit setelah jam perkuliahan dimulai.</div>
        </div>
      </div>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-person-check" style="color:var(--ok)"></i>
        <div class="grow">
          <strong>Syarat Kehadiran UAS</strong>
          <div class="muted">Minimal 75% kehadiran per mata kuliah untuk dapat mengikuti ujian akhir.</div>
        </div>
      </div>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-laptop" style="color:var(--navy)"></i>
        <div class="grow">
          <strong>Praktikum Lab</strong>
          <div class="muted">Wajib memakai pakaian rapi/berkerah dan sepatu tertutup di seluruh lab kampus.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
