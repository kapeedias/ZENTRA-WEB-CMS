<?php
require_once __DIR__ . '/db.php';

// GoDaddy → manual credentials
$pdo = Database::getInstance();

// Azure → environment variables
// $pdo = Database::getInstance();