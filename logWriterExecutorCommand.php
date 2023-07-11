<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
$root = $root = dirname(__FILE__);
Yii::import('application.extensions.components.KCliColor');
Yii::import('application.vendor.rapidapi');

defined('API_URL_DATADOG_LOGS') || define('API_URL_DATADOG_LOGS', 'https://http-intake.logs.datadoghq.com/v1/input/');
//depend on docs https://docs.datadoghq.com/api/?lang=bash#create-embed

defined('API_TOKEN_DATADOG_LOGS') || define('API_TOKEN_DATADOG_LOGS', 'blabla');
//create it on https://app.datadoghq.com/account/settings#api
//in "Client Tokens"

$root = str_replace(DIRECTORY_SEPARATOR . "commands", "", $root);
require_once($root . '/app/lib/vendor/rapidapi/rapidapi-connect/src/RapidApi/RapidApiConnect.php'); // Такой вызов необходимо делать ДО погрузки Yii так как у него свой автозагрузчик
require_once($root . '/app/lib/vendor/rapidapi/rapidapi-connect/src/RapidApi/Utils/HttpInstance.php'); // Такой вызов необходимо делать ДО погрузки Yii так как у него свой автозагрузчик

require_once($root . '/app/extensions/liveTrigger/OrganizerTriggerClass.php');

//require_once($root . '/app/extensions/liveTrigger/TriggerClass.php');
use RapidApi\RapidApiConnect;

// start with command: docker exec -t mbst_php72 php /home/www/mobsted/boiler/yiic logWriterExecutor Start
class logWriterExecutorCommand extends CConsoleCommand {
    public function __destruct() {
    }

    public function init() {
    }

    public function actionStart() {

        $receiver = new RabbitMessages(Yii::app()->params['RabbitServers']['Triggers']['host'],
                                      Yii::app()->params['RabbitServers']['Triggers']['port'],
                                      Yii::app()->params['RabbitServers']['Triggers']['login'],
                                      Yii::app()->params['RabbitServers']['Triggers']['password'],
                                      'forListener',
                                      'exchListener');

        $callback = function($msg)
        {
            $bodyJson = json_decode($msg->body, true);
            //echo $bodyJson;
            Utils::clearCyclesLnkAndCache();

            //            $handle = curl_init($url);
            //            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            //            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
            //            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            //            exec_curl_request($handle);

            try {
                $curl = curl_init();
                $curlopt_url = API_URL_DATADOG_LOGS . API_TOKEN_DATADOG_LOGS;
                //echo $curlopt_url;
                curl_setopt_array($curl, [
                    CURLOPT_URL => $curlopt_url,
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                ]);

                //$bodyJson = '{"message":"json formatted log", "ddtags":"env:my-env,user:my-user", "ddsource":"my-integration", "hostname":"my-hostname", "portal":"my-hostname",
                // "service":"my-service"}';
                //$bodyJson = http_build_query($bodyJson);

                curl_setopt($curl, CURLOPT_POSTFIELDS, $bodyJson);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                //dump($response);
                //dump($err);
                $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                //dump($httpStatus);
                curl_close($curl);
                if (intval($httpStatus) == "200") {
                } else {
                    $this->writeItInBase($err, $response, $bodyJson);
                    //return false;
                }

            } catch (Throwable $t) {
                //echo "\n !! " . $t->getMessage();
                //do not use Yii::log here, never
                //!!!!! never use it here Yii::log($t->getMessage());//

                $this->writeItInBase([$t->getMessage()]);
                //return false;
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $receiver->setCallback($callback);
        $receiver->listen('logs');

    }

    public function writeItInBase($errors = [], $response, $jsonWithData) {
        ///some thing have to be here
    }

}