<?php

namespace Tests\Feature;

use App\Services\ServerMail;
use Tests\TestCase;

class ServerMailTest extends TestCase
{
    public function test_the_command_gets_the_flags_the_transport_needs_and_the_program_is_found(): void
    {
        config(['mail.server_command' => 'D:/laragon/bin/mailpit/1.22.3/mailpit.exe sendmail']);
        $this->assertSame('D:/laragon/bin/mailpit/1.22.3/mailpit.exe sendmail -t -i', ServerMail::command());
        $this->assertSame('D:/laragon/bin/mailpit/1.22.3/mailpit.exe', ServerMail::binary());
        $this->assertSame('mailpit.exe', ServerMail::name());
        $this->assertTrue(ServerMail::isCatcher());

        config(['mail.server_command' => '/usr/sbin/sendmail -bs -i']);
        $this->assertSame('/usr/sbin/sendmail -bs -i', ServerMail::command());   // already has a mode: left alone
        $this->assertFalse(ServerMail::isCatcher());

        config(['mail.server_command' => '"'.PHP_BINARY.'" some.php']);
        $this->assertSame(PHP_BINARY, ServerMail::binary());                      // quoted paths with spaces work
        $this->assertTrue(ServerMail::ready());

        config(['mail.server_command' => 'sendmail -t']);
        $this->assertTrue(ServerMail::ready());                                    // a bare name is looked up on the PATH when it runs

        config(['mail.server_command' => '/definitely/missing/sendmail -t']);
        $this->assertFalse(ServerMail::ready());

        config(['mail.server_command' => '']);
        $this->assertSame('', ServerMail::command());
        $this->assertFalse(ServerMail::ready());
    }
}
