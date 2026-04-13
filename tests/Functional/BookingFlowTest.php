<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingFlowTest extends WebTestCase
{
    public function testDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        // Attempt to access user dashboard without authentication
        $client->request('GET', '/dashboard/customer'); // Assuming this route exists or /my-bookings

        // Should be redirected to login page
        $this->assertResponseRedirects('/login');
    }
    
    public function testHomepageIsPublicAccessible(): void
    {
        $client = static::createClient();
        
        // The homepage should be public
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.archive-section');
    }
}
