<?php

set_time_limit(0);

include_once 'classes/database.php';
include_once 'classes/tools.php';
include_once 'classes/gamespy.php';
include_once 'classes/tcp_socket.php';

// initialize database
database::construct('localhost','root','technik,01','master');

// create gpcm.gamespy.com with max 20 connections per IP and max. 1.000 sockets
$gamestats = new tcp_socket('gamestats',29920,'0.0.0.0',20,1000);

while(true) {
    $gamestats->loop();
    usleep(100);
}