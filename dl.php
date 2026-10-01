<?php
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);
file_put_contents('composer.phar', file_get_contents('http://getcomposer.org/download/latest-stable/composer.phar', false, $context));
echo "Downloaded composer.phar\n";
?>
