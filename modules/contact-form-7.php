<?php
global $wpcf7_request_uri;
$wpcf7_request_uri = rtrim(get_option($wpsssl_opt_name), '/') . $wpcf7_request_uri;