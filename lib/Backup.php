<?php

class Backup {

	protected $environment;

	protected $settings = array(
		'compress' => 'None',
		'add-drop-database' => true,
		'add-drop-table' => true,
		'single-transaction' => true,
		'lock-tables' => true,
		'add-locks' => true,
		'extended-insert' => true,
		'disable-foreign-keys-check' => false,
	);

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getEnvironment()
	{
		return $this->environment;
	}

	public function getSettings()
	{
		return $this->settings;
	}



	public function makeFilePath($url)
	{
		$folder = $this->environment->getShared('backup_path');

		return $folder . '/data_' . str_replace('/', '_', $url) . '.sql';
	}




	public function makeDumper()
	{
		$configs = $this->environment->getDatabase();

		extract($configs);

		$settings = $this->getSettings();

		return new Mysqldump($db_name, $db_user, $db_password, $db_host, 'mysql', $settings);
	}

	public function backup()
	{
		$url = $this->environment->getUrlFor();

		$dumper = $this->makeDumper();

		$path = $this->makeFilePath($url);

		$dumper->start($path);

		return true;
	}





	public function makeMigrator()
	{
		return new Migrator;
	}

	public function migrateFromAndCleanup($environment)
	{
		$url = $this->environment->getUrlFor($environment);

		$source = $this->makeFilePath($url);

		$migrator = $this->makeMigrator();

		if (!$code = $migrator->readFile($source))
		{
			throw new RuntimeException('Could not read file [' . $source . ']');
		}

		$current = $this->environment->getConfigs($environment);

		$configs = $this->environment->getConfigs();

		foreach ($configs as $config)
		{
			$migrated = $migrator->migrate($code, $current, $config);

			$destination = $this->makeFilePath($config['url']);

			$migrator->writeFile($destination, $migrated);
		}

		return true;
	}



	public function makeMysqli()
	{
		$configs = $this->environment->getDatabase();

		extract($configs);

		$mysqli = @mysqli_connect($db_host, $db_user, $db_password, $db_name);

		if (mysqli_connect_error())
		{
			throw new RuntimeException('Error connecting to database [' . $db_name . '@' . $db_host . '] with [' . $db_user . ']');
		}

		return $mysqli;
	}

	public function restore()
	{
		$url = $this->environment->getUrlFor();

		$source = $this->makeFilePath($url);

		$migrator = $this->makeMigrator();

		if (!$code = $migrator->readFile($source))
		{
			throw new RuntimeException('Could not read file [' . $source . ']');
		}

		$mysqli = $this->makeMysqli();

		if ($mysqli->multi_query($code))
		{
			while ($mysqli->next_result())
			{
			}
		}

		$mysqli->close();

		if ($mysqli->errno)
		{
			throw new RuntimeException('Error executing restore script.');
		}

		return true;
	}


}