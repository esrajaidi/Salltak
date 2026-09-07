<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use App\Support\SiteContentDefaults;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SiteContentDefaults::sections() as $definition) {
            SiteSection::query()->firstOrCreate(
                ['slug'=>$definition['slug']],
                [
                    'label'=>$definition['label'],
                    'content'=>$definition['content'], 'draft_content'=>$definition['content'],
                    'is_visible'=>$definition['is_visible'], 'draft_is_visible'=>$definition['is_visible'],
                    'sort_order'=>$definition['sort_order'], 'draft_sort_order'=>$definition['sort_order'],
                    'published_at'=>now(),
                ]
            );
        }
    }
}
