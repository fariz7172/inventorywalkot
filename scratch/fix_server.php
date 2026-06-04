<?php
require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;

$ssh = new SSH2('farizahmad.com', 65002);
if (!$ssh->login('u674511048', '!FarizAhmad123456')) {
    exit('Login Failed');
}

echo "Login Successful\n";

$commands = [
    "cd /home/u674511048/domains/farizahmad.com/public_html/inventorywalkot",
    "echo '<IfModule mod_rewrite.c>' > .htaccess",
    "echo '    RewriteEngine On' >> .htaccess",
    "echo '    RewriteRule ^(.*)$ public/$1 [L]' >> .htaccess",
    "echo '</IfModule>' >> .htaccess",
    "find . -type d -exec chmod 755 {} \;",
    "find . -type f -exec chmod 644 {} \;",
    "chmod -R 775 storage bootstrap/cache"
];

$commandString = implode(" && ", $commands);

echo "Executing fix commands...\n";
$output = $ssh->exec($commandString);
echo "Output: " . $output;
echo "\nFix finished.\n";
