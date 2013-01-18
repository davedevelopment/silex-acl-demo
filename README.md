Silex ACL Demo
==============

This is a demo of Symfony's ACL using Silex. I've tried to annotate the services
in `app/bootstrap.php`, but to be honest, I originally reverse engineered the
symfony full stack frameworks DI configuration without fully understanding what
everything does, still don't!

Usage
------------

```
> git clone git@github.com:davedevelopment/silex-acl-demo.git
> cd silex-acl-demo
> composer.phar install
> bin/doctrine orm:schema-tool:create
> bin/create_acl_tables.php
> php -S localhost:4444 web/index.php
```

Windows instructions
--------------------
In windows, "composer install" could take a long time to execute and no messages are printed in console. Just be patient.
```
git clone https://github.com/davedevelopment/silex-acl-demo.git
cd silex-acl-demo
composer install
php bin/doctrine orm:schema-tool:create
php bin/create_acl_tables.php
php -S localhost:4444 web/index.php
```

There are 3 users configured admin, davem and benr, all with `foo` as the
password. The admin user should have permissions to delete messages created by
all users as they have the ADMIN_ROLE, whereas the other users should only be
allowed to delete the messages where they are the owner.
