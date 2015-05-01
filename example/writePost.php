<?php
include_once 'vendor/autoload.php';

use FacebookPostingHelper\FacebookPostingHelper;

$facebookPostingHelper = new FacebookPostingHelper('options.ini');

$postId = $facebookPostingHelper->post('Message for my first posting');

echo 'Posting was saved with ID ' . $postId;