<?php
//wrapped in if statement so that 
if (getEnv("OPENSHIFT_MYSQL_DB_HOST") !== false) {
	$container->setParameter('database_host', getEnv("OPENSHIFT_MYSQL_DB_HOST"));
	$container->setParameter('database_port', getEnv("OPENSHIFT_MYSQL_DB_PORT"));
	$container->setParameter('database_name', getEnv("OPENSHIFT_APP_NAME"));
	$container->setParameter('database_user', getEnv("OPENSHIFT_MYSQL_DB_USERNAME"));
	$container->setParameter('database_password', getEnv("OPENSHIFT_MYSQL_DB_PASSWORD"));
}

$envs = array(
    'cas_server',
    'cas_port',
    'cas_path',
    'wiki_base_url',
    'auth2_username',
    'auth2_password',
    'auth2_service_application',
    'auth2_service_url',
    'sis_base_url',
);

array_walk($envs, function($v) use ($container) {
   if (false !== getEnv($v)) {
       $container->setParameter($v, getEnv($v));
   }
});
