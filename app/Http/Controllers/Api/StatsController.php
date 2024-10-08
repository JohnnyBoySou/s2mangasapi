<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Collection;

class StatsController extends Controller
{
    public function getStatistics()
    {
        $totalUsers = User::count();
        $totalCollections = Collection::count();

        return response()->json([
            'total_users' => $totalUsers,
            'total_collections' => $totalCollections,
        ]);
    }
}
