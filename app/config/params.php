<?php
// load environment variable configs
$envs = array(
    'OPENSHIFT_MYSQL_DB_HOST' => 'database_host',
    'OPENSHIFT_MYSQL_DB_PORT' => 'database_port',
    'OPENSHIFT_APP_NAME' => 'database_name',
    'OPENSHIFT_MYSQL_DB_USERNAME' => 'database_user',
    'OPENSHIFT_MYSQL_DB_PASSWORD' => 'database_password',
    'DB_HOST' => 'database_host',
    'DB_PORT' => 'database_port',
    'DB_NAME' => 'database_name',
    'DB_USERNAME' => 'database_user',
    'DB_PASSWORD' => 'database_password',
    'cas_server' => 'cas_server',
    'cas_port' => 'cas_port',
    'cas_path' => 'cas_path',
    'wiki_base_url' => 'wiki_base_url',
    'auth2_username' => 'auth2_username',
    'auth2_password' => 'auth2_password',
    'auth2_service_application' => 'auth2_service_application',
    'auth2_service_url' => 'auth2_service_url',
    'sis_base_url' => 'sis_base_url',
);

array_walk($envs, function($v, $k) use ($container) {
   if (false !== getEnv($k)) {
       $container->setParameter($v, getEnv($k));
   }
});
