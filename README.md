# DB Manager Server Tool #

Requirements:
------------
* Docker 
* Docker Compose

Installation
------------


Usage
-----
Available commands:
1. `make console app:db:process --uid=<Database UID> --db=<Database Name>` - start db backup processing
2. `make console app:db:analyze --uid=<Database UID> --db=<Database Name>` - analyze db structure and send to service
3. `make console app:db:getScheduled` - get scheduled backups
4. `make console app:db:log --uuid=<Backup UUID> --status=<Process Status>` --message=<Message> - send log information to service


Enabling a new DB Engine
------------------------
You can enable a new DB engine in two cases:
1. during first installation process
2. manually with next steps:
   1. renamed needed .env file: env.mysql-sample > .env.mysql
   2. update configurations
   3. execute command: `make start-db <engine>` ( ex.: `make start-db mysql`)

# Enabling Cron Jobs
`make console c='app:cron:install'` - install required Cron Jobs

Libraries:
----------
- Mongodb: https://github.com/jenssegers/laravel-mongodb/tree/master
- Oracle: https://github.com/yajra/laravel-oci8
- 