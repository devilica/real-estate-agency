<?php

declare(strict_types=1);

/**
 * Copy this file to config.php.
 * Create the password hash locally and paste it below:
 * php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT), PHP_EOL;"
 *
 * mail_from must be an address on your domain (for example noreply@yourdomain.com),
 * otherwise hosting often refuses to send. Leave mail_to empty so inquiries go
 * to the email entered in the admin area.
 */
return [
    'password_hash' => '',
    'mail_to' => '',
    'mail_from' => '',
    'base_url' => '',
    'base_path' => '',
    'debug' => false,
];
