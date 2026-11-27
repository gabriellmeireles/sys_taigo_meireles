<?php

namespace App\Http\Controllers\Admin;

use App\Models\Technique;

class TechniqueController extends TaxonomyController
{
    protected function model(): string { return Technique::class; }
    protected function noun(): string { return 'técnica'; }
    protected function translationTable(): string { return 'technique_translations'; }
    protected function foreignKey(): string { return 'technique_id'; }
}
