<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('attachments');
        parent::tearDown();
    }

    public function test_uploads_are_limited_by_type_and_size(): void
    {
        $h = $this->owner();
        $this->post('/api/attachments', ['file' => UploadedFile::fake()->create('evil.exe', 10)], $h + ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/attachments', ['file' => UploadedFile::fake()->create('big.pdf', 6000)], $h + ['Accept' => 'application/json'])->assertStatus(422);
        $res = $this->post('/api/attachments', ['file' => UploadedFile::fake()->create('Price list.pdf', 20, 'application/pdf')], $h + ['Accept' => 'application/json'])->assertCreated();
        $this->assertSame('Price list.pdf', $res->json('name'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $res->json('token'));
    }

    public function test_marked_files_are_attached_to_the_sent_email_and_other_companies_cannot_use_them(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
        $token = $this->post('/api/attachments', ['file' => UploadedFile::fake()->createWithContent('guide.pdf', '%PDF-1.4 hello')], $h + ['Accept' => 'application/json'])->json('token');

        $send = function (array $h, string $tok) {
            $id = $this->postJson('/api/campaigns', ['name' => 'C'.$tok, 'type' => 'email', 'subject' => 'S', 'body' => "<p>Hi</p><!--fc-attach:$tok-->"], $h)->json('id');
            $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        };
        $send($h, $token);
        $raw = Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($m) => $m->toString())->implode("\n");
        $this->assertStringContainsString('filename=guide.pdf', $raw);

        // a second company pointing at the same token gets no attachment
        $this->app['auth']->forgetGuards();
        $h2 = $this->owner('p@y.com');
        $this->postJson('/api/people', ['email' => 'bob@y.com'], $h2)->assertCreated();
        Mail::mailer()->getSymfonyTransport()->flush();
        $send($h2, $token);
        $raw2 = Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($m) => $m->toString())->implode("\n");
        $this->assertStringNotContainsString('guide.pdf', $raw2);
    }
    public function test_a_forms_auto_reply_carries_its_attachments(): void
    {
        $h = $this->owner();
        $token = $this->post('/api/attachments', ['file' => UploadedFile::fake()->createWithContent('brochure.pdf', '%PDF-1.4 hi')], $h + ['Accept' => 'application/json'])->json('token');
        $form = $this->postJson('/api/forms', [
            'name' => 'F', 'autoreply' => true, 'autoreply_subject' => 'Thanks', 'autoreply_body' => 'Here you go',
            'autoreply_attachments' => [['token' => $token, 'name' => 'x'], ['token' => str_repeat('a', 32), 'name' => 'not mine']],
        ], $h)->assertCreated();
        $this->assertSame(['brochure.pdf'], array_column($form->json('autoreply_attachments'), 'name')); // unknown tokens are dropped

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['email' => 'p@x.com'])->assertOk();
        $raw = Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($m) => $m->toString())->implode("
");
        $this->assertStringContainsString('filename=brochure.pdf', $raw);

        $off = $this->putJson('/api/forms/'.$form->json('id'), ['name' => 'F', 'autoreply' => false, 'autoreply_attachments' => [['token' => $token]]], $h)->assertOk();
        $this->assertEmpty($off->json('autoreply_attachments'));
    }
    public function test_campaign_attachments_go_with_the_campaign_and_the_test_email(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
        $up = $this->post('/api/attachments', ['file' => UploadedFile::fake()->createWithContent('offer.pdf', '%PDF-1.4 offer')], $h + ['Accept' => 'application/json'])->json();
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<p>Hi</p>', 'attachments' => [$up, ['token' => str_repeat('b', 32), 'name' => 'ghost']]], $h)->assertCreated();
        $this->assertSame(['offer.pdf'], array_column($id->json('attachments'), 'name'));
        $id = $id->json('id');

        $this->postJson("/api/campaigns/$id/test", ['to' => 'me@x.com'], $h)->assertOk();
        $this->assertStringContainsString('filename=offer.pdf', Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($m) => $m->toString())->implode("
"));

        Mail::mailer()->getSymfonyTransport()->flush();
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        $this->assertStringContainsString('filename=offer.pdf', Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($m) => $m->toString())->implode("
"));

        // SMS campaigns never keep attachments
        $sms = $this->postJson('/api/campaigns', ['name' => 'T', 'type' => 'sms', 'body' => 'hi', 'attachments' => [$up]], $h)->assertCreated();
        $this->assertEmpty($sms->json('attachments'));
    }

    public function test_templates_keep_attachments(): void
    {
        $h = $this->owner();
        $up = $this->post('/api/attachments', ['file' => UploadedFile::fake()->createWithContent('t.pdf', '%PDF-1.4 t')], $h + ['Accept' => 'application/json'])->json();
        $t = $this->postJson('/api/templates', ['name' => 'T', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>', 'attachments' => [$up]], $h)->assertCreated();
        $this->assertSame(['t.pdf'], array_column($t->json('attachments'), 'name'));
    }
}
