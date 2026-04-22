<?php
require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;

$ssh = new SSH2('145.79.14.233', 65002);
if (!$ssh->login('u674511048', '!FarizAhmad123456')) {
    exit('Login Failed');
}

echo "Login Successful\n";

$commands = [
    "cd /home/u674511048/domains/farizahmad.com/public_html/inventorywalkot",
    "php artisan key:generate --force",
    "php artisan config:clear",
    "php artisan cache:clear",
    "php artisan view:clear",
    "cat .env | grep APP_KEY"
];

$commandString = implode(" && ", $commands);

echo "Executing fix commands...\n";
$output = $ssh->exec($commandString);
echo "Output:\n" . $output;
echo "\nFix finished.\n";
