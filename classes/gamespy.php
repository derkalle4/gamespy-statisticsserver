<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of gamespy
 *
 * @author Kalle
 */
class gamespy {

    // err 516 -> account name already in use
    // err 262 -> profile got deleted create a new one
    // err 261 -> profil ungültig
    // err 260 -> wrong password
    // err 259 -> wrong mail
    // err 258 -> name ungültig
    // err 257 -> no server connection
    public static $error_msg_notcompatible = '\error\\err\263\fatal\\errmsg\Your Game is currently not compatible with our MasterServer. Visit GameShare.co for help!\id\1\final\\';
    public static $error_msg_wronglogin = '\error\\err\261\fatal\\errmsg\Sorry, Username or Password wrong!\id\1\final\\';
    public static $plugins = array();
    static $SPECIAL_CHARS_DEF = ['=', '+', '/'];
    static $SPECIAL_CHARS_GSP = ['_', '[', ']'];

    public static function init() {
        tools::log('Initializing gamespy class');
        $handle = opendir('./games/');
        while ($file = readdir($handle)) {
            if ($file != '.' AND $file != '..') {
                include_once('./games/' . $file);
                $file = substr($file, 0, strlen($file) - 4);
                self::$plugins[$file] = new $file();
            }
        }
        closedir($handle);
    }

    public static function check_login($socket, $ex, $schallenge, $session) {
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('gamename', $ex) + 1])
                OR ! isset(self::$plugins[$ex[array_search('gamename', $ex) + 1]])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            if (isset(self::$plugins[$ex[array_search('gamename', $ex) + 1]])) {
                return self::$plugins[$ex[array_search('gamename', $ex) + 1]]->login($socket, $ex, $schallenge, $session);
            } else {
                return self::$plugins['common']->login($socket, $ex, $schallenge, $session);
            }
        }
    }

    public static function status($socket, $ex, $schallenge, $session) {
        // \status\1\sesskey\43628\statstring\Online\locstring\swbfront2pc\final\
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('sesskey', $ex) + 1])
                OR ! isset($ex[array_search('statstring', $ex) + 1])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->status($socket, $ex, $schallenge, $session);
        }
    }

    public static function get_profile($socket, $ex, $schallenge, $session) {
        if (!isset($ex[array_search('final', $ex)])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->get_profile($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function update_profile($socket, $ex, $schallenge, $session) {
        if (!isset($ex[array_search('final', $ex)])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->update_profile($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function register($socket, $ex, $schallenge, $session) {
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('gamename', $ex) + 1])
                OR ! isset(self::$plugins[$ex[array_search('gamename', $ex) + 1]])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            if (isset(self::$plugins[$ex[array_search('gamename', $ex) + 1]])) {
                return self::$plugins[$ex[array_search('gamename', $ex) + 1]]->register($socket, $ex, $schallenge, $session);
            } else {
                return self::$plugins['common']->register($socket, $ex, $schallenge, $session);
            }
        }
    }

    public static function addbuddy($socket, $ex, $schallenge, $session) {
        //\addbuddy\\sesskey\59184\newprofileid\1\reason\Battlefront2 Request\final\
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('newprofileid', $ex) + 1])
                OR ! isset($ex[array_search('reason', $ex) + 1])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->addbuddy($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function authadd($socket, $ex, $schallenge, $session) {
        //\authadd\\sesskey\59184\fromprofileid\1\sig\234\autosync\true..false\final\
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('fromprofileid', $ex) + 1])
                OR ! isset($ex[array_search('sig', $ex) + 1])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->authadd($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function registercdkey($socket, $ex, $schallenge, $session) {
        //\registercdkey\\sesskey\59184\cdkeyenc\2rfwejfigoerglreg\gameid\1\id\1\final\
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('registercdkey', $ex) + 1])
                OR ! isset($ex[array_search('cdkeyenc', $ex) + 1])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->registercdkey($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function pinvite($socket, $ex, $schallenge, $session) {
        //\pinvite\\sesskey\59184\profileid\1\productid\1\location\1\final\
        if (!isset($ex[array_search('final', $ex)])
                OR ! isset($ex[array_search('pinvite', $ex) + 1])
                OR ! isset($ex[array_search('profileid', $ex) + 1])) {
            // disconnect client if he does not sent all information
            socket_write($socket, self::$error_msg_notcompatible);
            return false;
        } else {
            return self::$plugins['common']->pinvite($socket, $ex, $schallenge, $session);
        }
    }
    
    public static function keep_alive($socket, $ex, $schallenge, $session) {
        return self::$plugins['common']->keep_alive($socket, $ex, $schallenge, $session);
    }

    public static function validate_response($username, $cchallenge, $schallenge, $password) {
        $value = $password;
        for ($i = 0; $i < 48; $i++) {
            $value.= " ";
        }

        $value.= $username;
        $value.= $cchallenge;
        $value.= $schallenge;
        $value.= $password;

        return md5($value);
    }

    public static function valid_response($username, $cchallenge, $schallenge, $password) {
        $value = $password;
        for ($i = 0; $i < 48; $i++) {
            $value.= ' ';
        }
        $value.= $username;
        $value.= $schallenge;
        $value.= $cchallenge;
        $value.= $password;
        return md5($value);
    }

    // https://github.com/BF2Statistics/ControlCenter/blob/master/BF2Statistics/Gamespy/GamespyUtils.cs#L40 
//    public static function pass_encode($pass) {
//        $a = 0;
//        $num = 0x79707367;
//        for ($i = 0; $i < strlen($pass);$i++) {
//            $num = $num;
//            $a = $num % 0xFF; 
//        $pass[$i] ^= $a;
//        }
//        
//        return $pass;
//    }
    
    static function intval32bits($value) {
        $value = ($value & 0xFFFFFFFF);

        if ($value & 0x80000000)
            $value = -((~$value & 0xFFFFFFFF) + 1);

        return $value;
    }

    static function gslame($num) {
        $c = (($num >> 16) & 0xffff) * 0x41a7;
        $a = self::intval32bits(($num & 0xffff) * 0x41a7 + (($c & 0x7fff) << 16));
        if ($a < 0) {
            $a &= 0x7fffffff;
            $a++;
        }
        $a += ($c >> 15);
        if ($a < 0) {
            $a &= 0x7fffffff;
            $a++;
        }
        return $a;
    }

    static function gspassenc($pass) {
        $num = 0x79707367;   // "gspy"

        for ($i = 0; $i < strlen($pass); $i++) {
            $num = self::gslame($num);
            $pass[$i] = chr(ord($pass[$i]) ^ ($num % 0xff));
        }
        return $pass;
    }

    static function passdecode($pass) {
        return self::gspassenc(base64_decode(str_replace(self::$SPECIAL_CHARS_GSP, self::$SPECIAL_CHARS_DEF, $pass)));
    }

    static function passencode($pass) {
        return str_replace(self::$SPECIAL_CHARS_DEF, self::$SPECIAL_CHARS_GSP, base64_encode(self::gspassenc($pass)));
    }

    public static function xor_this($text, $key = 'GameSpy3D') {
        $outText = '';
        for ($i = 0; $i < strlen($text);) {
            for ($j = 0; ($j < strlen($key) && $i < strlen($text)); $j++, $i++) {
                $outText .= $text{$i} ^ $key{$j};
            }
        }
        return $outText;
    }

}
