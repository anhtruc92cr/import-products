<?php

class Sx_Send_Email
{

    /**
     * Write log to file
     */
    public static function send_email($value = 'error')
    {
        $emails = get_option('sx-import-emails');
        $dir = SX_IMPORT_PATH_BK . "/logs";
        try {
            if (!empty($emails)) {
                if ($value == 'error') {
                    $subject = 'Importing is in trouble: ' . get_home_url();
                    $message = 'Please check log file at <a href="' . $dir . '/log_' . date("d-m-Y") . '.log">here</a>' . ' for more detail.';
                } else {
                    $subject = 'Congratulation! The importing have done on ' . get_home_url();
                    $message = 'The import process have done. All products were imported. Please check log file at <a href="' . SX_IMPORT_URL_BK . '/logs/log_' . date("d-m-Y") . '.log">here</a>' . ' for more detail.';
                }
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($emails, $subject, $message, $headers);
                Sx_Write_Log::write_log('Successful: Sent email to ' . $emails);
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Cannot send email to ' . $emails . ': ' . $e->getMessage());
        }
    }
}