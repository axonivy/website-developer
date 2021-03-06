# Axon Ivy Dev Website

## Setup
  
Run `./up.sh` to start the website in docker
  
... and later `docker-compose down` to stop the containers.

## Execute tests

Run `./run-tests.sh` to execute tests.

## Issue list for the news pages

- Run https://jenkins.ivyteam.io/job/website-developer_issue-list-generator with the ivy version to release
- Copy and paste the issues from the console log to the files in the news folder

# IDE Setup

### VSCode (recommended)

- Install extension **PHP Intelphense** and follow the Quickstart guide
- Install extension **Twig**

### Eclipse

- Download Eclipse PHP
- Install Twig Plugin from Eclipse Marketplace
- Configure PHP Formatter PSR7

# Update a php library

```
// Show outdated dependencies
docker-compose exec web composer show --outdated

// Upgrade dependencies
docker-compose exec web composer update --prefer-dist -a --with-all-dependencies
```

## Ressources

- Slim Project Bootstrap <https://github.com/kalvn/Slim-Framework-Skeleton>
- SlimFramework <http://www.slimframework.com>
- Template <https://templated.co/introspect>
- JS-Framework <https://github.com/ajlkn/skel>
