<?php
// Local packaged-release smoke helper. Apache in CI remains the routing authority.
$public=realpath($argv[1]??getenv('RELEASE_PUBLIC_PATH'));
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($path,'/backend')){
 $_SERVER['SCRIPT_NAME']='/backend/index.php';$_SERVER['PHP_SELF']='/backend/index.php';$_SERVER['SCRIPT_FILENAME']=$public.'/backend/index.php';
 require $public.'/backend/index.php';return;
}
if(str_contains($path,'..')||str_contains($path,'/.')){http_response_code(404);return;}
if(is_file($public.$path))return false;
header('Content-Type: text/html');readfile($public.'/index.html');
