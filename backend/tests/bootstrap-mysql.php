<?php
require_once __DIR__.'/../vendor/autoload.php';
// Refuse to reset an arbitrary database before RefreshDatabase ever runs.
$database=$_ENV['DB_DATABASE']??getenv('DB_DATABASE');
$driver=$_ENV['DB_CONNECTION']??getenv('DB_CONNECTION');
if($driver!=='mysql'||!is_string($database)||!preg_match('/^invoice_ci[a-zA-Z0-9_]*$/',$database)){
 throw new RuntimeException('MySQL tests require a dedicated database whose name begins with invoice_ci.');
}
