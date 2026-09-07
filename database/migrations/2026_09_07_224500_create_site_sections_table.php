<?php

use App\Support\SiteContentDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('label', 160);
            $table->json('content')->nullable();
            $table->json('draft_content')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('draft_is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->unsignedInteger('draft_sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach (SiteContentDefaults::sections() as $section) {
            DB::table('site_sections')->insert([
                'slug'=>$section['slug'], 'label'=>$section['label'],
                'content'=>json_encode($section['content'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'draft_content'=>json_encode($section['content'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'is_visible'=>$section['is_visible'], 'draft_is_visible'=>$section['is_visible'],
                'sort_order'=>$section['sort_order'], 'draft_sort_order'=>$section['sort_order'],
                'published_at'=>$now, 'created_at'=>$now, 'updated_at'=>$now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sections');
    }
};
