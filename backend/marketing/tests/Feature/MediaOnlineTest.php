<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Services\OnlineImageImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaOnlineTest extends TestCase
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

    protected function png(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(40, 20));

        return ob_get_clean();
    }

    protected function photo(array $over = []): array
    {
        return $over + [
            'url' => 'https://8.8.8.8/full.png', 'thumb' => 'https://8.8.4.4/thumb.png', 'source' => 'Openverse', 'author' => 'Jane Doe',
            'author_url' => 'https://example.org/jane', 'license' => 'CC BY 2.0', 'link' => 'https://example.org/photo/1',
        ];
    }

    protected function vouch(array $photo, ?int $company = null): void
    {
        OnlineImageImport::vouch($company ?? (int) DB::table('companies')->orderBy('id')->value('id'), [$photo]);
    }

    public function test_a_searched_image_is_copied_into_the_library_with_its_credit(): void
    {
        $h = $this->owner();
        Http::fake(['8.8.8.8/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
        $p = $this->photo();
        $this->vouch($p);

        $m = $this->postJson('/api/media/import', $p, $h)->assertCreated()->json();
        $this->assertSame(['openverse', 'Jane Doe', 'CC BY 2.0', 'image', 'image/png', 40, 20, 'Photo by Jane Doe'], [$m['source'], $m['credit'], $m['license'], $m['kind'], $m['mime'], $m['width'], $m['height'], $m['name']]);
        Storage::disk('local')->assertExists(DB::table('media')->value('path'));

        // adding the same photo again returns the copy we already have
        $this->postJson('/api/media/import', $p, $h)->assertOk()->assertJsonPath('id', $m['id']);
        $this->assertSame(1, Media::count());
    }

    public function test_an_address_the_search_did_not_return_is_refused(): void
    {
        $h = $this->owner();
        Http::fake();
        $this->postJson('/api/media/import', $this->photo(), $h)->assertStatus(422)->assertJsonPath('message', 'Search for the image first, then add it.');
        Http::assertNothingSent();
        $this->assertSame(0, Media::count());
    }

    public function test_private_hosts_and_non_images_are_refused(): void
    {
        $h = $this->owner();
        Http::fake(['8.8.8.8/*' => Http::response('<html>nope</html>', 200)]);

        $private = $this->photo(['url' => 'http://127.0.0.1/secret.png', 'thumb' => null, 'link' => 'https://example.org/p/2']);
        $this->vouch($private);
        $this->postJson('/api/media/import', $private, $h)->assertStatus(422);

        $html = $this->photo(['thumb' => null, 'link' => 'https://example.org/p/3']);
        $this->vouch($html);
        $this->postJson('/api/media/import', $html, $h)->assertStatus(422)->assertJsonPath('message', 'That is not a PNG, JPG, GIF or WebP image.');
        $this->assertSame(0, Media::count());
    }

    public function test_the_preview_is_used_when_the_original_cannot_be_downloaded(): void
    {
        $h = $this->owner();
        Http::fake(['8.8.8.8/*' => Http::response('gone', 404), '8.8.4.4/*' => Http::response($this->png(), 200)]);
        $p = $this->photo();
        $this->vouch($p);
        $this->postJson('/api/media/import', $p, $h)->assertCreated()->assertJsonPath('source', 'openverse');
    }

    public function test_the_library_can_be_filtered_by_where_images_came_from(): void
    {
        $h = $this->owner();
        $up = $this->post('/api/media', ['file' => UploadedFile::fake()->image('mine.png', 20, 20)], $h)->assertCreated()->json('id');
        Http::fake(['8.8.8.8/*' => Http::response($this->png(), 200)]);
        $p = $this->photo();
        $this->vouch($p);
        $on = $this->postJson('/api/media/import', $p, $h)->assertCreated()->json('id');

        $ids = fn (string $q) => collect($this->getJson('/api/media?'.$q, $h)->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$up, $on], $ids(''));
        $this->assertSame([$up], $ids('origin=uploaded'));
        $this->assertSame([$on], $ids('origin=online'));
        $this->assertSame([$on], $ids('source=openverse'));
    }

    public function test_another_companys_search_does_not_allow_an_import(): void
    {
        $this->owner('a@x.com', 'A');
        $b = $this->owner('b@x.com', 'B');
        Http::fake(['8.8.8.8/*' => Http::response($this->png(), 200)]);
        $p = $this->photo();
        $this->vouch($p, (int) DB::table('companies')->orderBy('id')->value('id')); // company A searched, B did not
        $this->postJson('/api/media/import', $p, $b)->assertStatus(422);
        $this->assertSame(0, Media::withoutGlobalScopes()->count());
    }
}
