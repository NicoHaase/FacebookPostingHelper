<?php
include_once 'vendor/autoload.php';

use FacebookPostingTool\FacebookPostingTool;

$facebookPostingHelper = new FacebookPostingTool('options.ini');

$facebookPostingHelper->post();