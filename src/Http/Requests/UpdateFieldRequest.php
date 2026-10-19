<?php

declare(strict_types=1);

namespace Oneduo\NovaFileManager\Http\Requests;

use Laravel\Nova\Resource;
use Oneduo\NovaFileManager\FileManager;

class UpdateFieldRequest extends BaseRequest
{
    public function editedResource(): Resource
    {
        return once(fn () => $this->findResourceOrFail());
    }

    /**
     * Resolved directly from the resource, ignoring wrapper/flexible params
     */
    public function field(): FileManager
    {
        return once(fn () => $this->editedResource()
            ->availableFields($this)
            ->whereInstanceOf(FileManager::class)
            ->findFieldByAttribute($this->attribute, function () {
                abort(404);
            }));
    }

    public function authorize(): bool
    {
        return $this->field()->allowEditOnDetail
            && !$this->field()->isReadonly($this)
            && $this->editedResource()->authorizedToUpdate($this);
    }

    public function rules(): array
    {
        return $this->field()->getUpdateRules($this);
    }
}
