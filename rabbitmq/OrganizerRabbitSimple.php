<?php
//namespace SmartGenerator1;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class OrganizerRabbitClass {//implements Trigger

}

/*
 *
 */

class OrganizerRabbitSimple extends OrganizerRabbitClass {

    public $codes = [
        200 => 'NOTICE',

        304 => 'NOTICE',

        301 => 'NOTICE',

        400 => 'ERROR',

        401 => 'ERROR',

        402 => 'ERROR',

        403 => 'ERROR',

        500 => 'ERROR',

        501 => 'ERROR',
    ];
    /**
     * get message category for ERabbitmqLogRouter class for LogWriter queue
     *
     * @param $code
     *
     * @return string
     */
    public function getCategoryByCode($code = 0) {
        $category = 'notice';
        if (in_array(intval($code), array_keys($this->codes))) {
            $category = $this->codes[$code];
        }

        return strtoupper($category);
    }

    public function initializeConnection() {

        if(!isset(Yii::app()->params) || Yii::app()->params['deadRabbitHost']){
            return false;
        }

        $writeItToTheTable = false;
        $tempHost = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'rabbitmq') ;//localhost//127.0.0.1
        $url = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $tempHost) . ":15672";
        //        $host = isset(Yii::app()->params['RabbitServers']['Triggers']['host']) ? Yii::app()->params['RabbitServers']['Triggers']['host'] : '';
        //        $port = isset(Yii::app()->params['RabbitServers']['Triggers']['port']) ? Yii::app()->params['RabbitServers']['Triggers']['port'] : '';
        //        $url = $host.":".$port;
	    $url = 'rabbitmq:15672'; // new CF wildcard ssl rules
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$retInfo = curl_getinfo($ch);
        //dd($retcode,$url,$retInfo,$_SERVER);
        curl_close($ch);
        if (200 == $retcode
            //&& in_array('AMQPStreamConnection',get_declared_classes())
        ) {


            //            $value=Yii::app()->cache->get('simpleLogsChannel');
            //            if($value!==false)
            //            {
            //                Yii::app()->params['simpleLogsChannel'] = $value;
            //            } else

            if ($connection = new AMQPStreamConnection(Yii::app()->params['RabbitServers']['Triggers']['host'],
                                                       Yii::app()->params['RabbitServers']['Triggers']['port'],
                                                       Yii::app()->params['RabbitServers']['Triggers']['login'],
                                                       Yii::app()->params['RabbitServers']['Triggers']['password'])) {
                if ($channel = $connection->channel()) {
                    $channel->queue_declare('logs',    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
                                            false, #passive - может использоваться для проверки того, инициирован ли обмен, без того, чтобы изменять состояние сервера
                                            true, #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении - очередь переживёт перезагрузку брокера
                                            false, #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
                                            false        #autodelete - очередь удаляется, когда отписывается последний подписчик
                    );
                    Yii::app()->params['simpleLogsChannel'] = $channel;//for prove access from all point
                    //                        Yii::app()->cache->set('simpleLogsChannel', $channel);
                    //$this->_simpleLogsChannel = $channel;
                } else {
                    $writeItToTheTable = true;
                }
            } else {
                $writeItToTheTable = true;
            }
        } else {
            $writeItToTheTable = true;
            Yii::app()->params['deadRabbitHost'] = true;
        }

        if ($writeItToTheTable) {
            //$this->_simpleLogsChannel = false;
            Yii::app()->params['simpleLogsChannel'] = false;
            //here we have to write to the table api call logs
        }

    }

    /**
     * adding simple messages in stored channel from ERabbitmqLogRouter class
     *
     * @param array $msg
     *
     * @param string $queue
     *
     * @return bool
     */
    public function addItInRabbitQueueForLogs($msg = []) {

        if (!Yii::app()->params['simpleLogsChannel']){
            $this->initializeConnection();
        }

        if (is_array($msg)) {
            $msg = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode(json_encode(isset($msg) ? $msg : '')));
        }
        $queue = 'logs';// method named 'add It In Rabbit Queue For Logs' - because that queue
        if (Yii::app()->params['simpleLogsChannel']
            // && class_exists('AMQPMessage')
        ) {//if exemplar of class ERabbitmqLogRouter was initialized
            $body = json_encode($msg);
            $msg = new AMQPMessage($body, ['delivery_mode' => 2]);
            /*
            //'delivery_mode' => 2 - this is ampq constant for delivery_mode
                const DELIVERY_MODE_NON_PERSISTENT = 1;
                const DELIVERY_MODE_PERSISTENT = 2; //safety method fo use and store message
             */

            Yii::app()->params['simpleLogsChannel']->basic_publish($msg,            #сообщение
                                                                   '',                #обмен
                                                                   $queue    #ключ маршрутизации (очередь)
            );
        } else {
            //here we have to write to the table api call logs
        }

    }

    /**
     * return count of messages in non stored channel from ERabbitmqLogRouter class
     *
     * @param array $msg
     *
     *
     * @return int
     */
    public function countOfMessagesLongTermOperation($msg = [])
    {
        $res = 0;
        $queue = 'longTermOperation';
        if ($connection = new AMQPStreamConnection(
            Yii::app()->params['RabbitServers']['Triggers']['host'],
            Yii::app()->params['RabbitServers']['Triggers']['port'],
            Yii::app()->params['RabbitServers']['Triggers']['login'],
            Yii::app()->params['RabbitServers']['Triggers']['password'])
        ) {
            if ($channel = $connection->channel()) {
                $channel->queue_declare(
                    $queue,    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
                    false,
                    #passive - может использоваться для проверки того, инициирован ли обмен,
                    # без того, чтобы изменять состояние сервера
                    true,
                    #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении -
                    # очередь переживёт перезагрузку брокера
                    false,
                    #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
                    false        #autodelete - очередь удаляется, когда отписывается последний подписчик
                );
                if ($channel
                    // && class_exists('AMQPMessage')
                ) {
                    while ($result = $channel->basic_get($queue)) {
                        if (is_object($result)) {
                            $res++;
                        }
                    }
                    $channel->close();
                    $connection->close();
                }
            }
        }

        return $res;
    }

    /**
     * adding simple messages in non stored channel from ERabbitmqLogRouter class
     *
     * @param array $msg
     *
     *
     * @return bool
     */
    public function alreadyExistLongTermOperationRequest($msg = [])
    {
        $queue = 'longTermOperation';
        if ($connection = new AMQPStreamConnection(
            Yii::app()->params['RabbitServers']['Triggers']['host'],
            Yii::app()->params['RabbitServers']['Triggers']['port'],
            Yii::app()->params['RabbitServers']['Triggers']['login'],
            Yii::app()->params['RabbitServers']['Triggers']['password'])
        ) {
            if ($channel = $connection->channel()) {
                $channel->queue_declare(
                    $queue,    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
                    false,
                    #passive - может использоваться для проверки того,
                    # инициирован ли обмен, без того, чтобы изменять состояние сервера
                    true,
                    #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении -
                    # очередь переживёт перезагрузку брокера
                    false,
                    #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
                    false        #autodelete - очередь удаляется, когда отписывается последний подписчик
                );
                if ($channel
                    // && class_exists('AMQPMessage')
                ) {
                    while ($result = $channel->basic_get($queue)) {
                        if (is_object($result)) {
                            $queueMsg = json_decode($result->body, true);
                            if ($queueMsg['tenant'] == $msg['tenant'] and $queueMsg['params'] == $msg['params']) {
                                //dump($queueMsg, $msg);
                                return true;
                            }
                        }
                    }
                    $channel->close();
                    $connection->close();
                }
            }
        }

        //dd(1);
        return false;
    }

    /**
     * adding simple messages in non stored channel from ERabbitmqLogRouter class
     *
     * @param array $msg
     *
     *
     * @return void
     */
    public function addItInRabbitQueueForLongTermOperation($msg = [])
    {
        $queue = 'longTermOperation';
        if ($connection = new AMQPStreamConnection(
            Yii::app()->params['RabbitServers']['Triggers']['host'],
            Yii::app()->params['RabbitServers']['Triggers']['port'],
            Yii::app()->params['RabbitServers']['Triggers']['login'],
            Yii::app()->params['RabbitServers']['Triggers']['password'])
        ) {
            if ($channel = $connection->channel()) {
                $channel->queue_declare(
                    $queue,    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
                    false,
                    #passive - может использоваться для проверки того, инициирован ли обмен,
                    # без того, чтобы изменять состояние сервера
                    true,
                    #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении -
                    # очередь переживёт перезагрузку брокера
                    false,
                    #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
                    false        #autodelete - очередь удаляется, когда отписывается последний подписчик
                );
                if ($channel
                    // && class_exists('AMQPMessage')
                ) {//if exemplar of class ERabbitmqLogRouter was initialized
                    $msg = json_encode($msg);
                    if (is_array($msg)) {
                        $msg = iconv(
                            'UTF-8', 'UTF-8//IGNORE', utf8_encode($msg));
                    }
                    $msg = new AMQPMessage($msg, ['delivery_mode' => 2]);
                    /*
                    //'delivery_mode' => 2 - this is ampq constant for delivery_mode
                        const DELIVERY_MODE_NON_PERSISTENT = 1;
                        const DELIVERY_MODE_PERSISTENT = 2; //safety method fo use and store message
                     */

                    $channel->basic_publish(
                        $msg,            #сообщение
                        '',                #обмен
                        $queue    #ключ маршрутизации (очередь)
                    );
                } else {
                    //here we have to write to the table api call logs
                }
            }
        }

    }



    /**
     * The function to setup default pwaless app bots
     * @param array $data
     */
    public function addCloneTenantOnCreate(array $data)
    {
        $msg = [
            'operation' => 'createClonet33tenant',
            'tenant' => 'master',
            'fields' => $data,
        ];

        $this->addItInRabbitQueueForLongTermOperation($msg);
    }


    /**
     * push message "copyAppication" to queue longTermOperation
     *
     * @param array $fields
     *
     *
     * @return array
     */
    public function pushCopyAppication($fields)
    {
        $msg = [
            "operation" => 'copyApplication',
            "tenant" => $fields['destination_tenant'],
            "params" => [
                "source_tenant" => $fields['source_tenant'],
                "destination_tenant" => $fields['destination_tenant'],
                "source_application_id" => $fields['source_application_id'],
            ],
            "fields" => $fields,
        ];
        $before = '';
        //check queue of existing same message
        if (!$this->alreadyExistLongTermOperationRequest($msg)) {

            $countMsgInQueue = $this->countOfMessagesLongTermOperation($msg);

            $this->addItInRabbitQueueForLongTermOperation($msg);

            $notifcation = "yours application will be copied nearest time";

            if ($countMsgInQueue == 0) {
                $notifcation = "copy of yours application is started";
            } else {
                $before = " (" . $countMsgInQueue . " messages before in this queue)";
            }

            $temp = '{"data": 
                [{
                    "type": "applications",
                    "attributes": "' . $before . '",
                    "message":"' . $notifcation . '"
                }]
            }';

            $response = CJSON::decode($temp, true);

            return ['code' => 200, 'response' => $response];
        } else {

            $notifcation = 'action by copy this application already exist in queue';

            $temp = '{"data": 
                [{
                    "type": "applications",
                    "decline": "true",
                    "attributes": "' . $before . '",
                    "message":"' . $notifcation . '"
                }]
            }';

            $response = CJSON::decode($temp, true);

            return ['code' => 200, 'response' => $response];
        }

    }

    /**  don't used now
     * adding messages in special table for latest sending it in same queue (is's alternative for method addItInRabbitQueueForLogs)
     *
     * @param array $msg
     *
     * @param string $queue
     *
     * @return bool
     */
    //    public function addItInTableForRabbitByCorne($msg = [], $queue = 'TriggerExecutor') {
    //        $tableLogsApi = 'api_call_log';
    //        if (is_array($msg)) {
    //            $msg = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode(json_encode(isset($msg) ? $msg : '')));
    //        }
    //        $res = Yii::app()->db->createCommand()
    //            ->insert($tableLogsApi, [
    //                //'queryParamsjs' => $msg,
    //                'queryParams' => $msg,
    //                'queue' => $queue,
    //
    //            ]);
    //    }

}
