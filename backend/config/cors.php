<?php
$frontend=env('FRONTEND_URL','http://localhost:5173');
return ['paths'=>['api/*'],'allowed_methods'=>['*'],'allowed_origins'=>[$frontend,str_replace('localhost','127.0.0.1',$frontend)],'allowed_headers'=>['*'],'exposed_headers'=>['Content-Disposition'],'max_age'=>0,'supports_credentials'=>false];