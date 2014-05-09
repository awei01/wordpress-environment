<?php

class Migrator {

	public function migrate($source, array $from, array $to)
	{
		$search = $this->extractReplacements($from);

		$replace = $this->extractReplacements($to);

		if ($replace != $search)
		{
			$result = str_replace($search, $replace, $source);
		}
		else
		{
			$result = $source;
		}

		if ($this->shouldCommentDrop($to))
		{
			$result = $this->commentDrop($result);
		}

		return $result;
	}

	protected function extractReplacements(array $configs)
	{
		$url = $configs['url'];

		$db = $configs['database']['db_name'];

		return array($url, $db);
	}

	protected function shouldCommentDrop(array $configs)
	{
		return !empty($configs['database']['no_drop']);
	}

	protected function commentDrop($source)
	{
		$search = array(
			'/*!40000 DROP DATABASE',
			'CREATE DATABASE',
		);

		$replace = array(
			'# ',
			'# ',
		);

		return str_replace($search, $replace, $source);
	}

	public function readFile($path)
	{
		return @file_get_contents($path);
	}

	public function writeFile($path, $contents)
	{
		if ($result = @file_put_contents($path, $contents))
		{
			@chmod($path, 0777);
		}

		if (!$result)
		{
			throw new RuntimeException('Cannot write to [' . $path . ']');
		}

		return $result;
	}

}