# DB Manager Server Tool #

## Requirements:

### For Manual Usage
* Composer
* PHP 8.2.x
* PHP Libraries:
  * Ctype
  * iconv
  * PCRE
  * Session
  * SimpleXML
  * PDO
* Configured Nginx / Apache server ( Symfony guide: https://symfony.com/doc/current/setup/web_server_configuration.html )
* Database Engine

## Getting Started
Before starting need to prepare are next parameters
- Current server URL, it must be public URL
- Full path to DB backups ( default is: /home/user/dumps/ )

The tool could be installed by two ways:
1. by executing: `curl http://db-manager-cli.bridge2.digital/download/server-install | bash`
2. or manually

### To setup tool manually need to do:
1. download source code to needed folder
2. execute `./dbvisor-agent setup`
3. Follow the further instructions

### Steps after setup
1. execute: `dbvisor-agent app:server:add` - command will authorize the server in service.
2. execute: `dbvisor-agent app:cron:install` - command will install all required cron jobs.

## Usage:

### Available commands:
1. `dbvisor-agent app:db:process --uid=<Database UID> --db=<Database Name>` - start db backup processing
2. `dbvisor-agent app:db:analyze --uid=<Database UID> --db=<Database Name>` - analyze db structure and send to service
3. `dbvisor-agent app:db:getScheduled` - get scheduled backups
4. `dbvisor-agent app:db:log --uuid=<Backup UUID> --status=<Process Status>` --message=<Message> - send log information to service

## Additional Configurations:

## Enabling / Configure a DB Engine:
You can enable a new DB engine in two cases:
1. during first installation process
2. manually with next steps:
   1. rename needed .env file: env.mysql-sample > .env.mysql
   2. update configurations

In case you use Docker need to:
- execute command: `make start-db <engine>` ( ex.: `make start-db mysql`)

## Setup connecting to local DB
### MySQL
By default, the tool uses the network with the next Subnet: 172.27.0.0/16. It can be changed by using the variable: DBVISOR_SUBNET
You must do the next steps:
1. open the file: /etc/mysql/mysql.conf.d/mysqld.cnf
   - add to parameter: bind-address - 172.27.0.1 via semicolon ( in case you left default Subnet value )
2. add access to your user with mysql commands:
   - CREATE USER '< User >'@'172.27.0.0/16' IDENTIFIED BY '< Password >';
   - FLUSH PRIVILEGES;

### Postgres

### Libraries:
- Mongodb: https://github.com/jenssegers/laravel-mongodb/tree/master
- Oracle: https://github.com/yajra/laravel-oci8
