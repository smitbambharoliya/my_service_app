<?php
// Simple email test using PHP mail / SMTP directly
// Run: php test_email.php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$dsn = $_ENV['MAILER_DSN'] ?? 'NOT SET';
echo "MAILER_DSN: " . $dsn . "\n\n";

// Parse DSN
if (preg_match('/gmail:\/\/(.+):(.+)@default/', $dsn, $m)) {
    $user = urldecode($m[1]);
    $pass = $m[2];
    echo "Gmail User: " . $user . "\n";
    echo "App Password length: " . strlen($pass) . " chars\n\n";
    
    // Test with PHPMailer-like raw SMTP
    echo "Testing SMTP connection to smtp.gmail.com:587...\n";
    
    $socket = @fsockopen('tls://smtp.gmail.com', 465, $errno, $errstr, 10);
    if ($socket) {
        echo "✅ Port 465 connection: SUCCESS\n";
        fclose($socket);
    } else {
        echo "❌ Port 465 failed: $errstr ($errno)\n";
    }
    
    $socket2 = @fsockopen('smtp.gmail.com', 587, $errno2, $errstr2, 10);
    if ($socket2) {
        echo "✅ Port 587 connection: SUCCESS\n";
        fclose($socket2);
    } else {
        echo "❌ Port 587 failed: $errstr2 ($errno2)\n";
    }
} else {
    echo "Could not parse MAILER_DSN: $dsn\n";
}

echo "\n--- Symfony Mailer Test ---\n";
try {
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();
    $mailer = $container->get('mailer.mailer');
    
    $email = (new \Symfony\Component\Mime\Email())
        ->from('smitbambharoliya76@gmail.com')
        ->to('smitbambharoliya76@gmail.com') // send to self for test
        ->subject('ServiceHub Test Email - ' . date('H:i:s'))
        ->text('This is a test email from ServiceHub. If you see this, email is working!');
    
    $mailer->send($email);
    echo "✅ Email sent successfully!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
        echo "Cause: " . $e->getPrevious()->getMessage() . "\n";
    }
}
