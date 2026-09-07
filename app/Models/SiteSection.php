<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSection extends Model
{
    protected $fillable = [
        'slug','label','content','draft_content','is_visible','draft_is_visible',
        'sort_order','draft_sort_order','published_at',
    ];

    protected function casts(): array
    {
        return [
            'content'=>'array', 'draft_content'=>'array',
            'is_visible'=>'boolean', 'draft_is_visible'=>'boolean',
            'sort_order'=>'integer', 'draft_sort_order'=>'integer',
            'published_at'=>'datetime',
        ];
    }

    public function hasDraftChanges(): bool
    {
        return $this->draft_content !== $this->content
            || $this->draft_is_visible !== $this->is_visible
            || $this->draft_sort_order !== $this->sort_order;
    }

    public function publish(): void
    {
        $this->forceFill([
            'content' => $this->draft_content ?? $this->content ?? [],
            'is_visible' => $this->draft_is_visible,
            'sort_order' => $this->draft_sort_order,
            'published_at' => now(),
        ])->save();
    }
}
