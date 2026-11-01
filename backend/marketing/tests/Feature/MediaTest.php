<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function upload(array $h, ?UploadedFile $file = null, array $extra = [])
    {
        return $this->post('/api/media', ['file' => $file ?? UploadedFile::fake()->image('Our Logo.png', 200, 100)] + $extra, $h);
    }

    public function test_an_image_can_be_uploaded_and_is_listed_with_its_details(): void
    {
        $h = $this->owner();
        $m = $this->upload($h)->assertCreated()->json();

        $this->assertSame(['Our Logo', 'Our Logo.png', 'image', 200, 100, 'image/png'], [$m['name'], $m['file_name'], $m['kind'], $m['width'], $m['height'], $m['mime']]);
        $this->assertStringContainsString('/api/m/', $m['url']);
        $this->assertArrayNotHasKey('path', $m);   // the disk path and the token are never listed on their own
        $this->assertArrayNotHasKey('token', $m);
        $this->assertSame([$m['id']], collect($this->getJson('/api/media', $h)->json('data'))->pluck('id')->all());
        Storage::disk('local')->assertExists(DB::table('media')->value('path'));
    }

    public function test_only_allowed_and_real_files_are_accepted(): void
    {
        $h = $this->owner();
        $this->upload($h, UploadedFile::fake()->create('virus.exe', 10))->assertStatus(422);
        $this->upload($h, UploadedFile::fake()->create('huge.pdf', 9000, 'application/pdf'))->assertStatus(422);
        $this->upload($h, UploadedFile::fake()->createWithContent('fake.png', 'this is not an image'))->assertStatus(422)->assertJsonPath('message', 'That file is not a valid image.');
        $this->upload($h, UploadedFile::fake()->create('Price list.pdf', 30, 'application/pdf'))->assertCreated()->assertJsonPath('kind', 'document');
        $this->assertSame(1, Media::count());
    }

    public function test_the_file_is_served_by_its_token_and_images_show_inline(): void
    {
        $h = $this->owner();
        $img = $this->upload($h)->json();
        $doc = $this->upload($h, UploadedFile::fake()->create('Price list.pdf', 30, 'application/pdf'))->json();

        $res = $this->get(str_replace(url('/'), '/', $img['url']))->assertOk(); // no sign-in needed
        $this->assertStringStartsWith('inline', $res->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('attachment', $this->get(str_replace(url('/'), '/', $doc['url']))->headers->get('Content-Disposition'));
        $this->get('/api/m/'.str_repeat('a', 40).'/x.png')->assertNotFound();
        $this->get('/api/m/short/x.png')->assertNotFound();
    }

    public function test_rename_filter_and_soft_delete(): void
    {
        $h = $this->owner();
        $img = $this->upload($h, null, ['name' => 'Spring banner'])->json();
        $doc = $this->upload($h, UploadedFile::fake()->create('Terms.pdf', 10, 'application/pdf'))->json();

        $this->putJson("/api/media/{$img['id']}", ['name' => 'Summer banner', 'alt' => 'A sunny banner'], $h)->assertOk()->assertJsonPath('name', 'Summer banner')->assertJsonPath('alt', 'A sunny banner');
        $this->assertSame([$img['id']], collect($this->getJson('/api/media?kind=image', $h)->json('data'))->pluck('id')->all());
        $this->assertSame([$doc['id']], collect($this->getJson('/api/media?kind=document', $h)->json('data'))->pluck('id')->all());
        $this->assertSame([$img['id']], collect($this->getJson('/api/media?search=summer', $h)->json('data'))->pluck('id')->all());
        $this->assertSame([], $this->getJson('/api/media?from=2999-01-01', $h)->json('data'));

        $this->deleteJson("/api/media/{$doc['id']}", [], $h)->assertNoContent();
        $this->assertNotNull(DB::table('media')->where('id', $doc['id'])->value('deleted_at')); // soft delete: the row stays
        $this->get(str_replace(url('/'), '/', $doc['url']))->assertNotFound();                    // but its link stops working
    }

    public function test_each_company_only_sees_and_changes_its_own_media(): void
    {
        $h = $this->owner();
        $m = $this->upload($h)->json();
        $h2 = $this->owner('p@y.com', 'Other');

        $this->assertSame([], $this->getJson('/api/media', $h2)->json('data'));
        $this->putJson("/api/media/{$m['id']}", ['name' => 'Hijacked'], $h2)->assertNotFound();
        $this->deleteJson("/api/media/{$m['id']}", [], $h2)->assertNotFound();
    }

    public function test_the_company_logo_is_chosen_from_media_only(): void
    {
        $h = $this->owner();
        $logo = $this->upload($h)->json();
        $doc = $this->upload($h, UploadedFile::fake()->create('Terms.pdf', 10, 'application/pdf'))->json();
        $body = fn ($id) => ['name' => 'Acme', 'profile' => ['logo_media_id' => $id]];

        // a typed address is no longer a thing
        $this->putJson('/api/settings', ['name' => 'Acme', 'profile' => ['logo_url' => 'https://example.com/x.png']], $h)->assertOk();
        $this->getJson('/api/settings', $h)->assertJsonMissingPath('profile.logo_url')->assertJsonPath('logo_url', null);

        $this->putJson('/api/settings', $body($logo['id']), $h)->assertOk()->assertJsonPath('profile.logo_media_id', $logo['id'])->assertJsonPath('logo_url', $logo['url']);
        $this->getJson('/api/me', $h)->assertJsonPath('company.logo_url', $logo['url']);   // what the sidebar shows

        $this->putJson('/api/settings', $body($doc['id']), $h)->assertStatus(422)->assertJsonValidationErrors('profile.logo_media_id'); // not an image
        $this->putJson('/api/settings', $body(99999), $h)->assertStatus(422);

        // another company's file cannot be used
        $h2 = $this->owner('p@y.com', 'Other');
        $this->putJson('/api/settings', $body($logo['id']), $h2)->assertStatus(422);

        $this->app['auth']->forgetGuards(); // back to the first company
        // the logo in use cannot be deleted; after choosing another, it can
        $this->deleteJson("/api/media/{$logo['id']}", [], $h)->assertStatus(422);
        $this->putJson('/api/settings', $body(null), $h)->assertOk();
        $this->getJson('/api/me', $h)->assertJsonPath('company.logo_url', null);
        $this->deleteJson("/api/media/{$logo['id']}", [], $h)->assertNoContent();
    }

    public function test_media_changes_are_mirrored_in_its_history(): void
    {
        $h = $this->owner();
        $m = $this->upload($h)->json();
        $this->putJson("/api/media/{$m['id']}", ['name' => 'Renamed'], $h)->assertOk();

        $rows = collect($this->getJson("/api/history/media/{$m['id']}", $h)->assertOk()->json());
        $this->assertEqualsCanonicalizing(['created', 'updated'], $rows->pluck('change_action')->all());
        $this->assertSame(['old' => 'Our Logo', 'new' => 'Renamed'], $rows->firstWhere('change_action', 'updated')['changes']['name']);
        $this->assertSame('••••••', DB::table('media_history')->value('token')); // the link token is never copied
    }
}
