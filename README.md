# VeilDB Agent

[Documentation](https://veildb.gitbook.io/) • [Main Project](https://github.com/veildb-tech) • [Website](https://veildb.com)

Docker-based processing engine responsible for applying anonymization rules to database backups.

The Agent runs inside your infrastructure and performs all heavy operations locally.

---

## Part of VeilDB

This repository is **part** of the VeilDB platform.

- Main project overview: [https://github.com/veildb-tech](https://github.com/veildb-tech)
- Documentation: [https://veildb.gitbook.io/](https://veildb.gitbook.io/)

---

## Responsibilities

- Connect to configured database sources
- Create database dumps
- Apply masking and anonymization rules
- Prepare processed backups
- Send status updates to the Service
- Analyze the structure of the database and send the schema to the Service

---

## Security Model

- The Agent never exposes direct database access externally.
- Processing happens inside your infrastructure.
- Only anonymized dumps are shared with developers.

---

## Workflow

1. Agent polls the Service for tasks
2. Receives anonymization rules
3. Creates database dump
4. Applies masking logic
5. Reports status back to Service


## Installation

**Requirements:**

- docker
- curl
- lsof
- zip

```bash
sudo apt update && sudo apt install curl lsof zip
curl https://veildb.com/download/veildb-agent-install | bash
source ~/.bashrc
```

**Explanation of prompts during the installation:**

- `Do you want to use Docker?` - **Yes**, currently set up without doesn't support
- `Enter the service URL` - the public URL to the service layer (To this repository https://github.com/veildb-tech/service)
- `Enter the host name for the current Server ( default: 209.38.233.204:8088 )` - Agent has one public endpoint for downloading processed dumps. It is **strongly** recommended to configure the domain and SSL.
- `Enter the full path to the folder where processed dumps will be placed` - this is just a configuration where the processed SQL file will be stored.
- `Do you have locally placed backups, or do you plan to use manual backups in the future?` - in case you want to upload or make dumps of the origin database manually (e.g., by cron), select Yes.
- `Enter the full path to folder with your local dumps` - This will be a directory for such backups. It means if you create a backup by cron like `mysqldump`, it should be placed in that folder.
- `Initializing supported DB engines`: select preferred engine. It can be selected multiple. (The engine can be enabled after installation as well)
- `Choose MySQL version` - Select the version of the engine. Strongly recommended to use the same version as the original database.

## Commands

```bash

Usage:
    veildb-agent [command]

Options:
    -l | --list | --help  - list of available commands

Available Commands:
    start      - start environment ( Docker only )
    stop       - stop environment ( Docker only )
    stopall    - stop all environments ( Docker only )
    re-build   - re-build containers ( Docker only )
    cli        - allows running a command line command inside of docker environment ( Docker only )
    console    - run app console commands
    cron       - run con commands ( Docker only )
    info       - basic information about the tool
    setup      - initial setup

Tool:
    app:server:add                 - Start adding the server to service
                                      --email[=EMAIL] Email to authorize
                                      --password[=PASSWORD] Password to authorize
                                      --current If this option set it command only updates server credentials

    app:server:update              - Update the server data
                                      --email[=EMAIL] Email to authorize
                                      --password[=PASSWORD] Password to authorize
                                      --current If this option set it command only updates server credentials

    app:server:generate-keypair    - Generate public/private keys for use in your application.
                                      --identifier[=EMAIL] User identifier!

    app:db:process                 - Start processing scheduled database
    app:db:analyze                 - Start analyzing db structure
                                      -u | --uid[=UID] Database UUID from the service
                                      --db[=DB] Database Name

    app:db:add                     - Create DB dump
                                      --db[=DB] Database Name
                                      --path[=PATH] Full path to backup file
                                      --engine[=ENGINE] Db Engine ( mysql | postgres ). Default: mysql

    app:db:update                  - Update DB dump
                                      -u | --uid[=UID] Database Uid

    app:db:backups:clear           - Start cleaning old DB backups
    app:cron:install               - Generates and installs crontab for current user
```
