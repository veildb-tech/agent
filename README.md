# DB Manager Server Tool #

## Requirements:

### For Manual Usage
* Composer
* PHP 8.x
* Database Engine

## Getting Started
Before starting need to prepare are next parameters
- Current server URL, it must be public URL
- Full path to DB backups ( default is: /home/user/dumps/ )

The tool could be installed by two ways:
1. by executing: `curl http://db-manager-cli.bridge2.digital/download/server-install | bash`
2. or manually

### To setup tool manually need to do:
1. download soruce code to needed folder
2. execute `./server-manager setup`
3. Follow the further instructions

### Steps after setup
1. execute: `server-manager app:server:add` - command will authorize the server in service.
2. execute: `server-manager app:cron:install` - command will install all required cron jobs.

Usage:
-----
Available commands:
1. `server-manager app:db:process --uid=<Database UID> --db=<Database Name>` - start db backup processing
2. `server-manager app:db:analyze --uid=<Database UID> --db=<Database Name>` - analyze db structure and send to service
3. `server-manager app:db:getScheduled` - get scheduled backups
4. `server-manager app:db:log --uuid=<Backup UUID> --status=<Process Status>` --message=<Message> - send log information to service


Enabling a new DB Engine:
------------------------
You can enable a new DB engine in two cases:
1. during first installation process
2. manually with next steps:
   1. rename needed .env file: env.mysql-sample > .env.mysql
   2. update configurations
   3. execute command: `make start-db <engine>` ( ex.: `make start-db mysql`)

# Enabling Cron Jobs:
`make console c='app:cron:install'` - install required Cron Jobs

Libraries:
----------
- Mongodb: https://github.com/jenssegers/laravel-mongodb/tree/master
- Oracle: https://github.com/yajra/laravel-oci8