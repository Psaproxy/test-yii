<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => "pgsql:host=$_ENV[POSTGRES_HOST];dbname=$_ENV[POSTGRES_DB];port=5432",
    'username' => $_ENV['POSTGRES_USER'],
    'password' => $_ENV['POSTGRES_PASSWORD'],
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
