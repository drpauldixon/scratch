<?php
// Collect php-fpm status metrics from the php-fpm status URL and write them to
// a tab separated log file

// URL to the Apache server php-fpm status page
$url = "http://127.0.0.1/xx-fpm.status";
$log_file = '/var/log/php-fpm/php-fpm-status.log';

// Raw output from php-fpm status
// pool:                 www
// process manager:      dynamic
// start time:           26/Sep/2024:10:49:53 +0000
// start since:          2724
// accepted conn:        13
// listen queue:         0
// max listen queue:     0
// listen queue len:     0
// idle processes:       4
// active processes:     1
// total processes:      5
// max active processes: 1
// max children reached: 0
// slow requests:        0

// Stats to collect
$stats_to_collect = array("accepted conn", "listen queue", "listen queue len", "idle processes", "active processes", "total processes", "max active processes", "max children reached", "slow requests");

// Fetch the status page content
$timestamp = date("c");
$status_content = file_get_contents($url);

if ($status_content === FALSE) {
    die("Error fetching the Apache server status.");
}

// Parse the status content
$status_lines = explode("\n", $status_content);
$status_data = array();

# Extract the status data and add to an array
foreach ($status_lines as $line) {
    // Split each line by colon and space
    $parts = explode(":", $line, 2);
    if (count($parts) == 2) {
        $key = trim($parts[0]);

        // Only continue if the $key is a stat we want to collect
        if (array_search($key, $stats_to_collect) === false){
           continue;
        }

        $value = trim($parts[1]);

        // Convert numeric values to their respective types and format floats to 1 decimal place
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                $value = number_format((float)$value, 1);
            } else {
                $value = (int)$value;
            }
        }

        // Add to the associative array
        $status_data[$key] = $value;
    }
}

// Generate the log file line
$log_line = $timestamp . "\t" . $status_data['accepted conn'] . "\t" . $status_data['listen queue'] . "\t" . $status_data['listen queue len'] . "\t" . $status_data['idle processes'] . "\t" . $status_data['active processes'] . "\t" . $status_data['total processes'] . "\t" . $status_data['max active processes'] . "\t" . $status_data['max children reached'] . "\t" . $status_data['slow requests'] . "\n";

// Open the file in append mode and write the JSON output
$file_handle = fopen($log_file, 'a');
if ($file_handle === FALSE) {
    die("Error opening file for appending.");
}

// Append JSON output to the file on a single line
if (fwrite($file_handle, $log_line) === FALSE) {
    die("Error writing apache status log file.");
}

// Close the file handle
fclose($file_handle);
?>