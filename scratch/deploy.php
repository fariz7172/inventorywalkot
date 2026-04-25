<?php
require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;

$host     = '145.79.14.233';
$port     = 65002;
$user     = 'u674511048';
$password = '!FarizAhmad123456';
$appPath  = '/home/u674511048/domains/farizahmad.com/public_html/inventorywalkot';
$repo     = 'https://github.com/fariz7172/inventorywalkot.git';
$branch   = 'farizahmad.github.io';

echo "=== INVENTORY DEPLOY SCRIPT ===\n";
echo "Connecting to $host:$port ...\n";

$ssh = new SSH2($host, $port);
if (!$ssh->login($user, $password)) {
    die("❌ Login Failed!\n");
}
echo "✅ Login Successful!\n\n";

// Step 1: Check PHP version
echo "--- PHP Version ---\n";
echo $ssh->exec('php -v | head -1') . "\n";

// Step 2: Check if app directory exists
echo "--- Checking app directory ---\n";
$check = $ssh->exec("[ -d '$appPath/.git' ] && echo 'GIT_EXISTS' || echo 'NOT_EXISTS'");
echo "Status: " . trim($check) . "\n\n";

if (trim($check) === 'NOT_EXISTS') {
    echo "--- Cloning repository ---\n";
    $cloneCmd = "cd /home/u674511048/domains/farizahmad.com/public_html && git clone -b $branch $repo inventorywalkot 2>&1";
    echo $ssh->exec($cloneCmd);
} else {
    echo "--- Git Pull ---\n";
    $pullCmd = "cd $appPath && git fetch origin && git reset --hard origin/$branch 2>&1";
    echo $ssh->exec($pullCmd);
}

echo "\n--- Checking Composer ---\n";
$composerCheck = trim($ssh->exec('which composer || which composer2 || echo NOT_FOUND'));
echo "Composer path: $composerCheck\n";

if ($composerCheck === 'NOT_FOUND') {
    echo "Installing Composer...\n";
    echo $ssh->exec("cd $appPath && curl -sS https://getcomposer.org/installer | php 2>&1");
    $composerBin = "php $appPath/composer.phar";
} else {
    $composerBin = $composerCheck;
}

echo "\n--- Composer Install ---\n";
$composerOut = $ssh->exec("cd $appPath && $composerBin install --no-dev --optimize-autoloader --no-interaction 2>&1");
echo $composerOut . "\n";

echo "--- .env Check ---\n";
$envExists = trim($ssh->exec("[ -f '$appPath/.env' ] && echo 'EXISTS' || echo 'NOT_EXISTS'"));
echo ".env: $envExists\n";

if ($envExists === 'NOT_EXISTS') {
    echo ".env NOT found. Please create it manually with your database credentials.\n";
    echo "Use: nano $appPath/.env\n";
} else {
    echo ".env already exists.\n";
}

echo "\n--- Storage Link ---\n";
echo $ssh->exec("cd $appPath && php artisan storage:link 2>&1") . "\n";

echo "--- Key Generate (if needed) ---\n";
echo $ssh->exec("cd $appPath && php artisan key:generate --no-interaction 2>&1") . "\n";

echo "--- Cache Clear ---\n";
echo $ssh->exec("cd $appPath && php artisan config:clear && php artisan cache:clear && php artisan view:clear 2>&1") . "\n";

echo "--- Permissions ---\n";
echo $ssh->exec("chmod -R 775 $appPath/storage $appPath/bootstrap/cache && echo 'Permissions set OK'") . "\n";

echo "--- .htaccess Check ---\n";
$htaccess = "Options -Indexes\nRewriteEngine On\nRewriteCond %{REQUEST_URI} !^/public\nRewriteRule ^(.*)$ /public/$1 [L,QSA]";
$ssh->exec("echo '$htaccess' > $appPath/.htaccess");
echo "✅ .htaccess created\n";

echo "\n=== DEPLOY COMPLETE ===\n";
echo "URL: https://farizahmad.com/inventorywalkot\n";
