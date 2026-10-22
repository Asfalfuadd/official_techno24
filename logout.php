<?php
require_once __DIR__ . '/includes/bootstrap.php';
// Logout lewat POST + CSRF agar tidak bisa dipicu dari tautan eksternal.
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); logout_user(); }
redirect('index.php');
