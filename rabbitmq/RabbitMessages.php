<?php
//require_once str_ireplace('www/rabbitmq', '', __DIR__) . 'app/composer/vendor/autoload.php';
$strfile = str_ireplace('app/extensions/rabbitmq', '', __DIR__) . 'app/extensions/yii-amqp/vendor/autoload.php';
if (file_exists($strfile))
require_once $strfile;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * RabbitMessages for provided process of working with SMS, Email etc...
 *
 * @category   RabbitMessages
 */
class RabbitMessages {
    /**
     * ip address where located queue server like 127.0.0.1
     *
     * @var string
     */
    private $_ip_rabbit_server;
    /**
     * port for cinnection to server
     *
     * @var string
     */
    private $_port_rabbit_server;
    /**
     * login for connection to server like 9999
     *
     * @var string
     */
    private $_login_rabbit_server;
    /**
     * password for connection to server
     *
     * @var string
     */
    private $_pass_rabbit_server;
    /**
     * name of point exchange
     *
     * @var string
     */
    private $_exchange;
    /**
     * key routing for make relation between point exchange and queue
     *
     * @var string
     */
    private $_key_for_route;
    /**
     * method for create consumer
     *
     * @var binary
     */
    private $_callback;
    /**
     * uses connection
     *
     * @var AMQPStreamConnection
     */
    private $_connection;
    /**
     * array parameters of existing process ready for consuming messages like
     * 1.)[0] name of point exchange
     * 2.)[1] key_for_route from this exchange on queue
     * 3.)[2] channel of current connection
     * 4.)[3] name queue
     *
     * @var array
     */
    private $_worker;
    /**
     * array of messages like
     * ['firs' => ['text' => 'My type message is sms bla bla', 'timeout' => 5, 'timeleft' => 8, 'type' => 1],
     * 'second' => [random list of values according uses in $_callback],
     * '...' =>...];
     * for example callback(array $firs){}
     *
     * @var array
     */
    private $_messages;
    /*
     * count of elements in array messages
     *  @var int
     */
    private $_count_messages;
    /*
     * i try create dinamic value of class exemplars
     */
    //static public $count;
    public function __construct($ip, $port, $login, $pass, $route_key = NULL, $exchange = NULL) {
        $this->setIp($ip);
        $this->setPort($port);
        $this->setLogin($login);
        $this->setPass($pass);
        $this->setKey($route_key);
        $this->setExchange($exchange);
        //self::$count++;
        //$this->count++;
    }
    public function __destruct() {
        //self::$count--;
        //$this->count--;
    }
    public function execute($messages, $callback) {
        $this->setCallback($callback);
        $this->setMessages($messages);
        $this->sendMessages();
    }
    /**
     * Set the ip address for connection
     *
     * @param string string|null
     */
    public function setIp($ip_rabbit_server) {
        if (is_null($ip_rabbit_server)) {
            $ip_rabbit_server = '127.0.0.1';
        }
        $this->_ip_rabbit_server = $ip_rabbit_server;
    }
    /**
     * Set the port for connection
     *
     * @param string string|null
     */
    public function setPort($port_rabbit_server) {
        if (is_null($port_rabbit_server)) {
            $port_rabbit_server = '5672';
        }
        $this->_port_rabbit_server = $port_rabbit_server;
    }
    /**
     * Set the login for connection
     *
     * @param string string|null
     */
    public function setLogin($login_rabbit_server) {
        if (is_null($login_rabbit_server)) {
            $login_rabbit_server = 'guest';
        }
        $this->_login_rabbit_server = $login_rabbit_server;
    }
    /**
     * Set the password for connection
     *
     * @param string string|null
     */
    public function setPass($pass_rabbit_server) {
        if (is_null($pass_rabbit_server)) {
            $pass_rabbit_server = 'guest';
        }
        $this->_pass_rabbit_server = $pass_rabbit_server;
    }
    /**
     * Set the name for point of exchange, if params is null
     * name will be generated
     *
     * @param string string|null
     */
    public function setExchange($exchange) {
        if (is_null($exchange)) {
            $exchange = $this->getToken(5);
        }
        $this->_exchange = $exchange;
    }
    /**
     * Set the routing key for relation between point exchange and queue
     * if will be passed null the key will be generated
     *
     * @param string string|null
     */
    public function setKey($key_for_route) {
        if (is_null($key_for_route)) {
            $key_for_route = $this->getToken(7);
        }
        $this->_key_for_route = $key_for_route;
    }
    /**
     *
     * Set executing method for create consumer
     *
     * @param binary
     */
    public function setCallback($callback) {
        $this->_callback = $callback;
    }
    /**
     * set array of messages and count of his elemets
     *
     * @param array
     */
    public function setMessages($messages) {
        $this->_messages = $messages;
        $count_messages = 0;
        foreach ($messages as $key => $val) {
            $count_messages++;
        }
        $this->_count_messages = $count_messages;
    }
    /**
     * /**
     * set STATIC value of class
     *
     * @param array
     */
    public function setCountMessages($count_messages) {
        // $this->_count_messages = $count_messages;
    }
    /**
     * Return Ip address of Rabbit server
     *
     * @return string
     */
    public function getIp() {
        return $this->_ip_rabbit_server;
    }
    /**
     * Return uses port of Rabbit server
     *
     * @return string
     */
    public function getPort() {
        return $this->_port_rabbit_server;
    }
    /**
     * Return name of point exchange
     *
     * @return string
     */
    public function getExchange() {
        return $this->_exchange;
    }
    /**
     * Return route key
     *
     * @return string
     */
    public function getKey() {
        return $this->_key_for_route;
    }
    /**
     * Return method consumer
     *
     * @return binary
     */
    public function getCellback() {
        return $this->_callback;
    }
    /**
     * Return current connection
     *
     * @return AMQPStreamConnection
     */
    public function getConnection() {
        return $this->_connection;
    }
    /**
     * Return array parameters of current consumer
     * 1.)[0] name of point exchange
     * 2.)[1] key_for_route from this exchange on queue
     * 3.)[2] channel of current connection
     * 4.)[3] name queue
     *
     * @return array
     */
    public function getWorker() {
        return $this->_worker;
    }
    /**
     * return array of messages like
     * ['firs' => ['text' => 'My type message is sms bla bla', 'timeout' => 5, 'timeleft' => 8, 'type' => 1],
     * 'second' => [random list of values according uses in $_callback],
     * '...' =>...];
     * for example callback(array $firs){}
     *
     * @return array
     */
    public function getMessages() {
        return $this->_messages;
    }
    /**
     * this function returns STATIC property of class
     *
     * @return int
     */
    public function getCountMessages() {
        //    return $this->_count_messages;
        foreach ($this->_messages as $key => $val) {
            $count_messages++;
        }
        return $count_messages;
    }
    /*
     * create random string
     * @param int
     * @return string
     */
    private function getToken($length) : string {
        //генерация токена заданной длинны
        if (intval($length) < 1) $length = 5;
        $token = "";
        $code = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $max = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $token .= $code[random_int(0, $max - 1)];
        }
        return $token;
    }
    /*
     * start execute messages chain on selected queue
     * @param array $invoice
     * @param string $queue_name
     */
    public function executeListener($invoice, $queue_name = 'queueListener') {
        $this->_connection =
            new AMQPStreamConnection($this->_ip_rabbit_server, $this->_port_rabbit_server, $this->_login_rabbit_server,
                                     $this->_pass_rabbit_server);
        $channel = $this->_connection->channel();
        $channel->queue_declare($queue_name,    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
                                FALSE,
            #passive - может использоваться для проверки того, инициирован ли обмен, без того, чтобы изменять состояние сервера
                                TRUE,
            #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении - очередь переживёт перезагрузку брокера
                                FALSE,
            #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
                                FALSE        #autodelete - очередь удаляется, когда отписывается последний подписчик
        );
        $body = json_encode($invoice);
        $msg = new AMQPMessage($body,
                               ['delivery_mode' => 2]
        //создаёт сообщение постоянным, чтобы оно не потерялось при падении или закрытии сервера
        );
        
        $channel->basic_publish($msg,            #сообщение
                                '',                #обмен
                                $queue_name    #ключ маршрутизации (очередь)
        );// $channel->basic_publish($msg, $exchange, $key_for_route||$queue_name);//
    
//        $message = $channel->basic_get($queue_name);//
//        //$channel->basic_ack($message->delivery_info['delivery_tag']);
//        var_dump($message->body);
        
        
        
        
        $channel->close();
        $this->_connection->close();
    }
    /*
     * create server procedure for listen chosen queue every time
     * @param string $queue_name
     */
    public function listen($queue_name = 'queueListener') {
        $this->_connection =
            new AMQPStreamConnection($this->_ip_rabbit_server, $this->_port_rabbit_server, $this->_login_rabbit_server,
                                     $this->_pass_rabbit_server);
        $channel = $this->_connection->channel();
        $callback = $this->_callback;
        $channel->queue_declare($queue_name, FALSE, TRUE, FALSE, FALSE);
        $channel->basic_qos(NULL,
            #размер предварительной выборки - размер окна предварительнйо выборки в октетах, null означает “без определённого ограничения”
                            1,
            #количество предварительных выборок - окна предварительных выборок в рамках целого сообщения
                            NULL    #глобальный - global=null означает, что настройки QoS должны применяться для получателей, global=true означает, что настройки QoS должны применяться к каналу
        );
        $channel->basic_consume($queue_name,        #очередь
                                '',
            #тег получателя - Идентификатор получателя, валидный в пределах текущего канала. Просто строка
                                FALSE,
            #не локальный - TRUE: сервер не будет отправлять сообщения соединениям, которые сам опубликовал
                                FALSE,
            #без подтверждения - false: подтверждения включены, true - подтверждения отключены. отправлять соответствующее подтверждение обработчику, как только задача будет выполнена
                                FALSE,
            #эксклюзивная - к очереди можно получить доступ только в рамках текущего соединения
                                FALSE, #не ждать - TRUE: сервер не будет отвечать методу. Клиент не должен ждать ответа
                                $callback    #функция обратного вызова - метод, который будет принимать сообщение
        );
        while (count($channel->callbacks)) {
            $channel->wait();
        }
        $channel->close();
        $this->_connection->close();
    }
    /*
     *start process
     *
     * get inner value like
     * _ip_rabbit_server,
     * _port_rabbit_server,
     * _login_rabbit_server,
     * _pass_rabbit_server
     * _exchange
     * _key_for_route
     * _callback
     *
     * set inner value like
     * _connection
     * _worker
     *
     *  @return array
     */
    public function makeWorker() : array {
        $this->_connection =
            new AMQPStreamConnection($this->_ip_rabbit_server, $this->_port_rabbit_server, $this->_login_rabbit_server,
                                     $this->_pass_rabbit_server);
        $queue_name = '';
        $channel = $this->_connection->channel();
        $exchange = $this->_exchange;
        $key_for_route = $this->_key_for_route;
        $callback = $this->_callback;
        $channel->exchange_declare($exchange, 'direct', FALSE, TRUE, TRUE);
        list($queue_name, ,) = $channel->queue_declare($queue_name, FALSE, TRUE, FALSE, TRUE);
        $channel->queue_bind($queue_name, $exchange, $key_for_route);
        $channel->basic_qos(NULL, 1, NULL);
        $channel->basic_consume($queue_name, $exchange, FALSE, FALSE, TRUE, FALSE, $callback);
        $this->_worker = [$exchange, $key_for_route, $queue_name];
        return [$exchange, $key_for_route, $channel, $queue_name];
    }
    /*
  *complete process
  *
  * get inner value like
  * _messages
  *
  * delete inner value like
  * _connection
  *
  *
  */
    public function sendMessages() {
        list($exchange, $key_for_route, $channel, $queue_name) = $this->makeWorker();
        
        $messages = $this->_messages;
        $count_messages = $this->_count_messages;
        $send = 1;
        foreach ($messages as $key => $val) {
            $body = json_encode($val);
            $msg = new AMQPMessage($body,
                                   ['delivery_mode' => 2]
            //создаёт сообщение постоянным, чтобы оно не потерялось при падении или закрытии сервера
            );
            $channel->basic_publish($msg, $exchange, $key_for_route);//, $queue_name
            //echo " \n\n [x] Sent count($messages) messages with in queue $queue_name \n ==================== \n\n";
        }
    
    
//        do {
//            $response = $channel->basic_get($queue_name);//берет сообщение но не отдает консюмеру
//        } while ( $response == null);
        
        $channel->wait_for_pending_acks();
        while (count($channel->callbacks) && (($send++) <= ($count_messages))) {
            // if(($send++) > ($count_messages)) // иначе наш сервис будет висеть вечно, но нам это не надо
            //&& isset($msg) && // извращенский способ выйти из цикла
            $channel->wait();//echo '_'.$send.'_'; print($count_messages);

//            if(($send++) > ($count_messages)){//вроде даже можно убить очередь раньше завершения и раньше отключения консюмера
//                $waitHelper = new Wait091();
//                $channel->queue_delete($queue_name);
//                $channel->wait(array($waitHelper->get_wait('basic.cancel')));
//            }
        
        }
       
        $channel->close();
        $this->_connection->close();
    
    
    }
}
