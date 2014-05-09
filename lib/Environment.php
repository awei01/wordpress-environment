<?php

class Environment {

	protected static $instance;

	protected $configs;

	protected $shared;

	protected $domain;

	protected $environment;

	protected $database;

	protected $error;

	public function __construct(array $configs)
	{
		$this->configs = $configs;
	}

	public function getConfigs($env = null)
	{
		$result = $this->configs;

		if ($env)
		{
			$result = $result[$env];
		}

		return $result;
	}

	public function getShared($key = null)
	{
		$result = $this->shared;

		if ($key)
		{
			$result = $result[$key];
		}

		return $result;
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function getEnvironment()
	{
		return $this->environment;
	}

	public function getDatabase($key = null)
	{
		$result = $this->database;

		if ($key)
		{
			$result = $result[$key];
		}

		return $result;
	}

	public function getError()
	{
		return $this->error;
	}

	public function boot()
	{
		if (static::$instance)
		{
			throw new LogicException('Environment already booted');
		}

		$this->setDomain();

		$this->extractAndSetShared();

		$this->setEnvironment();

		static::$instance = $this;
	}

	protected function setDomain()
	{
		$this->domain = $this->getCurrentDomain();
	}

	protected function extractAndSetShared()
	{
		$this->shared = $this->configs['shared'];

		unset($this->configs['shared']);
	}

	protected function getCurrentDomain()
	{
		return $_SERVER['HTTP_HOST'];
	}

	protected function setEnvironment()
	{
		foreach ($this->configs as $env => $configs)
		{
			$parts = explode('/', $configs['url']);

			$url = $parts[0];

			if ($url === $this->domain)
			{
				$this->environment = $env;

				$this->database = $configs['database'];

				return;
			}
		}

		throw new RuntimeException('No configuration found for domain [' . $this->domain . ']');
	}

	public function makeUrl()
	{
		return 'http://' . $this->domain;
	}



	public function getUrlFor($environment = null)
	{
		$environment = $environment ? : $this->getEnvironment();

		return $this->configs[$environment]['url'];
	}

	public static function getInstance()
	{
		return static::$instance;
	}

	public function makeBackup()
	{
		return new Backup($this);
	}

	public function backupDatabase()
	{
		$backup = $this->makeBackup();

		try
		{
			$backup->backup();

			$backup->migrateFromAndCleanup($this->getEnvironment());
		}
		catch (Exception $exception)
		{
			$this->setError($exception);

			return false;
		}

		return true;
	}

	public function restoreDatabase()
	{
		$backup = $this->makeBackup();

		try
		{
			$backup->restore();
		}
		catch (Exception $exception)
		{
			$this->setError($exception);

			return false;
		}

		return true;
	}

	protected function setError(Exception $exception)
	{
		$this->error = $exception->getMessage();
	}
}