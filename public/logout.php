<?php
require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    redirect('/');
}

logout();
redirect('/');
