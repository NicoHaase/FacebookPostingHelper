<?php
include_once '../vendor/autoload.php';

use FacebookPostingHelper\FacebookPostingHelper;

$facebookPostingHelper = new FacebookPostingHelper('options.ini');

if($facebookPostingHelper->loginValid()) {
    echo "Proper token already stored in FacebookPostingHelper";
} else {
    $loginDone = $facebookPostingHelper->performLogin();
    if($loginDone) {
        echo "Proper token is now stored in FacebookPostingHelper";
    }
}