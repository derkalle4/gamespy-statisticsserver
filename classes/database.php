<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of database
 *
 * @author kalle
 */
class database {
    public static $socket;
    private static $host;
    private static $username;
    private static $password;
    private static $db;
    
    public static function construct($host,$username,$password,$db) {
        self::$host = $host;
        self::$username = $username;
        self::$password = $password;
        self::$db = $db;
        self::connect();
    }
    
    private function connect() {
        tools::log('initializing database connection');
        self::$socket = @mysqli_connect(self::$host,self::$username,self::$password) OR sleep(1);
        @mysqli_select_db(self::$socket,self::$db) OR sleep(1);
        if(@mysqli_ping(self::$socket)) {
            tools::log('successful initialized database connection');
        }else{
            self::ping();
        }
    }
    
    private function ping() {
        if(!@mysqli_ping(self::$socket)) {
            tools::log('ping timeout database connection');
            self::disconnect();
            self::connect();
        }
    }
    
    private function disconnect() {
        @mysql_close(self::$socket);
        tools::log('disconnected database connection');
    }
    
    public static function query($query) {
        self::ping();
        return mysqli_query(self::$socket,$query);
    }
    
    public static function num_rows($query) {
        self::ping();
        return mysqli_num_rows($query);
    }
    
    public static function fetch_object($query) {
        self::ping();
        return mysqli_fetch_object($query);
    }
    
    public static function last_insert_id() {
        self::ping();
        return mysqli_insert_id(self::$socket);
    }
    
    public static function esc($str,$type = 'str') {
        if($type == 'str') {
            return mysqli_real_escape_string(self::$socket,$str);
        }else{
            return intval($str);
        }
    }
}
