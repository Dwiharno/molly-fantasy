<?php

namespace App\Repositories;

use App\Models\SubCategory;

class SubCategoryRepository extends BaseRepository
{
    public function __construct(SubCategory $model)
    {
        parent::__construct($model);
    }

    public function byCategory(int $categoryId)
    {
        return $this->model->where('category_id', $categoryId)->where('is_active', true)->get();
    }

    public function paginateWithCategory(int $perPage = 15)
    {
        return $this->model->with('category')->latest()->paginate($perPage);
    }
}
