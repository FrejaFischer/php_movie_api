<?php

Class Logger
{
    private const LOG_DIRECTORY = __DIR__.'/../log'; // defining directory
    
    /**
     * Function for logging. Saves params in todays log file (and creates directory and file if needed)
     * @param $content is a numeroues amount of params in any data type, that we want to log
     * @return void
     * 
     */
    public static function logText(string|array ...$content): void
    {
        $logFileName = Logger::LOG_DIRECTORY . '/log_' . date("Ymd") . '.htm'; // defining file name
        // If the logging directory does not exist, it is created
        if (!is_dir(self::LOG_DIRECTORY)) {
            if (!mkdir(self::LOG_DIRECTORY)) {
                return;
            }
        }
    
        $output = '';
        
        if (!file_exists($logFileName)) {
            $output .= '<pre>';
        }
    
        $timestamp = date("Y-m-d H:i:s A"); // current timestamp
        $output .= '<br>' . '--- ' . $timestamp . ' --- ';
    
        // for each paramter add linebreaks and add them to the output
        foreach ($content as $param) {
            if (is_string($param)) {
                $output .= '<br>' . $param;  // For strings, just append with <br>
            } elseif (is_array($param)) {
                $output .= '<br>' . print_r($param, true);  // For arrays, use print_r() to print it as-is
            } elseif (is_object($param)) {
                $output .= '<br>' . json_encode($param);  // For objects, use json_encode to print in JSON format
            } else {
                $output .= '<br>' . strval($param);  // For any other type (int, float, etc.), convert to string
            }
        }
        
        file_put_contents($logFileName, $output, FILE_APPEND); // Adding to the files content
    }
}