# DB Manager Server Symfony Tool #

Requirements:
------------
* PHP 8.1^


Installation
------------


Usage
-----

Available commands:
`
php bin/console app:db:process --uid=<Database UID> --db=<Database Name> - start db backup processing
php bin/console app:db:analyze --uid=<Database UID> --db=<Database Name> - analyze db structure and send to service
php bin/console app:db:getScheduled - get scheduled backups
php bin/console app:db:log --uuid=<Backup UUID> --status=<Process Status> --message=<Message> - send log information to service
`

Libraries:
----------
- Mongodb: https://github.com/jenssegers/laravel-mongodb/tree/master
- Oracle: https://github.com/yajra/laravel-oci8
- 