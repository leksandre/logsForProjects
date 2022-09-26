<?php
	defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('userId_for_bots') || define('userId_for_bots', -9);
    defined('delay_between_operation') || define('delay_between_operation', 300);

	$projectRoot = dirname(__FILE__,4);
	$_apppath= dirname(__DIR__,2);
	$console_config = array(

		'basePath' =>  $_apppath. '/app',
		'aliases' => array(
			'application' => $_apppath. '/app',
			'bootstrap' => $_apppath. '/app/lib/vendor/2amigos/yiistrap',
			'yiiwheels' => $_apppath . '/app/lib/vendor/2amigos/yiiwheels',
			'vendor' => 'application.lib.vendor',
			'megre' => $_apppath . '/migrations/',
		),
		'components' => array(
			'cliColor' => array(
				'class' => 'application.extensions.components.KCliColor'
			),
			'log' => array(
				'class' => 'CLogRouter',
				'routes' => array(




                    array(
                        'class' => 'ERabbitmqLogRouter',
                        'filter' => 'ERabbitmqLogFilter',
                        'levels'=>'warning, error',
                        'enabled' => true,
                    ),


				),
			),


		),
		'commandMap' => array(
			'migrate' => array(
				'class' => 'system.cli.commands.MigrateCommand',
				'migrationPath' => 'megre',
				'migrationTable' => 'tbl_migration',
				'connectionID' => 'db',
				'templateFile' => 'megre.template',
			),
		),
		'params' => array(
			'tenantsInBatch' => 2,
			'productionDatabaseServer' => '188.225.76.68',
			'productionServer' => '',

			'folderProduction_A_Path' => $projectRoot,
			'folderProduction_B_Path' => '/home/www/production_b',

			'gitBinaryPath' => '/usr/bin/git',
			'gitRepositoryName' => '',
			'gitRepositoryFolder' => '/home/www/mobsted/',
			'gitServerName' => '',
			'gitUserName' => '',
			'gitPassword' => '',


			'nginxConfigFilePath' => '/etc/nginx/nginx.conf',
			'nginxBinaryPath' => '/usr/sbin/nginx',
			'httpdConfigFilePath' => '/etc/httpd/conf/httpd.conf',

			'tenantsProduction_A_ConfigPath' => $projectRoot . '/boiler/app/tenants',
			'tenantsProduction_A_FilesPath' => $projectRoot . '/boiler/www/tenants',

			'tenantsProduction_B_ConfigPath' => '/home/www/mobsted/boiler/app/tenants',
			'tenantsProduction_B_FilesPath' => '/home/www/mobsted/boiler/www/tenants',

			'yiicProduction_A_Path' => $projectRoot . '/boiler/yiic',
			'yiicProduction_B_Path' => '/home/www/mobsted/boiler/yiic',

			'databaseScriptFilePath' => APPPATH . '/data/postgres_schema.sql',
		),
	);
