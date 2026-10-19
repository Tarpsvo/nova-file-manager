<?php

declare(strict_types=1);

namespace Oneduo\NovaFileManager\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Oneduo\NovaFileManager\Http\Requests\UpdateFieldRequest;

class FieldController extends Controller
{
    /**
     * Update the field value from the detail view
     */
    public function __invoke(UpdateFieldRequest $request): JsonResponse
    {
        $model = $request->editedResource()->model();

        $callback = $request->field()->fill($request, $model);

        $model->save();

        if ($callback instanceof Closure) {
            $callback();
        }

        return response()->json([
            'message' => __('nova-file-manager::messages.field.update'),
        ]);
    }
}
