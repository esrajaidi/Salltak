<?php

namespace Tests\Feature;

use App\Models\SiteSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContentCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_draft_without_changing_published_home_content(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $hero = SiteSection::query()->where('slug', 'hero')->firstOrFail();
        $publishedTitle = $hero->content['title'];

        $this->actingAs($admin)->put(route('admin.site-content.update', $hero), [
            'title' => 'عنوان تجريبي للمسودة',
            'description' => 'وصف تجريبي للمسودة',
            'is_visible' => '1',
            'sort_order' => 10,
        ])->assertRedirect();

        $hero->refresh();
        $this->assertSame($publishedTitle, $hero->content['title']);
        $this->assertSame('عنوان تجريبي للمسودة', $hero->draft_content['title']);
    }

    public function test_public_home_does_not_render_hidden_published_section(): void
    {
        $this->seed();
        $hero = SiteSection::query()->where('slug', 'testimonials')->firstOrFail();
        $hero->update(['is_visible' => false]);

        $this->get(route('home'))->assertOk()->assertDontSee('آراء العملاء');
    }
}
