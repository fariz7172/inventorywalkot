<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use Illuminate\Support\Facades\File;

echo "=== STARTING DATABASE PUSH TO SUBDOMAIN ===\n";

// 1. BACKUP LOCAL
$database = config('database.connections.mysql.database');
$username = config('database.connections.mysql.username');
$password = config('database.connections.mysql.password');
$host = config('database.connections.mysql.host');

$backupDir = storage_path('app/backups');
if (!File::exists($backupDir)) File::makeDirectory($backupDir, 0755, true);

$localFile = $backupDir . '/latest_push.sql';
$mysqldump = '"C:\xampp\mysql\bin\mysqldump.exe"'; // Path yang tadi berhasil

echo "1. Backing up local database to $localFile ...\n";
$cmd = "$mysqldump --user=$username --password=$password --host=$host $database > \"$localFile\"";
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) die("❌ Local backup failed!\n");
echo "✅ Local backup successful.\n";

// 2. UPLOAD TO SERVER
$sshHost = 'farizahmad.com';
$sshPort = 65002;
$sshUser = 'u674511048';
$sshPass = '!FarizAhmad123456';
$remoteFile = '/home/u674511048/domains/farizahmad.com/public_html/inventorywalkot/storage/app/backup_latest.sql';

echo "2. Uploading to server via SFTP...\n";
$sftp = new SFTP($sshHost, $sshPort);
if (!$sftp->login($sshUser, $sshPass)) die("❌ SFTP Login Failed!\n");

if ($sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE)) {
    echo "✅ Upload Successful.\n";
} else {
    die("❌ Upload Failed!\n");
}

// 3. IMPORT ON SERVER
echo "3. Importing on remote server...\n";
$ssh = new SSH2($sshHost, $sshPort);
if (!$ssh->login($sshUser, $sshPass)) die("❌ SSH Login Failed!\n");

$dbName = 'u674511048_inventoryjakut';
$dbUser = 'u674511048_inventoryjakut';
$dbPass = '!FarizAhmad123456';

$importCmd = "mysql -u $dbUser -p'$dbPass' $dbName < $remoteFile";
$ssh->exec($importCmd);

echo "✅ Database Import on Server Complete!\n";
echo "=== PUSH COMPLETE ===\n";
