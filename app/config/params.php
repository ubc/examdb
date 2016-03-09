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
    'upload_dir' => 'upload_dir',
    'cache_driver' => 'cache_driver',
    'cache_driver_wiki' => 'cache_driver_wiki',
    'OPENSHIFT_REDIS_HOST' => 'redis_host',
    'OPENSHIFT_REDIS_PORT' => 'redis_port',
    'REDIS_HOST' => 'redis_host',
    'REDIS_PORT' => 'redis_port',
    'REDIS_PASSWORD' => 'redis_password',
    'session_handler' => 'session_handler',
    'analytics_tracker' => 'analytics_tracker',
);

array_walk($envs, function($v, $k) use ($container) {
    $val = getEnv($k);
    if (false !== $val) {
        $container->setParameter($v, is_numeric($val) ? (int)$val : $val);
    }
});

// construct redis dsn with optional password and port
$redis_dsn = "redis://";
if ($container->getParameter('redis_password')) {
    $redis_dsn .= $container->getParameter('redis_password') . '@';
}
$redis_dsn .= $container->getParameter('redis_host');
if ($container->getParameter('redis_port')) {
    $redis_dsn .= ':'.$container->getParameter('redis_port');
}
$container->setParameter('redis_dsn', $redis_dsn);


// change pdo_DRIVER to driver for pdoSessionHandler
$container->setParameter('database_session_driver', str_replace('pdo_', '', $container->getParameter('database_driver')));
