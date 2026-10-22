<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) redirect('login.php');
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'create') {
        $cap = mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 300);
        try {
            $fn = save_media($_FILES['media'] ?? [], 'stories', false);
            if (!$fn && $cap === '') throw new RuntimeException('Isi teks atau pilih foto/video untuk story.');
            $created = now(); $expires = date('Y-m-d H:i:s', time() + 86400); // kedaluwarsa dihitung di server: 24 jam
            $type = $fn ? (in_array(strtolower(pathinfo($fn, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov'], true) ? 'video' : 'image') : null;
            q('INSERT INTO stories (user_id, media_filename, media_type, caption, created_at, expires_at) VALUES (?,?,?,?,?,?)', [$u['id'], $fn, $type, $cap ?: null, $created, $expires]);
            flash('ok', 'Story dipublikasikan selama 24 jam.');
        } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); }
    } elseif ($act === 'delete') {
        $s = q('SELECT * FROM stories WHERE id = ?', [(int)($_POST['id'] ?? 0)])->fetch();
        if ($s && ((int)$s['user_id'] === (int)$u['id'] || is_admin())) {
            q('DELETE FROM stories WHERE id = ?', [$s['id']]); delete_upload('stories', $s['media_filename']);
            audit('story_delete', 'id=' . $s['id']); flash('ok', 'Story dihapus.');
        } else forbidden();
    }
    redirect('pages/stories.php');
}
// Hanya story yang belum kedaluwarsa (dicek di query, bukan di JavaScript)
$rows = q('SELECT s.*, u.name, u.avatar FROM stories s JOIN users u ON u.id = s.user_id WHERE s.expires_at > ? ORDER BY s.created_at DESC', [now()])->fetchAll();
$js = []; $users = [];
foreach ($rows as $r) {
    $js[] = ['id' => (int)$r['id'], 'uid' => (int)$r['user_id'], 'name' => $r['name'], 'caption' => $r['caption'], 'img' => $r['media_filename'] ? url('uploads/stories/' . $r['media_filename']) : null, 'type' => $r['media_type'] ?: 'image',
             'ago' => time_ago($r['created_at']), 'left' => time_left($r['expires_at']), 'canDelete' => $u && (is_admin() || (int)$r['user_id'] === (int)$u['id'])];
    $users[$r['user_id']] = ['name' => $r['name'], 'avatar' => $r['avatar']];
}

$recentPhotos = q('SELECT id, title, filename FROM photos WHERE is_public = 1 ORDER BY created_at DESC LIMIT 6')->fetchAll();
$totalStoriesCount = count($rows);
$activeUsersCount = count($users);

$pageTitle = 'Story 24 jam'; $active = 'stories';
$openUid = (int)($_GET['u'] ?? 0);
$extraScripts = '<script>window.STORIES=' . json_encode($js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';window.CSRF=' . json_encode($u ? csrf_token() : '') . ';'
  . ($openUid ? 'window.addEventListener("load",function(){var b=document.querySelector("[data-story=\'' . $openUid . '\']");if(b)b.click();});' : '') . '</script>';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-play-circle-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Story 24 Jam</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Bagikan momen singkat, kabar harian, dan keseruan aktivitas kelas bersama teman-teman.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $activeUsersCount ?> Teman Berbagi Story</span>
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $totalStoriesCount ?> Story Aktif</span>
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><i class="bi bi-clock-history"></i> Auto-Expire 24 Jam</span>
      </div>
    </div>
    <?php if ($u): ?>
      <div style="flex:none">
        <button type="button" class="btn" style="background:#fff;color:var(--navy);font-weight:600" data-trigger-file="media" data-focus-after="cap"><i class="bi bi-camera-fill"></i> Bagikan Story</button>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="grid g-desktop-split">
  <!-- Kolom Kiri: Story Aktif & Kilas Balik Foto -->
  <div>
    <div class="card">
      <div class="row" style="align-items:center;margin-bottom:14px">
        <h2 style="margin:0;flex:1"><i class="bi bi-broadcast" style="color:var(--blue);margin-right:6px"></i> Story Aktif</h2>
        <?php if ($activeUsersCount > 0): ?>
          <span class="tag tag-blue"><?= $activeUsersCount ?> Akun</span>
        <?php endif; ?>
      </div>
      <?php if (!$users): ?>
        <div class="empty" style="padding:28px 16px">
          <i class="bi bi-camera" style="font-size:32px;margin-bottom:8px"></i>
          <div>Belum ada story aktif dalam 24 jam terakhir.</div>
          <div class="muted" style="font-size:12px;margin-top:4px">Jadilah yang pertama mengunggah story hari ini!</div>
        </div>
      <?php else: ?>
        <div class="stories" style="padding-bottom:12px">
          <?php foreach ($users as $uid => $s): ?>
            <button type="button" class="story-bubble" data-story="<?= (int)$uid ?>">
              <span class="ring"><?= avatar_html($s, 'md') ?></span><br><?= e($s['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="muted" style="margin-top:12px;font-size:12px;display:flex;align-items:center;gap:6px">
          <i class="bi bi-info-circle"></i> Klik foto profil di atas untuk membuka layar penampil story.
        </div>
      <?php endif; ?>
    </div>

    <!-- Kilas Balik Foto Dokumentasi Kelas agar tidak kosong -->
    <?php if ($recentPhotos): ?>
      <div class="card">
        <div class="row" style="align-items:center;margin-bottom:14px">
          <h2 style="margin:0;flex:1"><i class="bi bi-images" style="color:var(--blue);margin-right:6px"></i> Kilas Balik Foto Kelas</h2>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('pages/gallery.php')) ?>">Buka Galeri</a>
        </div>
        <div class="photos">
          <?php foreach ($recentPhotos as $p): ?>
            <a class="photo" href="<?= e(url('pages/photo.php?id=' . (int)$p['id'])) ?>">
              <img loading="lazy" src="<?= e(url('uploads/photos/' . $p['filename'])) ?>" alt="<?= e($p['title']) ?>">
              <div><?= e($p['title']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Kolom Kanan: Form Buat Story & Info Panduan -->
  <div>
    <?php if ($u): ?>
      <div class="card sticky-card" id="story-form">
        <h2><i class="bi bi-camera-fill" style="color:var(--blue);margin-right:6px"></i> Bagikan Story Baru</h2>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <label for="media">Foto / Video Story (opsional — foto HP dikompres · video maks 1 menit)</label>
          <input id="media" type="file" name="media" accept="image/*,video/*" data-upcompress="1536" data-vidmax="60" data-preview="sp" data-previewvideo="sv">
          <img id="sp" alt="Pratinjau" style="display:none;max-height:180px;margin-top:8px;border-radius:10px;object-fit:cover;width:100%">
          <video id="sv" controls preload="metadata" style="display:none;max-height:240px;margin-top:8px;border-radius:10px;width:100%"></video>
          <label for="cap" style="margin-top:10px">Teks / Caption Story</label>
          <input id="cap" name="caption" maxlength="300" placeholder="Tuliskan cerita singkat atau kabar hari ini...">
          <p style="margin-top:14px">
            <button class="btn" type="submit"><i class="bi bi-send"></i> Publikasikan Story (24 Jam)</button>
          </p>
        </form>
      </div>
    <?php else: ?>
      <div class="card sticky-card">
        <h2><i class="bi bi-lock" style="color:var(--warn);margin-right:6px"></i> Buat Story Kelas</h2>
        <p class="muted">Silakan masuk ke akun TECHNO 24 Anda untuk membagikan story hari ini.</p>
        <p><a class="btn" href="<?= e(url('login.php')) ?>"><i class="bi bi-box-arrow-in-right"></i> Login Sekarang</a></p>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2><i class="bi bi-lightbulb" style="color:var(--warn);margin-right:6px"></i> Ketentuan & Tips Story</h2>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-hourglass-split" style="color:var(--blue)"></i>
        <div class="grow">
          <strong>Kedaluwarsa Otomatis</strong>
          <div class="muted">Story otomatis terhapus dari server setelah tepat 24 jam.</div>
        </div>
      </div>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-shield-check" style="color:var(--ok)"></i>
        <div class="grow">
          <strong>Etika & Privasi</strong>
          <div class="muted">Gunakan konten positif yang mendukung kebersamaan dan kekeluargaan kelas.</div>
        </div>
      </div>
      <div class="list-item" style="border:none;padding:6px 0">
        <i class="bi bi-file-earmark-image" style="color:var(--navy)"></i>
        <div class="grow">
          <strong>Format Didukung</strong>
          <div class="muted">Foto JPG/PNG/WebP tampil maksimal 30 detik, atau video MP4/WebM/MOV maksimal 1 menit; atau pesan teks tanpa media.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="storyModal" role="dialog" aria-modal="true" aria-label="Story"><div class="story-view"></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
