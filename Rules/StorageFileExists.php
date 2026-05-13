<?php

namespace Modules\Crm\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class StorageFileExists implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        if (file_exists($value)) {
            return true;
        }

        return Storage::disk(config('filesystems.default'))->exists($value)
            || Storage::disk('local')->exists($value);
    }

    public function message(): string
    {
        return 'The selected file does not exist.';
    }
}
