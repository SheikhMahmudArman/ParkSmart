<?php
$base=__DIR__.'/../backend/parkbackend/';
require $base.'vendor/autoload.php';
$app=require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables=['users','parking_lots','parking_spaces','vehicles','reservations','parking_sessions','payments','finds','feedbacks','employees','personal_access_tokens'];
$sql="-- ParkSmart base schema: reference/manual install into an EMPTY database.\n-- Preferred installation is php artisan migrate --seed. Do not mix methods.\n-- Generated from the original table migrations, without data.\n\n";
foreach($tables as $table) {
 $row=(array)Illuminate\Support\Facades\DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
 $ddl=preg_replace('/ AUTO_INCREMENT=\d+/','',$row['Create Table']);
 // MariaDB represents JSON as LONGTEXT; keep the submitted MySQL schema native JSON.
 if($table==='parking_lots') $ddl=preg_replace('/`features` longtext[^\n]+/', '`features` json DEFAULT NULL,', $ddl);
 $sql.=$ddl.";\n\n";
}
file_put_contents($base.'database/sql/schema.sql',$sql);
echo "Raw base schema exported without data.\n";
