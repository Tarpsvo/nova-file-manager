<?php

declare(strict_types=1);

namespace Oneduo\NovaFileManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Oneduo\NovaFileManager\Http\Requests\IndexRequest;

class IndexController extends Controller
{
    /**
     * Get the data for the tool
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(IndexRequest $request)
    {
        $manager = $request->manager();

        $allItems = collect([...$manager->directories(), ...$manager->files()]);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $manager
            ->paginate($allItems)
            ->onEachSide(1);

        $folders = collect($paginator->items())->where('type', 'folder')->values();
        $files = collect($paginator->items())->where('type', '!=', 'folder')->values();

        return response()->json([
            'disk' => $manager->getDisk(),
            'breadcrumbs' => $manager->breadcrumbs(),
            'folders' => $folders,
            'files' => $files,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection()->toArray(),
            ],
        ]);
    }
}
