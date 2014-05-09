<?php

class EnvironmentTest extends TestCase {

	protected function fakeConfigs()
	{
		return array(

			'shared' => array(
				'backup_path' => '/path/to/backup',
				'table_prefix' => 'prefix',
			),

			'production' => array(
				'url' => 'production.com',

				'database' => array(
					'db_name' => 'production db',
				),
			),

			'staging' => array(
				'url' => 'staging.com/folder',

				'database' => array(
					'db_name' => 'staging db',
				)
			),

			'local' => array(
				'url' => 'domain.local',

				'database' => array(
					'db_name' => 'local db',
				),
			),

		);
	}

	function test_construct_ConfigArray_SetsConfigs()
	{
		$configs = $this->fakeConfigs();
		$env = new Environment($configs);

		$result = $env->getConfigs();

		$this->assertEquals($configs, $result);
	}

	protected function fakeEnvironment()
	{
		$configs = $this->fakeConfigs();

		$env = new FakeEnvironment($configs);
		$env->fakeDomain = 'domain.local';

		$env->clearInstance();

		return $env;
	}

	function test_boot_NoParams_SetsInstanceOnStatic()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$result = FakeEnvironment::getInstance();

		$this->assertEquals($env, $result);
	}

	function test_boot_CalledTwice_ThrowsLogicException()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$this->setExpectedException('LogicException');

		$env->boot();
	}

	function test_boot_NoParams_SetsShared()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$result = $env->getShared();

		$this->assertEquals(array(
			'backup_path' => '/path/to/backup',
			'table_prefix' => 'prefix',
		), $result);
	}

	function test_boot_NoParams_SetsDomain()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$result = $env->getDomain();

		$this->assertEquals('domain.local', $result);
	}

	function test_boot_NoParams_SetsEnvironment()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$result = $env->getEnvironment();

		$this->assertEquals('local', $result);
	}

	function test_boot_DomainWithFilePath_SetsEnvironment()
	{
		$env = $this->fakeEnvironment();
		$env->fakeDomain = 'staging.com';
		$env->boot();

		$result = $env->getEnvironment();

		$this->assertEquals('staging', $result);
	}

	function test_boot_DomainNotFoundInConfigs_ThrowsRuntimeException()
	{
		$env = $this->fakeEnvironment();
		$env->fakeDomain = 'notfounddomain';

		$this->setExpectedException('RuntimeException');

		$env->boot();
	}

	function test_boot_NoParams_SetsDatabase()
	{
		$env = $this->fakeEnvironment();
		$env->boot();

		$result = $env->getDatabase();

		$this->assertEquals(array('db_name' => 'local db'), $result);
	}

	protected function fakeBootedEnvironment()
	{
		$env = $this->fakeEnvironment();

		$env->boot();

		return $env;
	}

	function test_getDatabase_AfterBooted_ReturnsAllDatabaseConfigs()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getDatabase();

		$this->assertEquals(array('db_name' => 'local db'), $result);
	}

	function test_getDatabase_Key_ReturnsValueOfKey()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getDatabase('db_name');

		$this->assertEquals('local db', $result);
	}

	function test_makeUrl_NoParams_ReturnsUrlPrefixedWithHttp()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->makeUrl();

		$this->assertEquals('http://domain.local', $result);
	}

	function test_getShared_WithKey_ReturnsTheSharedConfigForThatKey()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getShared('backup_path');

		$this->assertEquals('/path/to/backup', $result);
	}

	function test_getConfigs_WithKey_ReturnsConfigsForThatKey()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getConfigs('staging');

		$this->assertEquals(array(
			'url' => 'staging.com/folder',

			'database' => array(
				'db_name' => 'staging db',
			)
		), $result);
	}

	function test_getUrlFor_NoParams_ReturnsUrlForCurrentEnvironment()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getUrlFor();

		$this->assertEquals('domain.local', $result);
	}

	function test_getUrlFor_ValidEnvironment_ReturnsUrlForTheEnvironment()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->getUrlFor('staging');

		$this->assertEquals('staging.com/folder', $result);
	}

	function test_makeBackup_NoParams_ReturnsInstanceOfBackup()
	{
		$env = $this->fakeBootedEnvironment();

		$result = $env->makeBackup();

		$this->assertInstanceOf('Backup', $result);
	}

	protected function fakeEnvironmentWithBackup()
	{
		$env = $this->fakeBootedEnvironment();

		$fakeBackup = $env->fakeBackup = $this->fakeClass('Backup');

		return array($env, $fakeBackup);
	}

	function test_backupDatabase_NoParams_CallsBackupOnBackupNoArgs()
	{
		list($env, $mockBackup) = $this->fakeEnvironmentWithBackup();

		$mockBackup->shouldReceive('backup')->once()->withNoArgs();

		$env->backupDatabase();
	}

	function test_backupDatabase_backupOnBackupThrowsException_SetsError()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
		$exception = new Exception('backup error');
		$stubBackup->shouldReceive('backup')->andThrow($exception);
		$env->backupDatabase();

		$result = $env->getError();

		$this->assertEquals('backup error', $result);
	}

	function test_backupDatabase_backupOnBackupThrowsException_ReturnsFalse()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
		$exception = new Exception('backup error');
		$stubBackup->shouldReceive('backup')->andThrow($exception);

		$result = $env->backupDatabase();

		$this->assertFalse($result);
	}

	function test_backupDatabase_backupOnBackupDoesNotThrowError_CallsMigrateFromAndCleanupOnBackupWithEnvironment()
	{
		list($env, $mockBackup) = $this->fakeEnvironmentWithBackup();

		$mockBackup->shouldReceive('migrateFromAndCleanup')->once()->with('local');

		$env->backupDatabase();
	}

	function test_backupDatabase_migrateFromAndCleanupBackupThrowsError_SetsError()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
			$exception = new Exception('migrateFromAndCleanup error');
		$stubBackup->shouldReceive('migrateFromAndCleanup')->andThrow($exception);
		$env->backupDatabase();

		$result = $env->getError();

		$this->assertEquals('migrateFromAndCleanup error', $result);
	}

	function test_backupDatabase_migrateFromAndCleanupBackupThrowsError_ReturnsFalse()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
			$exception = new Exception('migrateFromAndCleanup error');
		$stubBackup->shouldReceive('migrateFromAndCleanup')->andThrow($exception);

		$result = $env->backupDatabase();

		$this->assertFalse($result);
	}

	function test_backupDatabase_backupAndMigrateFromAndCleanupOnBackupDontThrowErrors_ReturnsTrue()
	{
		list($env) = $this->fakeEnvironmentWithBackup();

		$result = $env->backupDatabase();

		$this->assertTrue($result);
	}



	function test_restoreDatabase_NoParams_CallsRestoreOnBackupWithNoArgs()
	{
		list($env, $mockBackup) = $this->fakeEnvironmentWithBackup();

		$mockBackup->shouldReceive('restore')->once()->withNoArgs();

		$env->restoreDatabase();
	}

	function test_restoreDatabase_NoExceptionThrown_ReturnsTrue()
	{
		list($env) = $this->fakeEnvironmentWithBackup();

		$result = $env->restoreDatabase();

		$this->assertTrue($result);
	}

	function test_restoreDatabase_restoreOnBackupThrowsException_SetsError()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
			$exception = new Exception('restore error');
		$stubBackup->shouldReceive('restore')->andThrow($exception);
		$env->restoreDatabase();

		$result = $env->getError();

		$this->assertEquals('restore error', $result);
	}

	function test_restoreDatabase_restoreOnBackupThrowsException_ReturnsFalse()
	{
		list($env, $stubBackup) = $this->fakeEnvironmentWithBackup();
			$exception = new Exception('restore error');
		$stubBackup->shouldReceive('restore')->andThrow($exception);

		$result = $env->restoreDatabase();

		$this->assertFalse($result);
	}


/*
*/
}

class FakeEnvironment extends Environment {

	public $fakeDomain;

	public $fakeBackup;

	public function fakeSharedConfigs(array $shared)
	{
		$this->configs['shared'] = $shared;
	}

	public function clearInstance()
	{
		static::$instance = null;
	}

	protected function getCurrentDomain()
	{
		return $this->fakeDomain ? : parent::getCurrentDomain();
	}

	public function makeBackup()
	{
		return $this->fakeBackup ? : parent::makeBackup();
	}

}