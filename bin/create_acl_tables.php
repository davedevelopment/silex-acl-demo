#!/usr/bin/env php
<?php

use Symfony\Component\Security\Acl\Dbal\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../app/bootstrap.php';

// most of this lifted from the symfony security bundle initacl command
$connection = $app['db'];
$sm = $connection->getSchemaManager();
$tableNames = $sm->listTableNames();
$tables = array(
    'class_table_name' => $app['security.acl.dbal.class_table_name'],
    'sid_table_name' => $app['security.acl.dbal.sid_table_name'],
    'oid_table_name' => $app['security.acl.dbal.oid_table_name'],
    'oid_ancestors_table_name' => $app['security.acl.dbal.oid_ancestors_table_name'],
    'entry_table_name' => $app['security.acl.dbal.entry_table_name'],
);

foreach ($tables as $table) {
    if (in_array($table, $tableNames, true)) {
        throw new \RuntimeException(sprintf('The table "%s" already exists. Aborting.', $table));
    }
}

$schema = new Schema($tables);
foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
    $connection->exec($sql);
}
