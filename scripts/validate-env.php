<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$checks=[config('app.env')==='production',config('app.debug')===false,strlen(config('app.key',''))>20,config('database.default')==='mysql',config('queue.default')==='database',config('cache.default')==='file',(int)config('queue.connections.database.retry_after')>90,str_starts_with(config('app.url',''),'https://')];
if(in_array(false,$checks,true)){fwrite(STDERR,"Production environment validation failed. Check APP_ENV, APP_DEBUG, APP_KEY, DB_CONNECTION, QUEUE_CONNECTION, CACHE_STORE and APP_URL.\n");exit(1);}
foreach(['bcmath','dom','fileinfo','gd','mbstring','pdo_mysql'] as $extension){if(!extension_loaded($extension)){fwrite(STDERR,"Missing PHP extension: $extension\n");exit(1);}}
echo "Production environment validated.\n";
