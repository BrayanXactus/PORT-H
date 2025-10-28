<?php
    declare(strict_types=1);

    error_reporting(E_ALL);
    ini_set('display_errors', 'true');
    ini_set('display_startup_errors', 'true');
    date_default_timezone_set('America/Bogota');

    define('DB_HOST', '54.186.124.6');
    define('DB_PORT', 1433);
    define('DB_USER', 'kobo');
    define('DB_PASSWORD', '@koBo4173');
    define('DB_DATA', 'kobo');
    define('DB_MOTOR', 'SQLSRV');

    define('PORT', filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING));
    define('BASE_URL', 'http://' . filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING) . ':' . PORT . '/visor.movilencuesta.com/');
    define('BASE_URL_IMAGE', 'https://' . filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING) . ':' . PORT . '/visor.movilencuesta.com/images/');
    define('ROOTPATH', '/var/www/visor.movilencuesta.com/');
    define('PATHFILES', '/var/www/visor.movilencuesta.com/files/');
	define('PATH_TEMP', '/var/www/visor.movilencuesta.com/temp/');