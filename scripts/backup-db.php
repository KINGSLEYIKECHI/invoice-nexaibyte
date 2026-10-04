<?php
// Private, consistent InnoDB snapshot. Does not print credentials or use proc_open.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!isset($argv[1]))exit(1);
$pdo=Illuminate\Support\Facades\DB::connection()->getPdo();
if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql')throw new RuntimeException('Backup requires MySQL');
$file=fopen($argv[1],'x');if(!$file)throw new RuntimeException('Backup destination exists or cannot be created');chmod($argv[1],0600);
try {
 $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
 fwrite($file,"SET FOREIGN_KEY_CHECKS=0;\n");
 $tables=$pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
 foreach($tables as $table){$quoted='`'.str_replace('`','``',$table).'`';$ddl=$pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_NUM)[1];fwrite($file,"DROP TABLE IF EXISTS $quoted;\n$ddl;\n");
  $statement=$pdo->query('SELECT * FROM '.$quoted);
  while($row=$statement->fetch(PDO::FETCH_ASSOC)){
   $columns=implode(',',array_map(fn($v)=>'`'.str_replace('`','``',$v).'`',array_keys($row)));
   $values=implode(',',array_map(fn($v)=>$v===null?'NULL':$pdo->quote((string)$v),array_values($row)));
   fwrite($file,"INSERT INTO $quoted ($columns) VALUES ($values);\n");
  }
 }
 fwrite($file,"SET FOREIGN_KEY_CHECKS=1;\n");$pdo->commit();fclose($file);
} catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fclose($file);unlink($argv[1]);throw $e;}
echo "Database snapshot saved privately.\n";
