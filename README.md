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
php bin/console app:db:process --uid=<Database UID> --db=<Database Name>
php bin/console app:db:getScheduled
php bin/console app:db:log --uuid=<Backup UUID> --status=<Process Status> --message=<Message>
`

Libraries:
----------
- Mongodb: https://github.com/jenssegers/laravel-mongodb/tree/master
- Oracle: https://github.com/yajra/laravel-oci8
- 