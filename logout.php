<?php
require_once 'config/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php?logged_out=1');
exit;


