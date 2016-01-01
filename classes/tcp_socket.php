<?php

/**
 * Description of tcp_socket
 *
 * @author Kalle
 */
class tcp_socket {

    // server type
    private $type;
    // server socket
    private $socket;
    // connected clients
    public $clients = array();
    // addresses of connected clients
    private $clients_address = array();
    // client classes
    private $clients_classes = array();
    // max clients per ip
    private $max_connperip = 0;
    // max connections
    private $max_clients = 0;
    // ip list of current open sockets
    private $list_clientips = array();
    // counter for current connected clients
    private $cur_connections = 0;
    // latest announcement of connected clients
    private $last_announce = 0;
    // keep_alive_timeout
    private $keep_alive_timeout = 30;
    // last keep_alive timeout
    private $last_keep_alive_timeout = 0;

    /**
     * 
     * @param type $type
     * @param type $port
     * @param type $address
     * @param type $maxclientsperip
     * @param type $maxconnections
     */
    public function __construct($type, $port, $address = '0.0.0.0', $max_connperip = 20, $max_clients = 1000) {
        // create socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
        // bind socket
        if(!@socket_bind($this->socket, $address, $port)) {
            tools::log("Could not bind socket " . $address . ":" . $port);
            exit; // stop script...
        }
        // listen to socket
        socket_listen($this->socket);
        // set socket non blocking
        socket_set_nonblock($this->socket);
        // max connections per ip
        $this->max_connperip = $max_connperip;
        // max clients for socket
        $this->max_clients = $max_clients;
        // set type
        $this->type = $type;
        // load class
        include('./classes/' . $this->type . '.php');
        // log message
        tools::log('socket started on ' . $address . ':' . $port . ' with ' . $max_clients . ' clients max');
    }

    /**
     * 
     * @param type $client
     * @param type $i
     */
    public function close_socket($client, $i) {
        // delete connection from our internal list
        $this->count_connectedips($i);
        // call class timeout if class exist
        if(isset($this->clients_classes[$i])) {
            $this->clients_classes[$i]->timeout();
            // unset class
            unset($this->clients_classes[$i]);
        }
        // close socket
        socket_close($client);
        tools::log('socket closed #tcp_socket');
        // collect cycles
        gc_collect_cycles();
        // unset client arrays
        unset($this->clients[$i]);
        unset($this->clients_address[$i]);

        return TRUE;
    }

    /**
     * 
     * @param type $newconnection
     */
    private function count_connectedips($i, $address = NULL) {
        if ($address == NULL) {
            $this->list_clientips[$this->clients_address[$i]['ip']] --;
            if ($this->list_clientips[$this->clients_address[$i]['ip']] == 0) {
                unset($this->list_clientips[$this->clients_address[$i]['ip']]);
            }
        } else {
            if (!isset($this->list_clientips[$address])) {
                $this->list_clientips[$address] = 1;
            } else {
                $this->list_clientips[$address] ++;
            }
        }

        return TRUE;
    }

    /**
     * 
     */
    private function client_announcement() {
        // announce connected clients every 30 seconds
        if ($this->last_announce <= time() - 30) {
            tools::log('======= ' . intval(count($this->clients)) . ' of ' . intval($this->max_clients) . ' sockets active =======');
            $this->last_announce = time();
        }

        return TRUE;
    }

    private function initialize_client($socket, $i) {
        // if we're not at the connected clients limit
        if (count($this->clients) <= $this->max_clients) {
            // if the user hasn't to much connections from is ip
            if ($this->list_clientips[$this->clients_address[$i]['ip']] <= $this->max_connperip) {
                // initialize new class for this client
                $this->clients_classes[$i] = new $this->type($socket, $i, $this->clients_address[$i]);
            } else {
                // close socket - to many connections from his ip
                tools::log('closed connection from ' . $this->clients_address[$i]['ip'] . ':' . $this->clients_address[$i]['port'] . ' #max_connperip_reached');
                $this->close_socket($this->clients[$i], $i);
            }
        } else {
            // close socket - to many overall connections
            $this->close_socket($this->clients[$i], $i);
            tools::log('closed connection from ' . $this->clients_address[$i]['ip'] . ':' . $this->clients_address[$i]['port'] . ' #max_clients_reached');
        }
    }

    /**
     * 
     * @param type $read
     */
    private function read_sockets($read) {
        if (in_array($this->socket, $read)) {
            if (( $client = socket_accept($this->socket))) {
                // set socket timeout for client
                socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
                socket_set_option($client, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
                // add client to clients array
                $this->clients[] = $client;
                // set pointer to last element to get key of last inserted element
                $client = end($this->clients);
                $index = key($this->clients);
                // get peer name from socket
                $address = NULL;
                $port = NULL;
                socket_getpeername($client, $address, $port);
                // add ip + port to client_address array
                $this->clients_address[$index] = array(
                    'ip' => $address,
                    'port' => $port);
                // count connected ip
                $this->count_connectedips(0, $address);
                // announce conninitialize_clientected client
                tools::log('new connection from ' . $address . ':' . $port);
                // initialize client
                $this->initialize_client($client, $index);
            } else {
                // error with client connection
                tools::log('closed connection #socket_not_accepted');
            }
        }
    }

    /**
     * 
     * @param type $client
     * @param type $i
     */
    private function check_timeout($client, $i) {
        if (socket_last_error($client) > 0 OR @ socket_write($client, '\ka\\final\\') === FALSE) {
            // log output
            $address = $this->clients_address[$i];
            tools::log('socket timeout (' . $address['ip'] . ':' . $address['port'] . ')');
            // close socket
            $this->close_socket($client, $i);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * 
     * @param type $read
     */
    private function read_clients($read) {
        foreach ($this->clients as $i => $client) {
            $timeout = FALSE;
            // keep an eye on disconnected clients..
            if ($this->last_keep_alive_timeout <= time() - $this->keep_alive_timeout) {
                $timeout = $this->check_timeout($client, $i);
            }

            if (in_array($client, $read) AND $timeout == FALSE) {
                if($this->clients_classes[$i]->loop() === FALSE) {
                    $this->close_socket($client, $i);
                }
            }
        }
        if ($this->last_keep_alive_timeout <= time() - $this->keep_alive_timeout) {
            $this->last_keep_alive_timeout = time();
        }
        return TRUE;
    }

    /**
     * 
     */
    public function loop() {
        // socket available
        if ($this->socket !== FALSE) {
            // announce connected clients
            $this->client_announcement();
            // read
            $read = array_merge(array($this->socket), $this->clients);
            $write = [];
            $except = [];
            // if you can read from sockets
            if (socket_select($read, $write, $except, 0, 0) !== FALSE) {
                // read from socket
                $this->read_sockets($read);
            } else {
                // exit if there is an fatal error
                tools::log('FATAL ERROR');
                exit;
            }
            // loop through existing clients
            $this->read_clients($read);
            // delete closed client connections
            $this->clients = array_filter($this->clients);
        }

        return TRUE;
    }

}
