#!/usr/bin/env php
<?php
require './vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

copy('../template/squid.conf','./squid.conf');
$to = Yaml::parseFile('../template/docker-compose.yml');
$proxies = fopen('../proxyList.txt','r');

$i = 1;
$port = 49152;

while ($line = fgets($proxies)){
    $line = trim($line);
    $proxyInfo = (explode(":",$line));
    $cred = '';
    if(!isset($proxyInfo[0]) && !isset($proxyInfo[1])){
        continue;
    }
    if(!isset($proxyInfo[2])){
        //open proxy server IP:Port Pattern.
        //No create gost
        //only add squid config
        file_put_contents('./squid.conf',PHP_EOL.sprintf('cache_peer %s parent %d 0 no-digest no-netdb-exchange connect-fail-limit=10 connect-timeout=8 round-robin no-query allow-miss proxy-only name=public%d',$proxyInfo[0],$proxyInfo[1],$i),FILE_APPEND);
        $i++;
        continue;
    }
    //other proxy type ex:socks
    if (isset($proxyInfo[3]) && isset($proxyInfo[4])) {
        $cred = urlencode($proxyInfo[3]) . ':' . urlencode($proxyInfo[4]) . '@';
    }
    $to['services']['proxy' . $i] = [
        'ports' => [
            $port . ':' . $port
        ],
        'image' => 'chenhw2/gost:latest',
        'environment' => [
            'ARGS' => sprintf('-L=:%d -F=%s://%s%s:%d', $port, $proxyInfo[2], $cred, $proxyInfo[0], $proxyInfo[1])
        ]
    ];
    $i++;
    $port++;
}

file_put_contents('../docker-compose.yml', Yaml::dump($to));
rename('./squid.conf','../config/squid.conf');
copy('../template/allowed_ip.txt','../config/allowed_ip.txt');
