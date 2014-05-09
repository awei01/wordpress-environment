<?php

return array(

	'shared' => array(
		// this defines where your backups are going to be stored. Currently defaults to /projectroot/backups
		'backup_path' => __DIR__ . '/../backups',
	),

	// copy the following block for each enviornment you want to support
	/*
	'environment' => array(

		'url' => 'environment.url.com',

		'database' => array(
			'db_name' => 'environment database name',
			'db_user' => 'environment database username',
			'db_password' => 'environment database password',
			'db_host' => 'environment host'

			// uncomment the following line if your server does not allow DROP DATABASE statements
			// 'no_drop' => true
		),

		)
	),
	*/

	// configs for local enviornment
	'local' => array(

		'url' => 'someurl.local',

		'database' => array(
			'db_name' => 'local database name',
			'db_user' => 'local database username',
			'db_password' => 'local database password',
			'db_host' => 'localhost'
		),

	),

);