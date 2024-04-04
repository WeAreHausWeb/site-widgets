<?php
/**
 *  Developer tool to simplify and enhance error logging.
 */

class Elog
{
    public static $file;

    public static function log($data, $type = false, $label = null) {
        // Don't log in production environment.
        if(\Webien\Site\BRP\BRP::isDev()) {
            if($data === null) {
                $out = '[null]';
            }
            else if($data === '') {
                $out = '[empty string]';
            }
            else if(is_bool($data)) {
                $out = $data ? '[true]' : '[false]';
            }
            else if(is_array($data) || is_object($data)) {
                $out = print_r($data, true);
            }
            else {
                $out = $data;
            }
            $message = ($type?'('.gettype($data).') ':'').($label?$label.': ':'').$out;
            if (!self::$file) {
                self::$file = dirname(__DIR__, 5) . '/elog.log';
            }
            try {
                error_log($message."\n", 3, self::$file);
            } catch (Exception $exception) {
                // error_log($message);
            }
        }
    }
}
// Helper function for error logging.
function elog($data, $type = false, $label = null) {
    Elog::log($data, $type, $label);
}
