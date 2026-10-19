<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Nova;
use Oneduo\NovaFileManager\Support\Asset;
use Workbench\App\Models\Page;
use Workbench\App\Nova\TestResource;
use Workbench\App\Nova\TestResourceEditableOnDetail;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config(['database.default' => 'testing']);

    Schema::create('pages', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug');
        $table->json('image')->nullable();
        $table->json('content')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
    });

    Nova::resources([TestResourceEditableOnDetail::class, TestResource::class]);

    $this->user = (new User())->forceFill(['id' => 42]);
    $this->page = Page::forceCreate(['title' => 'Page', 'slug' => 'page']);
    $this->payload = [
        'resourceId' => $this->page->getKey(),
        'attribute' => 'image',
        'image' => json_encode([['disk' => 'public', 'path' => 'image.jpg']]),
    ];
});

it('can update the field from the detail view', function () {
    actingAs($this->user)
        ->postJson(route('nova-file-manager.field.update', TestResourceEditableOnDetail::uriKey()), $this->payload)
        ->assertOk();

    expect($this->page->fresh()->image)
        ->toBeInstanceOf(Asset::class)
        ->path->toBe('image.jpg');
});

it('cannot update a field that is not editable on detail', function () {
    DB::table('users')->insert(['id' => 1]);

    actingAs($this->user)
        ->postJson(route('nova-file-manager.field.update', TestResource::uriKey()), [
            ...$this->payload,
            'resourceId' => 1,
        ])
        ->assertJsonValidationErrors(['field']);
});

it('cannot update the field when the resource cannot be updated', function () {
    Gate::policy(Page::class, get_class(new class {
        public function update(): bool
        {
            return false;
        }
    }));

    actingAs($this->user)
        ->postJson(route('nova-file-manager.field.update', TestResourceEditableOnDetail::uriKey()), $this->payload)
        ->assertJsonValidationErrors(['field']);

    expect($this->page->fresh()->image)->toBeNull();
});
