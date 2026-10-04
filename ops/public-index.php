<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$backend=dirname(__DIR__,2).'/backend';
if(file_exists($maintenance=$backend.'/storage/framework/maintenance.php'))require $maintenance;
require $backend.'/vendor/autoload.php';
$app=require_once $backend.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
