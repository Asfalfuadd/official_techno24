<?php
// Logo resmi TECHNO 24
$logoType = $logoType ?? 'badge'; // 'badge', 'full', 'icon', or 'orig'
$logoSize = $logoSize ?? 34;
$logoClass = $logoClass ?? 'logo';
$logoSrc = match($logoType) {
    'full' => url('assets/images/logo.png'),
    'icon' => url('assets/images/logo-icon.png'),
    'orig' => url('assets/images/logo.jpg'),
    default => url('assets/images/logo-badge.png'),
};
?>
<img src="<?= e($logoSrc) ?>" alt="TECHNO 24" class="<?= e($logoClass) ?>" width="<?= (int)$logoSize ?>" height="<?= (int)$logoSize ?>">
