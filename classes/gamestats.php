<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of gpcm
 *
 * @author Kalle
 */
class gamestats {

    private $index;
    private $ip = 0;
    private $port = 0;
    private $challenge;
    private $session;

    public function __construct($socket, $index, $address) {
        // set private variables
        $this->socket = $socket;
        $this->index = $index;
        $this->ip = $address['ip'];
        $this->port = $address['port'];
        // create challenge
        $this->challenge = tools::generateRandomString(10);
        // create session
        $this->session = tools::generateRandomInt(5);
        // send challenge to client
        $response = gamespy::xor_this('\lc\1\challenge\\' . $this->challenge . '\id\1') . '\final\\';
        tools::log('server: ' . '\lc\1\challenge\\' . $this->challenge . '\id\1' . '\final\\');
        socket_write($this->socket, $response);
        tools::log('initializing new client (' . $this->ip . ':'.$this->port.')');
    }

    /**
     * if the client got an timeout do stuff to let him disconnect correctly
     */
    public function timeout() {
        //log
        tools::log('class timeout for ' . $this->ip . ':' . $this->port);

        return true;
    }

    /**
     * the main loop for the client we handle I/O operations
     */
    public function loop() {
        $tmp = TRUE;
        // read from socket
        $input = @socket_read($this->socket, 1024000);
        if ($input !== FALSE) {
            if (!empty($input)) {
                // if there are multiple messages in one "packet"
                if (substr_count($input, '\final\\') > 1) {
                    $ex = explode('\final\\', $input);
                    foreach ($ex as $item) {
                        if (trim($item) !== '') {
                            if ($this->commands($item . '\final\\') === FALSE) {
                                $tmp = FALSE;
                            }
                        }
                    }
                } else {
                    $tmp = $this->commands($input);
                }
                return $tmp;
            }
        }
        if (socket_last_error($this->socket) > 0) {
            return FALSE;
        }
        return TRUE;
    }

    public function commands($input) {
        if ($input !== FALSE) {
            if (!empty($input)) {
                $ex = explode('\final\\', trim($input));
                if (isset($ex[0])) {
                    $input = gamespy::xor_this($ex[0]) . '\final\\';
                    tools::log('client: ' . $input);
                    $ex = explode('\\', trim($input));
                    switch ($ex[1]) {
                        case 'auth':
                            // server authenticates 
                            tools::log('\lc\2\sesskey\\' . $this->session . '\proof\0\id\1' . '\final\\');
                            $response = gamespy::xor_this('\lc\2\sesskey\\' . $this->session . '\proof\0\id\1') . '\final\\';
                            socket_write($this->socket, $response);
                            return true;
                            break;
                        case 'newgame':
                            tools::log('client (' . $this->ip . ':' . $this->port . ') #newgame');
                            return true;
                            break;
                        case 'updgame':
                            tools::log('client (' . $this->ip . ':' . $this->port . ') #updgame');
                            $this->parse_statistics($csocket, $ex, $this->challenge, $this->session);
                            return true;
                        default:
                            tools::log('client (' . $this->ip . ':' . $this->port . ') disconnected... #default');
                            return false;
                            break;
                    }
                } else {
                    tools::log('client (' . $this->ip . ':' . $this->port . ') disconnected... #3');
                    return false;
                }
            } else {
                tools::log('client (' . $this->ip . ':' . $this->port . ') disconnected... #1');
                return false;
            }
        } else {
            tools::log('client (' . $this->ip . ':' . $this->port . ') disconnected... #0');
            return false;
        }
    }

    private function sql_insertround($array) {
        // we dont want any entries like the id
        if (isset($array['id'])) {
            unset($array['id']);
        }

        // do we have the required entries for auth?
        if (isset($array['player'])) {
            // then try to find this user
            $query = "SELECT * FROM users WHERE name='" . database::esc($array['player']) . "' AND status>='1' LIMIT 0,1";
            $sql = database::query($query);
            // if we found him insert the round
            if (database::num_rows($sql) == 1) {
                $row = database::fetch_object($sql);
                // init vars
                $tmp1 = '';
                $tmp2 = '';
                // set uid
                $array['uid'] = $row->id;
                // build sql insert statement
                foreach ($array as $key => $value) {
                    $tmp1.= database::esc($key) . ',';
                    $tmp2.= '\'' . database::esc($value) . '\',';
                }
                // insert played round
                $query = "INSERT INTO stats_rounds (" . substr($tmp1, 0, -1) . ")VALUES(" . substr($tmp2, 0, -1) . ")";
                $sql = database::query($query);
                // update player ranking
                $sql = "UPDATE users SET"
                        . " stats_finishes=stats_finishes+'" . database::esc($array['finishes'], 'int') . "',"
                        . " stats_deaths=stats_deaths+'" . database::esc($array['deaths'], 'int') . "',"
                        . " stats_kills=stats_kills+'" . database::esc($array['kills'], 'int') . "',"
                        . " stats_playerpoints=stats_playerpoints+'" . database::esc($array['playerpoints'], 'int') . "',"
                        . " stats_timePlayed=stats_timePlayed+'" . database::esc($array['timePlayed'], 'int') . "',"
                        . " stats_ctime=stats_ctime+'" . database::esc($array['ctime'], 'int') . "',"
                        . " stats_starts=stats_starts+'" . database::esc($array['starts'], 'int') . "',"
                        . " stats_heropoints=stats_heropoints+'" . database::esc($array['heropoints'], 'int') . "',"
                        . " stats_livingStreak=stats_livingStreak+'" . database::esc($array['livingStreak'], 'int') . "',"
                        . " stats_rating=stats_rating+'" . database::esc($array['rating'], 'int') . "',"
                        . " stats_gameComplete=stats_gameComplete+'" . database::esc($array['gameComplete'], 'int') . "',"
                        . " stats_winningCnt=stats_winningCnt+'" . database::esc($array['winningCnt'], 'int') . "',"
                        . " stats_losingCnt=stats_losingCnt+'" . database::esc($array['losingCnt'], 'int') . "',"
                        . " stats_roundsplayed=stats_roundsplayed+1,"
                        . " stats_lastPlayed='" . database::esc(time(), 'int') . "'"
                        . " WHERE id='" . database::esc($row->id, 'int') . "'";
                database::query($sql);
            }
        }
    }

    public function parse_statistics($csocket, $ex, $schallenge, $session) {
        // only whitelisted servers are allowed to give us statistics
//        $query = "SELECT * FROM stats_servers WHERE ip='" . database::esc($this->ip) . "' AND active='1' LIMIT 0,1";
//        $sql = database::query($query);
//        if (database::num_rows($sql) == 1) {
//            $row = database::fetch_object($sql);
        if (in_array('gamedata', $ex)) {
            $data = explode(chr(1), $ex[array_search('gamedata', $ex) + 1]);
            $users = array();
            $server = array();
            $time = time();
            foreach ($data as $key => $value) {
                // 0 => 0   Var
                // 1 => 0,5 Wert
                // 2 => 1   Var
                // 3 => 1,5 Wert
                // if this entry is a variable
                if ($key % 2 === 1) {
                    // user value
                    if (strpos($value, '_') !== FALSE) {
                        $ex3 = explode('_', $value, 2);
                        if (isset($ex3[1]) AND isset($data[$key + 1])) {
                            if (isset($users[$ex3[1]])) {
                                $users[$ex3[1]][$ex3[0]] = $data[$key + 1];
                            } else {
                                $users[$ex3[1]] = array();
                                $users[$ex3[1]][$ex3[0]] = $data[$key + 1];
                            }
                        }
                    } else {  // server value
                        if (isset($data[$key + 1])) {
                            $server[$value] = $data[$key + 1];
                        }
                    }
                }
            }
            // we have data - round ended :)
            // users array:
            // 0...X => array:
            // finishes => 1
            // player => Kalle
            // deaths => 10
            // playerpoints => 102
            // endfaction => Empire
            // timePlayed => 565
            // kills => 4
            // ctime => 0
            // starts => 1
            // heropoints => 0
            // livingStreak => 0
            // rating_0 => 102
            // 
            // server array:
            // winningTeam => Empire
            // gameComplete => 1
            // winningCnt => 2147483622
            // losingCnt => 2147483581
            // gametype => IA
            // losingTeam => Rebels
            // GameMode => 3
            // mapname => spa7c_ass
            // only if more then 3 people are logged in!
            // 
            if (count($users) > 0 AND count($server) > 0) {
                foreach ($users as $user) {
                    if (isset($user['player']) AND
                            isset($user['endfaction']) AND
                            isset($user['auth'])) {
                        if (!isset($user['finishes'])) {
                            $user['finishes'] = 0;
                        }
                        if (!isset($user['deaths'])) {
                            $user['deaths'] = 0;
                        }
                        if (!isset($user['playerpoints'])) {
                            $user['playerpoints'] = 0;
                        }
                        if (!isset($user['timePlayed'])) {
                            $user['timePlayed'] = 0;
                        }
                        if (!isset($user['kills'])) {
                            $user['kills'] = 0;
                        }
                        if (!isset($user['ctime'])) {
                            $user['ctime'] = 0;
                        }
                        if (!isset($user['starts'])) {
                            $user['starts'] = 1;
                        }
                        if (!isset($user['heropoints'])) {
                            $user['heropoints'] = 0;
                        }
                        if (!isset($user['livingStreak'])) {
                            $user['livingStreak'] = 0;
                        }
                        if (!isset($user['rating'])) {
                            $user['rating'] = 0;
                        }

                        $user['timestamp'] = $time;
//                            $user['sid'] = $row->id;
                        tools::log(print_r($user, true));
                        $this->sql_insertround(array_merge($user, $server));
                    }
                }
                tools::log('gameserver (' . $this->ip . ':' . $this->port . ') statistic updated');
            }
        }
//        } else {
//            tools::log('gameserver (' . $this->ip . ':' . $this->port . ') is NOT whitelisted - sorry no statistics...');
//        }
    }

}
