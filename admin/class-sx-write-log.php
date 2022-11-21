<?php
class Sx_Write_Log {

    /**
     * Write log to file
     */
    public static function write_log($log)
    {
        $dir = SX_IMPORT_PATH_BK . "/logs";
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        file_put_contents($dir . '/log_' . date("d-m-Y") . '.log', date("H:i:s") . " " . $log . PHP_EOL, FILE_APPEND);
    }
}