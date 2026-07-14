<?php
namespace App\Http\Controllers\Api\V1\Public;
use App\Http\Controllers\Controller;
use App\Models\ServiceSubCategory;
class ServiceSubCategoryController extends Controller
{
    public function index()
    {
        $subCategories = ServiceSubCategory::all();
        return response()->json(['data' => $subCategories]);
    }
}
