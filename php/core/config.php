<?php
    declare(strict_types=1);

    error_reporting(E_ALL);
    date_default_timezone_set('America/Bogota');

    define('DB_HOST', '');
    define('DB_PORT', 1433);
    define('DB_USER', '');
    define('DB_PASSWORD', '');
    define('DB_DATA', '');
    define('DB_MOTOR', 'SQLSRV');

    define('PORT', filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_UNSAFE_RAW));
    define('BASE_URL', 'http://' . filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_UNSAFE_RAW) . ':' . PORT . '/');
    define('BASE_URL_IMAGE', 'http://' . filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_UNSAFE_RAW) . ':' . PORT . '/image/');
    define('ROOTPATH', '');
    define('PATHFILES', '');
    define('PATH_TEMP', '');

    define('STORAGE', 'LOCAL'); //S3 o LOCAL 
    define('BUCKET', '');
    define('BUCKET_ARCHIVOS', BUCKET);
    define('awsAccessKey', '');
    define('awsSecretKey', '');
    define('BASE_S3_ARCHIVOS', 's3://' . BUCKET . "/");