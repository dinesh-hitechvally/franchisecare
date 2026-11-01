<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Media;
use App\Services\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyLogoTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_logo_tag_is_the_logo_chosen_from_media(): void
    {
        Storage::fake('local');
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'O', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        $company = Company::first();
        $r = new MessageRenderer;

        $this->assertSame('', $r->replace('{{company_logo}}', $r->vars($company, null, 'email'))); // no logo chosen yet

        $id = $this->post('/api/media', ['file' => UploadedFile::fake()->image('logo.png', 50, 50)], $h)->assertCreated()->json('id');
        $this->putJson('/api/settings', ['name' => 'Acme', 'profile' => ['logo_media_id' => $id]], $h)->assertOk();

        $out = $r->replace('<img src="{{ company_logo }}">', $r->vars($company->fresh(), null, 'email'));
        $this->assertStringContainsString(Media::find($id)->url, $out);
    }
}
