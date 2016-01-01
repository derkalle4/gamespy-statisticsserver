<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tools
 *
 * @author Kalle
 */
class tools {

    public static function log($msg) {
        $msg = "[" . date('d.m.Y H:i:s', time()) . "] $msg\n";
        tools::log_to_file($msg);
        echo $msg;
    }

    public static function log_to_file($text, $filename = 'gsloginserver.log') {
        if (!file_exists($filename)) {
            touch($filename);
            chmod($filename, 0666);
        }
        if (filesize($filename) > 2 * 1024 * 1024) {
            $filename2 = "$filename.old";
            if (file_exists($filename2))
                unlink($filename2);
            rename($filename, $filename2);
            touch($filename);
            chmod($filename, 0666);
        }
        if (!is_writable($filename))
            die("\nCannot open log file ($filename)");
        if (!$handle = fopen($filename, 'a'))
            die("\nCannot open file ($filename)");
        if (fwrite($handle, $text) === FALSE)
            die("\nCannot write to file ($filename)");
        fclose($handle);
    }

    public static function generateRandomString($length = 10) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function generateRandomInt($length = 10) {
        $characters = '0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

}
