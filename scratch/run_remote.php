<?php
require __DIR__ . '/../vendor/autoload.php';
use phpseclib3\Net\SSH2;
$cmd = $argv[1] ?? 'ls';
$ssh = new SSH2('145.79.14.233', 65002);
if (!$ssh->login('u674511048', '!FarizAhmad123456')) exit('Login Failed');
$output = $ssh->exec("cd /home/u674511048/domains/farizahmad.com/public_html/inventorywalkot && " . $cmd);
echo $output;
