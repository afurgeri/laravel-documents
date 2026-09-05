<?php

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $extension
 * @property string|null $mime_type
 * @property int $size
 * @property string $checksum
 */
#[Fillable([
    'disk',
    'path',
    'original_name',
    'extension',
    'mime_type',
    'size',
    'checksum',
])]
class Document extends Model
{
    public function getTable(): string
    {
        return (string) config('documents.table', parent::getTable());
    }
}
