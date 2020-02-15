<?php
/**
 * Created by PhpStorm.
 * User: Rhilip
 * Date: 1/17/2020
 * Time: 2020
 */

namespace IYUU\Client\transmission;

/**
 * This is the type of exception the TransmissionRPC class will throw
 */
class TransmissionRPCException extends \Exception
{
    /**
     * Exception: Invalid arguments
     */
    const E_INVALIDARG = -1;

    /**
     * Exception: Invalid Session-Id
     */
    const E_SESSIONID = -2;

    /**
     * Exception: Error while connecting
     */
    const E_CONNECTION = -3;

    /**
     * Exception: Error 401 returned, unauthorized
     */
    const E_AUTHENTICATION = -4;

    /**
     * Exception constructor
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        // PHP version 5.3.0 and above support Exception linking
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
    }
}
