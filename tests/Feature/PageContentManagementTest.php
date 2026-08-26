<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Locks the CMS page-update contract:
 * content merges (never wipes), image URL fields, file uploads and the generic editor.
 */
class PageContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->user->roles()->attach($role);
    }

    private function homePage(): Page
    {
        return Page::create([
            'slug' => 'home',
            'title' => 'Home Page',
            'content' => [
                'slide1_title' => 'Old Title',
                'slide1_image' => 'https://example.com/old.jpg',
                'banner_button' => 'কোর্সসমূহ দেখুন',
            ],
            'sections' => [],
            'is_active' => true,
        ]);
    }

    public function test_update_merges_content_instead_of_wiping_unsaved_keys(): void
    {
        $page = $this->homePage();

        $this->actingAs($this->user)->put(route('dashboard.cms.update', $page), [
            'title' => 'Home Page',
            'content' => ['slide1_title' => 'New Title'],
        ])->assertSessionHas('success');

        $fresh = $page->fresh();
        $this->assertSame('New Title', $fresh->getContent('slide1_title'));
        $this->assertSame('https://example.com/old.jpg', $fresh->getContent('slide1_image'));
        $this->assertSame('কোর্সসমূহ দেখুন', $fresh->getContent('banner_button'));
    }

    public function test_image_url_submission_sets_content_value(): void
    {
        $page = $this->homePage();

        // Browsers submit the field as content[slide1_image]_url; PHP's variable
        // parser drops the trailing "_url" and lands the value at content[slide1_image].
        $this->actingAs($this->user)->put(route('dashboard.cms.update', $page), [
            'title' => 'Home Page',
            'content' => ['slide1_image' => 'https://example.com/new.jpg'],
        ]);

        $this->assertSame('https://example.com/new.jpg', $page->fresh()->getContent('slide1_image'));
    }

    public function test_cleared_image_field_falls_back_to_view_default(): void
    {
        $page = $this->homePage();

        $this->actingAs($this->user)->put(route('dashboard.cms.update', $page), [
            'title' => 'Home Page',
            'content' => ['slide1_image' => ''],
        ]);

        $this->assertNull($page->fresh()->getContent('slide1_image'));
    }

    public function test_file_upload_is_stored_and_wired_into_content(): void
    {
        Storage::fake('public');
        $page = $this->homePage();

        $this->actingAs($this->user)->put(route('dashboard.cms.update', $page), [
            'title' => 'Home Page',
            'content' => ['slide1_image' => UploadedFile::fake()->image('slide.jpg')],
        ]);

        $value = $page->fresh()->getContent('slide1_image');
        $this->assertNotNull($value);
        $this->assertStringStartsWith('storage/cms/', $value);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $value));
    }

    public function test_generic_editor_replaces_content_with_key_value_pairs(): void
    {
        $page = $this->homePage();

        $this->actingAs($this->user)->put(route('dashboard.cms.update', $page), [
            'title' => 'Home Page',
            'content_keys' => ['custom_key'],
            'content_values' => ['custom value'],
        ]);

        $fresh = $page->fresh();
        $this->assertSame('custom value', $fresh->getContent('custom_key'));
        $this->assertNull($fresh->getContent('slide1_title'));
    }

    public function test_services_and_team_editors_render(): void
    {
        $this->actingAs($this->user)->get(route('dashboard.cms.services'))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard.cms.team'))->assertOk();
    }
}