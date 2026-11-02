<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Services\Attachments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentFromMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_a_media_file_becomes_an_attachment_copy_that_survives_deleting_the_media(): void
    {
        $h = $this->owner();
        $id = $this->post('/api/media', ['file' => UploadedFile::fake()->createWithContent('Price list.pdf', '%PDF-1.4 hello')], $h)->assertCreated()->json('id');

        $a = $this->postJson('/api/attachments/from-media', ['media_id' => $id], $h)->assertCreated()->json();
        $this->assertSame('Price list.pdf', $a['name']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $a['token']);

        $this->deleteJson("/api/media/{$id}", [], $h)->assertNoContent();
        $company = (int) DB::table('companies')->value('id');
        $this->assertCount(1, Attachments::in(Attachments::marker([$a]), $company)); // the email still has its file
    }

    public function test_unsuitable_or_foreign_media_is_refused(): void
    {
        $a = $this->owner('a@x.com', 'A');
        $big = $this->post('/api/media', ['file' => UploadedFile::fake()->createWithContent('big.txt', str_repeat('a', 5632 * 1024))], $a)->assertCreated()->json('id'); // fits media (8 MB), not an email (5 MB)
        $this->postJson('/api/attachments/from-media', ['media_id' => $big], $a)->assertStatus(422);

        $zipless = Media::count(); // keep the count honest: nothing was attached
        $this->assertSame(1, $zipless);

        $ok = $this->post('/api/media', ['file' => UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf')], $a)->assertCreated()->json('id');
        $b = $this->owner('b@x.com', 'B');
        $this->postJson('/api/attachments/from-media', ['media_id' => $ok], $b)->assertNotFound(); // another company's file
    }
}
