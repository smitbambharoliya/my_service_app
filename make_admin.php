<?php

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Get the first user and make them an admin
$user = $em->getRepository(User::class)->findOneBy([]);

if ($user) {
    if (!in_array('ROLE_ADMIN', $user->getRoles())) {
        $roles = $user->getRoles();
        $roles[] = 'ROLE_ADMIN';
        $user->setRoles($roles);
        $em->flush();
        echo "Successfully granted ROLE_ADMIN to user: " . $user->getEmail() . "\n";
    } else {
        echo "User " . $user->getEmail() . " already has ROLE_ADMIN.\n";
    }
} else {
    echo "No users found in the database.\n";
}
