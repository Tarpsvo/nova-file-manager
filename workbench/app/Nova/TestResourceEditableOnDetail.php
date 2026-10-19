<?php

declare(strict_types=1);

namespace Workbench\App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Oneduo\NovaFileManager\FileManager;
use Workbench\App\Models\Page;

class TestResourceEditableOnDetail extends Resource
{
    public static $model = Page::class;

    public function fields(NovaRequest $request): array
    {
        return [
            FileManager::make('Image')->allowEditOnDetail(),
        ];
    }
}
