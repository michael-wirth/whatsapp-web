<?php

namespace WhatsApp\Controller;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Util;
use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

class CodeRequest
{
    public $cc;
    public $in;
    public $method;
    public $vname;
    public $twostep_code;
    public $reset;
}

class RegisterRequest
{
    public $cc;
    public $in;
    public $code;
    public $vname;
    public $twostep_code;
    public $reset;
}

class AdminController
{
    public function migrateDatabase(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        $logger = $app['monolog'];
        $conn = $app['db'];
        do {
            if (Util::isSandboxMode()) {
                $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            try {
                $dbConfig = $this->getDatabaseConfig($request, $errors);
            } catch (\Exception $e) {
                $logger->error("Validation failed. " . $e->getMessage());
                if ($e->getCode()) {
                    $errorCode = $e->getCode();
                } else {
                    $errorCode = ApiError::GENERIC_ERROR;
                }
                $errors->add($errorCode, $e->getMessage());
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $dbUtil = new DBUtil($logger);
            try {
                $retval = $dbUtil->migrateDatabase($conn, $dbConfig);
            } catch (DBALException $e) {
                $logger->error("Unable to migrate Database. " . $e->getMessage());
                $retval = false;
            }
            if (!$retval) {
                $logger->info("Database migration skipped or failed");
                $errors->add(ApiError::GENERIC_ERROR, "Database migration skipped or failed");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function getDatabaseConfig(Request $request, $errors)
    {
        do {
            $db_engine = Util::getMandatoryParam($request, $errors, 'database_engine');
            if ($db_engine === null) {
                break;
            }
            $dbConfig = null;
            if (strtolower($db_engine) === Constants::DB_MYSQL) {
                $dbConfig = Util::getMandatoryParam($request, $errors, 'mysql');
            } else if (strtolower($db_engine) === Constants::DB_PGSQL) {
                $dbConfig = Util::getMandatoryParam($request, $errors, 'pgsql');
            }
            if (!$dbConfig) {
                break;
            }
            $dbConfig['engine'] = $db_engine;
            $dbConfig['dbname'] = Constants::WEB_DB_NAME;
            return DBUtil::getDatabaseConfig($dbConfig);
        } while (false);
        return null;
    }
} 