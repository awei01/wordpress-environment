# Wordpress Environment #

This is a repository that seeks to make wordpress more portable and easier to version control. With this repository, you should be able to:

1. Migrate your wordpress from one domain to another domain.
1. Backup your wordpress database to a dump file and check it into version control.

## Requirements ##

* git client on your development server
* php composer dependency manager
* If running tests or doing development, PHPUnit


## Recommended ##

* If you have a git client on your remote host, your deploy process will be simplified


## Installation ##

1. Create a new repository for your wordpress codebase
1. Change directory to root of your repository
1. Add this repository as an upstream dependency: `git remote add upstream https://github.com/awei01/wordpress-environment.git`
1. Pull this repository's code: `git pull upstream master`
1. Add WordPress as an another dependency: `git remote add wordpress https://github.com/WordPress/WordPress.git`
1. Pull in a wordpress tagged version or branch that you want: `git pull wordpress 3.9.1` (Using tag 3.9.1)


## Configuration ##

For security purposes the `/bootstrap/configs.php` file is not under source control by default. If you are using private repository, and are aware of the risks, you can remove the `/bootstrap/.gitignore` file and keep the your `/bootstrap/configs.php` file under source control.

You should copy the following code and save it under `/bootstrap/configs.php`

```
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

```

Brief explanation of each of the elements in the config file.


### Backup Path ###

```

	'shared' => array(
		'backup_path' => __DIR__ . '/../backups',
	),

```

You can configure where you want your sql dumps to go. It currently defaults to /projectroot/backups


### Environment Configuration ###

Each enviornment should have a configuration file that is matched by the url.

```
	'environment' => array(

		'url' => 'environment.url.com',

		'database' => array(
			'db_name' => 'environment database name',
			'db_user' => 'environment database username',
			'db_password' => 'environment database password',
			'db_host' => 'environment host'
		),

		)
	),

```

The `environment` array key should be changed to whatever handle you want to use for that particular environment, e.g. `local`, `development`, `staging`, or `production`.

The value for the envrionment should be an array containing the following keys:

* `url` this is the url that will be matched for your particular environment

* `database` is an array of database configuration information.

* If your environment does not allow DROP DATABASE statements, then you'll also want to add `'no_drop' => true` to the database array. This will suppress the DROP DATABASE statement for the dump file for that particular environment.


## Backing up the database ##

1. Go to your wordpress admin page.
1. Click on tools -> simple backup
1. Click 'backup database'
1. A sql dump file should be generated in the backup folder for each environment that exists in your configuraiton file.


## Restoring the database ##

1. Go to your wordpress admin page.
1. Click on tools -> simple backup
1. Click 'restore datasbase'
1. Your backup sql file for the enviornment you're currently working on should be easily detected and run. All links should automatically work.


## Setting up remote environment ##

Assuming you've:
* created a development envrionment
* properly configured the `boostrap/configs.php` file for the remote envrionment you're about to create
* put all your code into your git repository
* you can do the following to create your new remote envrionment

1. SSH into the remote environment.
1. git clone your repository `git clone <repo>`
1. Browse to your wordpress installation on the remote site.
1. You'll be confronted with the default setup-your-wordpress process.
1. Fill in all the details with some temporary information, but be sure to remember your admin username/password.
1. Log into your admin page.
1. Enable the simple backup plugin if needed.
1. Restore the database.


## Typical work process ##

Suppose you have 3 environments: local development, staging environment, production environment.

1. Do whatever wordpress development adding/editing content, plugins, themes, etc. on your local enviornment.
1. When you're done, backup the database.
1. Commit all your changes to your git repository.
1. Go to your staging environment, do a git pull on your repository. Then, restore the database. All your staging environment content should be synched with your local environment.
1. Go to your production environment, do a git pull on your repository. Then restore the database. All your production environemnt content should be synched with your local environment.


## Caveats and workarounds ##

### No git on remote environment ###

If you do not have a git client on your remote environment, you'll likely have to work on your development server, backup the database and the commit everything. Then, ftp your entire codebase to your remote envrionment. Finally, restore the database.

### No composer on remote envrionment ###

If you do not have composer package management on your remote environment, you'll have to uncomment the `!vendor` line in your .gitignore file. This will ensure all the vendor files are committed to your git repository and they will be available on your remote envrionment after a git pull.

### Wordpress urls that are in a subdirectory ###

Ideally all of your wordpress envrionment urls will not be located in a subfolder. But, if your wordpress url needs to be something like `www.envrionment.com/blog`, you may run into an issue with accessing some of your uploaded files/images when you switch between envrionments. One possible work around is to make all of your envrionments use the same `/blog` subpath and configure your apache accordingly so that it serves up the correct index.php.

### No mysqli client on remote ###

You may run into the problem where you can't create a mysql client on the remote server. Shouldn't be a huge deal, because most sites use have a phpMyAdmin interface that you can use to upload the appropriate backup script.


## Contributing ##

Feel free to fork. Pull requests considered. This was a quick and dirty application I made and there is plenty of room for improvement. If you're mucking with the `/lib` stuff, please also provide tests in `/tests`

## Todo list ##

* Generate security salts in Enviornment class using some hash of the host name and some constant. Otherwise, anyone looking at the wp_config.php salts and the mysql dumps can hack sensitive data.

* Better error handling

* Handle remote hosts that don't allow for mysqli

* Completely empty the target backup directory before generating scripts. This will delete unnecessary backup scripts for environments that don't exist.