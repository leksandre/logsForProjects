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
class longTermExecutorCommand extends CConsoleCommand
{
    public function __destruct()
    {
    }

    public function init()
    {
    }

    public function actionStart()
    {

        $receiver = new RabbitMessages(
            Yii::app()->params['RabbitServers']['Triggers']['host'],
            Yii::app()->params['RabbitServers']['Triggers']['port'],
            Yii::app()->params['RabbitServers']['Triggers']['login'],
            Yii::app()->params['RabbitServers']['Triggers']['password'],
            'forListener', 'exchListener');

        $callback = function ($msg)
        {
            $body = json_decode($msg->body, true);
            //echo $bodyJson;
            Utils::clearCyclesLnkAndCache();

            try {

                dump($body);
                //dd($body);

                $connectionItem = Tenants::getTenantDbConnection($body['tenant']);
                Yii::app()
                    ->setComponent('db', $connectionItem);
                Yii::app()->db->setActive(false);
                Yii::app()->db->setActive(true);


                $tenantList = Yii::app()->getComponent('tenantList_db');
                //$lastIdLogs = 0;
                //dd(Yii::app()->params['currentServerParams']['url']);
                //$MBST_SERVER = getenv('MBST_SERVER', true) ?: getenv('MBST_SERVER');
                //dd(Yii::app()->params,$MBST_SERVER);

                if ($connectionItem) {

                    if ($tenantList) {
                        $tenantList->createCommand()
                            ->insert(
                                'long_operation_logs', [
                                'operation' => $body['operation'],
                                'message' => json_encode($body),
                                'tenant' => $body['tenant'],
                                //'date_start' => date("Y-m-d H:i:s"),
                            ]);
                        //$lastIdLogs = $tenantList->getLastInsertID('long_operation_logs_id_seq');

                    }

                    if ($body['operation'] == 'importObjectsFromFile') {
                        $controller = new ObjectsController('');
                        $res = $controller->actionStartImportObjects($body['fields']);
                        dump($res);
                    }
                    if ($body['operation'] == 'copyApplication') {
                        $controllerApi8 = new Restapi8Controller('');
                        $res = $controllerApi8->copyApplication($connectionItem, $body['fields']);
                        dump($res);
                    }
                    if ($body['operation'] == 'CheckAndUpdateYml') {
                        $YmlGetter = new SimpleTreadYmlGetter(
                            $body['fields']['appId'], $connectionItem, $body['fields']['tenant']);
                        $res = $YmlGetter->CheckAndUpdateYml($body['fields']['listName']);
                        dump($res);
                    }
                    if ($body['operation'] == 'addDefaultInsalesWidgets') {
                        $funcName = $body['operation'];
                        $this->$funcName($body);
                    }



                    if ($body['operation'] == 'createClonet33tenant') {
                        $controllerApi8 = new Restapi8RegistrationController('');
                        $res = $controllerApi8->createColneTenant($connectionItem, $body['fields']);
                        dump($res);
                    }


                    
//                    if($body['operation']=='copyListBetweenApp'){
//                        $controllerListApi8 = new Restapi8listController('');
//
//                        $connectionItemD = $connectionItem;
//                        $connectionItemS = $connectionItem;
//
////                        if($body['destination_tenant']){
////                            $connectionItemD = Tenants::getTenantDbConnection($body['fields']['destination_tenant']);
////                        }
////                        if($body['source_tenant']){
////                            $connectionItemS = Tenants::getTenantDbConnection($body['fields']['source_tenant']);
////                        }
//
//                        $res = $controllerListApi8->copyListBetweenApp(
//                            $connectionItemS, $connectionItemD, $body['fields']);
//                        dump($res);
//                    }

                    if ($tenantList) {
                        $tenantList->createCommand()
                            ->update(
                                'long_operation_logs', [
                                'date_end' => 'now()',
                                'result' => json_encode($res ?? ''),
                            ], ' "operation"=:operation and "message"=:message and "tenant"=:tenant ', [
                                ':operation' => $body['operation'],
                                ':message' => json_encode($body),
                                ':tenant' => $body['tenant'],
                            ]);
                    }


                }

            } catch (Throwable $t) {
                dump($t->getMessage());
                //return false;//skip it in error case
            }
            //sleep(constant('delay_between_operation'));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            //sleep(constant('delay_between_operation'));
        };

        $receiver->setCallback($callback);
        $receiver->listen('longTermOperation');

    }

    /**
     * The function to setup default pwaless app bots
     * @param $body
     * @throws CException
     */
    public function addDefaultInsalesWidgets($body)
    {
        $bots = $body['fields']['bots'] ?? null;
        $user = $body['fields']['user'] ?? [];
        $tenantName = $body['tenant'] ?? '';
        $host = $body['host'] ?? '';

        Yii::app()->params['client_portal'] = $tenantName;
        Yii::app()->params['host'] = $host;

        $connection = Tenants::getTenantDbConnection($tenantName);
        Yii::app()->setComponent('db', $connection);
        Yii::app()->db->setActive(false);
        Yii::app()->db->setActive(true);

        if (isset($bots)) {
            $restApi5Controller = new Restapi5Controller('');

            foreach ($bots as $index => $bot) {
                EController::createEmptyEcosystemsFiles();

                $tokenAlreadyUsed = $restApi5Controller->changeParamsIfBotsExist($bot);
                $restApi5Controller->createBot($bot, $user);

                $botsType = $index;
                $resBots = $restApi5Controller->generateBots($user, $botsType, Yii::app()->tenantList_db, $bot['TenantName']);

            }
        }
    }

}