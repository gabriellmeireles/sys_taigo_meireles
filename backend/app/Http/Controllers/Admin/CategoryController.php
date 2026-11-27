<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;

class CategoryController extends TaxonomyController
{
    protected function model(): string { return Category::class; }
    protected function noun(): string { return 'categoria'; }
    protected function translationTable(): string { return 'category_translations'; }
    protected function foreignKey(): string { return 'category_id'; }
}
