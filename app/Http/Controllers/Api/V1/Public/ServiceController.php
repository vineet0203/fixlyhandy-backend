<?php
namespace App\Http\Controllers\Api\V1\Public;
use App\Http\Controllers\Controller;
use App\Models\Service;
class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return response()->json(['data' => $services]);
    }
}
