# logsForProjects




-----BEGIN PGP MESSAGE-----

jA0ECQMCPh5KJXGkuh/p0sC0AYEhV/n4Qe0Rpwa6yLV7Cw68J8Z7VD+AL0IbsxNT
XU6Hj3ZZ7TY5RV4doz/Q0CltrIjfxRqG9PITpPpr3lbpiEJKnPO0l35KSlMdypz4
4ZrWRQjJN7aCn+bTfjA6PPcFoTwlS/c3n/y0UeImdoSWtrGmmfpJT9AXvIZwsEv6
vOWQrD7n3FSgUKPIb5/MkWHGlcUtjHx1l5+Fx9gArJMNKyYYMeJpGBxFY2ucr9+K
hyked1A5JmKYsU59Hxd4ZGNensS7aaSnozo6l3RxTBSqRZ5WktmCzrSW0Q7vJsqq
uIJxGyY2ZbxLAEIP5gP813mv4CGFflZUgl43XIxwqqSniJ5pggTaTjPz6zS3l4Ui
6S+oXaXuvg7sVVUl3A+F73anoedesuLOEfwdj+FhtcUfjXsSW99urwmEhMWL1SJE
W9TORQcC6Bv4Rz8c/0R+cScHJc6Eb5FiEth1BkSuGWXQTc6XVvAZcBtK6d/W3j6c
9XJvzE7N
=1VxE
-----END PGP MESSAGE-----

passphrase jopa


//p1
defined('API_URL_DATADOG_LOGS') || define('API_URL_DATADOG_LOGS', 'https://http-intake.logs.datadoghq.com/v1/input/');
defined('API_TOKEN_DATADOG_LOGS') || define('API_TOKEN_DATADOG_LOGS', 'blabla');
defined('dataDogUrlForGettingLog') || define(
    'dataDogUrlForGettingLog', 'https://api.datadoghq.com/api/v1/logs-queries/list');
    defined('dataDogApiKey') || define('dataDogApiKey', 'blabla2');
    defined('dataDogApplicationKey') || define('dataDogApplicationKey', 'blabla3');
    defined('limitOfCountRecordsForRequestFromDataDog') || define('limitOfCountRecordsForRequestFromDataDog', 1000);
    
    
    //p2
              array(
                        'class' => 'ERabbitmqLogRouter',
                        'filter' => 'ERabbitmqLogFilter',
                        'levels'=>'warning, error',
                        'enabled' => true,
                    ),
                    
                    
   //p3
   
   //p31
    public function error($code, $message) : array
    {
        return [
            'code' => $code,
            'body' => [
                'errors' => [
                    Valid::failValidate(
                        $code, $code, Yii::t('api', 'Error'), $message),
                ],
            ],
        ];
    }

   //p32
    public function makeDataDogQueryString($params) : string
    {
        $currentTenant = 'tenant1';
        $queryString = "($currentTenant || host:$currentTenant-fail*)";
        $possibleAmpq = '';
        $showErrors = '';
        if ($params['getErrors'] == 1) {
            $showErrors = 'status:error';
        }
        $showExternalApi = '';
        if ($params['getExternalQueries'] == 1) {
            $showExternalApi = 'service:(  \"external api\" OR \"executeOperation incoming\") ';
            if ($params['getErrors'] == 1) {
                $possibleAmpq = '||';
            }
        }

        $queryString = "($showErrors $possibleAmpq $showExternalApi) " . $queryString;
        
        return '{ 
             "query": " ' . $queryString . ' ",
             "time": {
             "from": "now - ' . $params['countHour'] . 'h",
             "to": "now"
              },
             "sort": "desc",
             "limit": "' . $params['limit'] . '"
             }';
    }

   //p33
    public function getJsonFromDataDogServer($jsonQuery) : object
    {

        $ch = curl_init();

        curl_setopt_array(
            $ch, [
            CURLOPT_URL => constant('dataDogUrlForGettingLog'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonQuery,
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Cache-Control: no-cache",
                "Content-Length: " . strlen($jsonQuery),
                "DD-API-KEY: " . constant('dataDogApiKey'),
                "DD-APPLICATION-KEY: " . constant('dataDogApplicationKey'),
                "cache-control: no-cache",
                "content-type: application/json; charset=utf-8",
            ],
        ]);

        $res = curl_exec($ch);
        $resultInfo = curl_getinfo($ch);
        $err = curl_error($ch);

        if ($resultInfo['http_code'] == 200 && $res != false) {
            $res = json_decode($res);
        } else {
            $res = json_decode([]);
            Yii::log('getTenantLogs fail on curl', \CLogger::LEVEL_ERROR);
            Yii::log($err, \CLogger::LEVEL_ERROR);
        }

        return $res;
    }

   //p34
    public function filterlogs(&$logs)
    {

        if (!isset($logs->logs)) {
            return [];
        }
        foreach ($logs->logs as $index => $log) {
            $service = $log->content->service ?? null;
            if (!in_array(
                $service, [
                'yii_log',
                'external api',
                'webHookNko',
            ])
            ) {
                $logs->logs[$index] = null;
            }
            $message = $log->content->attributes->message_text ?? null;
            $messagePlace = $logs->logs[$index]->content->attributes->message_text ?? null;
            if ($message && $messagePlace) {
                if ($message) {
                    $message = preg_replace('/(.*\/)(.+)[:.]{1,1}/', '', $message);
                }
                $logs->logs[$index]->content->attributes->message_text = $message;
            }

            $message = $log->content->message ?? null;
            $messagePlace = $logs->logs[$index]->content->message ?? null;
            if ($message && $messagePlace) {
                $message = preg_replace('/(.*\/)(.+)[:.]{1,1}/', '', $message);
                if ($message) {
                    $logs->logs[$index]->content->message = $message;
                }
            }

        }
    }

   //p35
    public function getTenantLogs(EDbConnection $connection, array $params) : array
    {
        $constraint = new Assert\Collection(
            [
                'countHour' => [
                    new Assert\NotBlank(),
                    new Assert\Range(
                        [
                            'min' => 1,
                            'max' => constant('maxPeriodOfRequestInHours'),
                            'minMessage' => 'You must be at least {{ limit }} tall to enter',
                            'maxMessage' => 'You cannot be gather than {{ limit }} to enter',
                        ]),
                ],
                'getErrors' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(
                        [
                            'pattern' => '/^[0-1]$/',
                            'message' => 'This value must be between 0 and 1',
                        ]),
                ],
                'getExternalQueries' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(
                        [
                            'pattern' => '/^[0-1]$/',
                            'message' => 'This value must be between 0 and 1',
                        ]),
                ],
                'limit' => [
                    new Assert\NotBlank(),
                    new Assert\Range(
                        [
                            'min' => 1,
                            'max' => constant('limitOfCountRecordsForRequestFromDataDog'),
                            'minMessage' => 'You must be at least {{ limit }} tall to enter',
                            'maxMessage' => 'You cannot be gather than {{ limit }} to enter',
                        ]),
                ],
            ]);
        $fields = $params;
        $errors = $this->globalValidator($fields, $constraint);
        if (is_array($errors)) {
            return $errors;
        }
        $res = [];
        if (($fields['getExternalQueries'] + $fields['getErrors']) > 0) {

            $jsonStr = $this->makeDataDogQueryString($fields);

            $res = $this->getJsonFromDataDogServer($jsonStr);
            if ($params['getErrors'] == '1') {
                $this->filterlogs($res);
            }
        } else {
            $msg = 'One of params getErrors or getExternalQueries must be equal 1';
            Yii::log($msg, 'error');

            return $this->error(400, $msg);
        }

        return [
            'code' => 200,
            'body' => [
                'data' => $res,
            ],
        ];
    }
    
    
    
    
public static function clearCyclesLnkAndCache() {

        if (!isset(self::$last_gc_cycle)) {
            self::$last_gc_cycle = time() - (24 * 3600);
            //echo 'start geting gc_collect_cycles();';
        }

        if (function_exists('gc_collect_cycles')) {
            $time = time();
            if ($time - self::$last_gc_cycle > 300) {//300 - could be like system constants
                //every 5 minutes we make
                self::$last_gc_cycle = $time;
                gc_collect_cycles();
                //echo 'start gc_collect_cycles();';
                if (PHP_VERSION_ID >= 70000) {
                    if (function_exists('gc_mem_caches')) {
                        gc_mem_caches();
                    }
                }
            }
            //echo self::$last_gc_cycle.'||';

        }

        //force run gc
        //        if (function_exists('gc_collect_cycles')) {
        //            gc_collect_cycles();
        //            if (PHP_VERSION_ID >= 70000) {
        //                gc_mem_caches();
        //            }
        //        }

    }
    

public function actionStart() {//rabbit function
 $receiver = new RabbitMessages($conf['RabbitServers']['Triggers']['host'],
                                      conf['port'],
                                      conf['login'],
                                      conf['password'],
                                      'forListener',
                                      'exchListener');
                               
        $callback = function($msg)
        {
            $bodyJson = json_decode($msg->body, true);
            Utils::clearCyclesLnkAndCache();

            try {
                $curl = curl_init();
                $curlopt_url = API_URL_DATADOG_LOGS . API_TOKEN_DATADOG_LOGS;
    
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

                //$bodyJson example - '{"message":"json formatted log", "ddtags":"env:my-env,user:my-user", "ddsource":"my-integration", "hostname":"my-hostname", "portal":"my-hostname", "service":"my-service"}';
         

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
                }

            } catch (Throwable $t) {
                $this->writeItInBase([$t->getMessage()]);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $receiver->setCallback($callback);
        $receiver->listen('logs');

    }       
                                      
                                      
