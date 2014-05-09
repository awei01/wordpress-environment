<?php

class BackupTest extends TestCase {

	protected function fakeEnvironment()
	{
		return $this->fakeClass('Environment');
	}


	function test_construct_Environment_SetsEnvironment()
	{
		$env = $this->fakeEnvironment();
		$backup = new Backup($env);

		$result = $backup->getEnvironment();

		$this->assertEquals($env, $result);
	}

	protected function makeBackup()
	{
		$env = $this->fakeEnvironment();

		return new Backup($env);
	}

	function test_getSettings_NoParams_ReturnsExpectedSettings()
	{
		$backup = $this->makeBackup();
		$expected = array(
			'compress' => 'None',
			'add-drop-database' => true,
			'add-drop-table' => true,
			'single-transaction' => true,
			'lock-tables' => true,
			'add-locks' => true,
			'extended-insert' => true,
			'disable-foreign-keys-check' => false
		);

		$result = $backup->getSettings();

		$this->assertEquals($expected, $result);
	}

	protected function makeBackupWithFakeEnvironment()
	{
		$backup = $this->makeBackup();

		$fakeEnvironment = $backup->getEnvironment();

		return array($backup, $fakeEnvironment);
	}

	function test_makeFilePath_Url_CallsGetSharedWithBackupPathKey()
	{
		list($backup, $mockEnv) = $this->makeBackupWithFakeEnvironment();

		$mockEnv->shouldReceive('getShared')->once()->with('backup_path');

		$backup->makeFilePath('domain.com');
	}

	function test_makeFilePath_UrlAndGetSharedOnBackupReturnsFolder_ReturnsFolderDataUnderscoreUrlDotSql()
	{
		list($backup, $stubEnv) = $this->makeBackupWithFakeEnvironment();
		$stubEnv->shouldReceive('getShared')->andReturn('/path/to');

		$result = $backup->makeFilePath('domain.com');

		$this->assertEquals('/path/to/data_domain.com.sql', $result);
	}

	function test_makeFilePath_UrlWithSlashAndFolder_ReturnsFolderAndUrlWithSlashConvertedToUnderscore()
	{
		list($backup, $stubEnv) = $this->makeBackupWithFakeEnvironment();
		$stubEnv->shouldReceive('getShared')->andReturn('/path/to');

		$result = $backup->makeFilePath('www.domain.com/folder');

		$this->assertEquals('/path/to/data_www.domain.com_folder.sql', $result);
	}

	protected function fakeDatabaseConfigs()
	{
		return array(
			'db_name' => 'dbname',
			'db_user' => 'dbuser',
			'db_password' => 'password',
			'db_host' => 'dbhost'
		);
	}

	function test_makeDumper_NoParams_CallsGetDatabaseOnEnvironmentWithNoArgs()
	{
		list($backup, $mockEnv) = $this->makeBackupWithFakeEnvironment();
		$fakeConfigs = $this->fakeDatabaseConfigs();

		$mockEnv->shouldReceive('getDatabase')->once()->withNoArgs()->andReturn($fakeConfigs);

		$backup->makeDumper();
	}

	function test_makeDumper_getDatabaseOnEnvironmentReturnsArray_ReturnsInstanceOfMysqldump()
	{
		list($backup, $stubEnv) = $this->makeBackupWithFakeEnvironment();
		$fakeConfigs = $this->fakeDatabaseConfigs();
		$stubEnv->shouldReceive('getDatabase')->andReturn($fakeConfigs);

		$result = $backup->makeDumper();

		$this->assertInstanceOf('Mysqldump', $result);
	}

	protected function fakeBackupWithFakeEnvironmentAndDumper()
	{
		$env = $this->fakeEnvironment();

		$backup = new FakeBackup($env);

		$fakeDumper = $backup->fakeDumper = $this->fakeClass('Mysqldump');

		return array($backup, $env, $fakeDumper);
	}

	function test_backup_NoParams_CallsGetUrlForOnEnvironmentWithNoArgs()
	{
		list($backup, $mockEnv) = $this->fakeBackupWithFakeEnvironmentAndDumper();

		$mockEnv->shouldReceive('getUrlFor')->once()->withNoArgs();

		$backup->backup();
	}

	function test_backup_getUrlOnEnvironmentReturnsUrl_CallsStartOnDumperWithGeneratedPath()
	{
		list($backup, $stubEnv, $mockDumper) = $this->fakeBackupWithFakeEnvironmentAndDumper();
		$backup->fakePath = '/path';
		$stubEnv->shouldReceive('getUrlFor')->andReturn('domain.com');

		$mockDumper->shouldReceive('start')->once()->with('/path/data_domain.com.sql');

		$backup->backup();
	}

	function test_backup_startOnDumperCalled_ReturnsTrue()
	{
		list($backup) = $this->fakeBackupWithFakeEnvironmentAndDumper();

		$result = $backup->backup();

		$this->assertTrue($result);
	}

	function test_makeMigrator_NoParams_ReturnsInstanceOfDumpGenerator()
	{
		$backup = $this->makeBackup();

		$result = $backup->makeMigrator();

		$this->assertInstanceOf('Migrator', $result);
	}

	protected function fakeBackupWithFakeEnvironmentAndMigrator()
	{
		$env = $this->fakeEnvironment();

		$backup = new FakeBackup($env);

		$backup->fakePath = '/path';

		$fakeMigrator = $backup->fakeMigrator = $this->fakeClass('Migrator');

		return array($backup, $env, $fakeMigrator);
	}

	function test_migrateFromAndCleanup_Environment_CallsGetUrlForOnEnvironmentWithEnvironment()
	{
		list($backup, $mockEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$stubMigrator->shouldReceive('readFile')->andReturn('sql file');
		$mockEnv->shouldReceive('getConfigs')->andReturn(array());

		$mockEnv->shouldReceive('getUrlFor')->once()->with('local');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_getUrlForOnEnvReturnsUrl_CallsReadFileOnMigratorWithSourcePathForUrl()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$stubEnv->shouldReceive('getUrlFor')->andReturn('local');
		$stubEnv->shouldReceive('getConfigs')->andReturn(array());

		$mockMigrator->shouldReceive('readFile')->once()->with('/path/data_local.sql')->andReturn('sql file');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_readFileOnMigratorReturnsFalsey_ThrowsRuntimeException()
	{
		list($backup, $stubEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$stubMigrator->shouldReceive('readFile')->andReturn('');

		$this->setExpectedException('RuntimeException');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_EnvironmentParamAndReadFileOnMigratorReturnsContents_CallsGetConfigsWithEnvironmentParam()
	{
		list($backup, $mockEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$stubMigrator->shouldReceive('readFile')->andReturn('sql file');
		$mockEnv->shouldReceive('getConfigs')->withNoArgs()->andReturn(array());

		$mockEnv->shouldReceive('getConfigs')->once()->with('local');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_EnvironmentParamAndReadFileOnMigratorReturnsContents_CallsGetConfigsWithNoArgs()
	{
		list($backup, $mockEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$stubMigrator->shouldReceive('readFile')->andReturn('sql file');

		$mockEnv->shouldReceive('getConfigs')->once()->withNoArgs()->andReturn(array());

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_readFileOnMigratorReturnsSourceGetConfigsOnEnvironmentReturnsFromConfigsAndMultipleToConfigs_CallsMigrateOnMigratorWithSourceFromConfigsAndFirstSetOfToConfigs()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$mockMigrator->shouldReceive('readFile')->andReturn('sql file');
		$stubEnv->shouldReceive('getConfigs')->with('local')->andReturn(array('local'));
		$stubEnv->shouldReceive('getConfigs')->withNoArgs()->andReturn(array('staging' => array('staging', 'url' => 'staging.com'), 'production' => array('production', 'url' => 'production.com')));

		$mockMigrator->shouldReceive('migrate')->once()->with('sql file', array('local'), array('staging', 'url' => 'staging.com'));

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_migrateOnMigratorWithFirstSetOfConfigsReturnsResult_CallsWriteFileOnMigratorWithPathUsingUrlOfFirstConfigs()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$mockMigrator->shouldReceive('readFile')->andReturn('sql file');
		$stubEnv->shouldReceive('getConfigs')->with('local')->andReturn(array('local'));
		$stubEnv->shouldReceive('getConfigs')->withNoArgs()->andReturn(array('staging' => array('staging', 'url' => 'staging.com'), 'production' => array('production', 'url' => 'production.com')));
		$mockMigrator->shouldReceive('migrate')->andReturn('staging migrated result');

		$mockMigrator->shouldReceive('writeFile')->once()->with('/path/data_staging.com.sql', 'staging migrated result');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_readFileOnMigratorReturnsSourceGetConfigsOnEnvironmentReturnsFromConfigsAndMultipleToConfigs_CallsMigrateOnMigratorWithSourceFromConfigsAndLastSetOfToConfigs()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$mockMigrator->shouldReceive('readFile')->andReturn('sql file');
		$stubEnv->shouldReceive('getConfigs')->with('local')->andReturn(array('local'));
		$stubEnv->shouldReceive('getConfigs')->withNoArgs()->andReturn(array('staging' => array('staging', 'url' => 'staging.com'), 'production' => array('production', 'url' => 'production.com')));

		$mockMigrator->shouldReceive('migrate')->once()->with('sql file', array('local'), array('production', 'url' => 'production.com'));

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_migrateOnMigratorWithFirstSetOfConfigsReturnsResult_CallsWriteFileOnMigratorWithPathUsingUrlOfLastConfigs()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$mockMigrator->shouldReceive('readFile')->andReturn('sql file');
		$stubEnv->shouldReceive('getConfigs')->with('local')->andReturn(array('local'));
		$stubEnv->shouldReceive('getConfigs')->withNoArgs()->andReturn(array('staging' => array('staging', 'url' => 'staging.com'), 'production' => array('production', 'url' => 'production.com')));
		$mockMigrator->shouldReceive('migrate')->andReturn('production migrated result');

		$mockMigrator->shouldReceive('writeFile')->once()->with('/path/data_production.com.sql', 'production migrated result');

		$backup->migrateFromAndCleanup('local');
	}

	function test_migrateFromAndCleanup_NoExceptionsThrown_ReturnsTrue()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentAndMigrator();
		$mockMigrator->shouldReceive('readFile')->andReturn('sql file');
		$stubEnv->shouldReceive('getConfigs')->andReturn(array());

		$result = $backup->migrateFromAndCleanup('local');

		$this->assertTrue($result);
	}


	protected function getIntegrationDatabaseConfigs()
	{
		return array(
			'db_user' => 'development_user',
			'db_password' => 'password',
			'db_host' => 'localhost',
			'db_name' => 'cai_development',
		);
	}

	function test_makeMysqli_NoParams_CallsGetDatabaseOnEnvironmentWithNoArgs()
	{
		$backup = $this->makeBackup();
		$mockEnv = $backup->getEnvironment();
			$configs = $this->getIntegrationDatabaseConfigs();

		$mockEnv->shouldReceive('getDatabase')->once()->withNoArgs()->andReturn($configs);

		$backup->makeMysqli();
	}

	function test_makeMysqli_IntegrationTest_ReturnsInstanceOfMysqli()
	{
		// THIS IS AN INTEGRATION TEST. YOU NEED TO HAVE DB SET UP

		//$this->markTestSkipped('THIS IS AN INTEGRATION TEST. YOU NEED TO HAVE DB SET UP');
		$backup = $this->makeBackup();
			$stubEnv = $backup->getEnvironment();
			$configs = $this->getIntegrationDatabaseConfigs();
		$stubEnv->shouldReceive('getDatabase')->andReturn($configs);

		$result = $backup->makeMysqli();

		$this->assertInstanceOf('mysqli', $result);
	}

	function test_makeMysqli_InvalidConfigs_ThrowsRuntimeException()
	{
		// THIS IS AN INTEGRATION TEST. YOU NEED TO HAVE DB SET UP

		//$this->markTestSkipped('THIS IS AN INTEGRATION TEST TO TEST THAT YOU CANT CONNECT');

		$backup = $this->makeBackup();
			$stubEnv = $backup->getEnvironment();
			$configs = array(
				'db_user' => 'invalid_user',
				'db_password' => 'invalid_pass',
				'db_host' => 'localhost',
				'db_name' => 'invalid_db',
			);
		$stubEnv->shouldReceive('getDatabase')->andReturn($configs);

		$this->setExpectedException('RuntimeException');

		$backup->makeMysqli();
	}

	protected function fakeBackupWithFakeEnvironmentMigratorAndMysqli()
	{
		$fakeEnv = $this->fakeEnvironment();

		$backup = new FakeBackup($fakeEnv);

		$backup->fakePath = '/path';

		$fakeMigrator = $backup->fakeMigrator = $this->fakeClass('Migrator');

		$fakeMysqli = $backup->fakeMysqli = $this->fakeClass('StdClass');

		$fakeMysqli->errno = '';

		return array($backup, $fakeEnv, $fakeMigrator, $fakeMysqli);
	}

	function test_restore_NoParams_CallsGetUrlForOnEnvironmentWithNoArgs()
	{
		list($backup, $mockEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubMigrator->shouldReceive('readFile')->andReturn('source sql');

		$mockEnv->shouldReceive('getUrlFor')->once()->withNoArgs();

		$backup->restore();
	}

	function test_restore_getUrlForOnEnvironmentReturnsUrl_CallsReadFileOnMigratorWithMadePath()
	{
		list($backup, $stubEnv, $mockMigrator) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubEnv->shouldReceive('getUrlFor')->andReturn('local.com');

		$mockMigrator->shouldReceive('readFile')->once()->with('/path/data_local.com.sql')->andReturn('source sql');

		$backup->restore();
	}

	function test_restore_readFileOnMigratorReturnsFalsey_ThrowsException()
	{
		list($backup, $stubEnv, $stubMigrator) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubEnv->shouldReceive('getUrlFor')->andReturn('local.com');
		$stubMigrator->shouldReceive('readFile')->andReturn('');

		$this->setExpectedException('RuntimeException');

		$backup->restore();
	}

	function test_restore_readFileOnMigratorReturnsFilestream_CallsMultiQueryOnMysqliWithFilestream()
	{
		list($backup, $stubEnv, $stubMigrator, $mockSql) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubEnv->shouldReceive('getUrlFor')->andReturn('local.com');
		$stubMigrator->shouldReceive('readFile')->andReturn('source sql');

		$mockSql->shouldReceive('multi_query')->once()->with('source sql');

		$backup->restore();
	}

	function test_restore_mysqliHasRun_CallsCloseOnMysqliWithNoArgs()
	{
		list($backup, $stubEnv, $stubMigrator, $mockSql) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubMigrator->shouldReceive('readFile')->andReturn('source sql');

		$mockSql->shouldReceive('close')->once()->withNoArgs();

		$backup->restore();
	}

	function test_restore_mysqliErrnoPropertyHasSomething_ThrowsRuntimeException()
	{
		list($backup, $stubEnv, $stubMigrator, $stubSql) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubMigrator->shouldReceive('readFile')->andReturn('source sql');
		$stubSql->errno = 'errors';

		$this->setExpectedException('RuntimeException');

		$backup->restore();
	}

	function test_restore_NoExceptionsThrow_ReturnsTrue()
	{
		list($backup, $stubEnv, $stubMigrator, $stubSql) = $this->fakeBackupWithFakeEnvironmentMigratorAndMysqli();
		$stubMigrator->shouldReceive('readFile')->andReturn('source sql');

		$result = $backup->restore();

		$this->assertTrue($result);

	}

/*
*/
}

class FakeBackup extends Backup {

	public $fakePath;

	public $fakeDumper;

	public $fakeMigrator;

	public $fakeMysqli;

	public function makeFilePath($domain)
	{
		return $this->fakePath ? $this->fakePath . '/data_' . $domain . '.sql' : parent::makeFilePath($domain);
	}

	public function makeDumper()
	{
		return $this->fakeDumper ? : parent::makeDumper();
	}

	public function makeMigrator()
	{
		return $this->fakeMigrator ? : parent::makeMigrator();
	}

	public function makeMysqli()
	{
		return $this->fakeMysqli ? : parent::makeMysqli();
	}

}