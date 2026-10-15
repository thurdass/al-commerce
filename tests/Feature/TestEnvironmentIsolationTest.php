<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestEnvironmentIsolationTest extends TestCase
{
    public function test_database_uses_sqlite_in_memory_instead_of_the_development_database(): void
    {
        $connection = DB::connection();

        $this->assertSame('sqlite', $connection->getDriverName());
        $this->assertSame(':memory:', $connection->getDatabaseName());
    }

    public function test_mail_uses_an_in_memory_transport_instead_of_smtp(): void
    {
        $transport = Mail::mailer()->getSymfonyTransport();

        $this->assertInstanceOf(ArrayTransport::class, $transport);
    }
}
