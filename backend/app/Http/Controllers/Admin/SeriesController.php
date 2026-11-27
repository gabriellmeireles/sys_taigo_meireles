<?php

namespace App\Http\Controllers\Admin;

use App\Models\Series;

class SeriesController extends TaxonomyController
{
    protected function model(): string { return Series::class; }
    protected function noun(): string { return 'série'; }
    protected function translationTable(): string { return 'series_translations'; }
    protected function foreignKey(): string { return 'series_id'; }
}
