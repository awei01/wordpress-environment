<?php

class MigratorTest extends TestCase {

	protected function fakeSource()
	{
		return array(
			'source' => "
--
-- Database: `localdb`
--

/*!40000 DROP DATABASE IF EXISTS `localdb`*/;
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `localdb` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
USE `localdb`;

INSERT INTO `wp_comments` VALUES('http://domain.local/some/path', 'http://domain.local/some.file', TRUE, FALSE);

",
			'simple' => "
--
-- Database: `simpledb`
--

/*!40000 DROP DATABASE IF EXISTS `simpledb`*/;
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `simpledb` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
USE `simpledb`;

INSERT INTO `wp_comments` VALUES('http://simple.com/some/path', 'http://simple.com/some.file', TRUE, FALSE);

",
			'urlpath' => "
--
-- Database: `urlpathdb`
--

/*!40000 DROP DATABASE IF EXISTS `urlpathdb`*/;
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `urlpathdb` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
USE `urlpathdb`;

INSERT INTO `wp_comments` VALUES('http://urlpath.com/extra/some/path', 'http://urlpath.com/extra/some.file', TRUE, FALSE);

",
			'commented' => "
--
-- Database: `commenteddb`
--

#  IF EXISTS `commenteddb`*/;
#  /*!32312 IF NOT EXISTS*/ `commenteddb` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
USE `commenteddb`;

INSERT INTO `wp_comments` VALUES('http://commented.com/extra/some/path', 'http://commented.com/extra/some.file', TRUE, FALSE);

",
			'sourcecommented' => "
--
-- Database: `localdb`
--

#  IF EXISTS `localdb`*/;
#  /*!32312 IF NOT EXISTS*/ `localdb` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
USE `localdb`;

INSERT INTO `wp_comments` VALUES('http://domain.local/some/path', 'http://domain.local/some.file', TRUE, FALSE);

",
		);
	}

	protected function fakeConfigs()
	{
		return array(
			'source' => array(
				'url' => 'domain.local',
				'database' => array(
					'db_name' => 'localdb',
				),
			),
			'simple' => array(
				'url' => 'simple.com',
				'database' => array(
					'db_name' => 'simpledb',
				),
			),
			'urlpath' => array(
				'url' => 'urlpath.com/extra',
				'database' => array(
					'db_name' => 'urlpathdb',
				),
			),
			'commented' => array(
				'url' => 'commented.com/extra',
				'database' => array(
					'db_name' => 'commenteddb',
					'no_drop' => true,
				),
			),
		);
	}

	function test_migrate_SourceFromConfigsAndToConfigsWithSimpleSearchReplace_ReturnsDatabaseAndUrlReplaced()
	{
		$migrator = new Migrator;
			$fakeSource = $this->fakeSource();
			$fakeConfigs = $this->fakeConfigs();
			$expected = $fakeSource['simple'];

		$result = $migrator->migrate($fakeSource['source'], $fakeConfigs['source'], $fakeConfigs['simple']);

		$this->assertEquals($expected, $result);
	}

	function test_migrate_SourceFromConfigsAndToConfigsWithUrlContainingPath_ReturnsDatabaseAndUrlReplaced()
	{
		$migrator = new Migrator;
			$fakeSource = $this->fakeSource();
			$fakeConfigs = $this->fakeConfigs();
			$expected = $fakeSource['urlpath'];

		$result = $migrator->migrate($fakeSource['source'], $fakeConfigs['source'], $fakeConfigs['urlpath']);

		$this->assertEquals($expected, $result);
	}

	function test_migrate_SourceFromConfigsAndToConfigsWithNoDrop_ReturnsDatabaseAndUrlsReplacedAndDropCommented()
	{
		$migrator = new Migrator;
			$fakeSource = $this->fakeSource();
			$fakeConfigs = $this->fakeConfigs();
			$expected = $fakeSource['commented'];

		$result = $migrator->migrate($fakeSource['source'], $fakeConfigs['source'], $fakeConfigs['commented']);

		$this->assertEquals($expected, $result);
	}

	function test_migrate_SourceFromConfigsAndToConfigsSameAsSourceButCommented_ReturnsSourceThatHasBeenCommented()
	{
		$migrator = new Migrator;
			$fakeSource = $this->fakeSource();
			$configs = $this->fakeConfigs();
			$fakeConfigs = $configs['source'];
			$fakeConfigs['database']['no_drop'] = true;
			$expected = $fakeSource['sourcecommented'];

		$result = $migrator->migrate($fakeSource['source'], $fakeConfigs, $fakeConfigs);

		$this->assertEquals($expected, $result);
	}


}