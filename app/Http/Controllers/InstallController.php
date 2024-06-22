<?php

namespace App\Http\Controllers;

use App\Models\Tenants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{

    public function index(Request $request)
    {
        Log::info('authenticate triggered');
        $tenant = $request->input('shop');
        $apiKey = env('SHOPIFY_API_KEY');
        $scopes = env('SHOPIFY_API_SCOPES');
        $redirectUri = env('SHOPIFY_REDIRECT_URI');
        $accessMode = 'offline';

        Log::info('aman');

        if (!$tenant) {
            return response()->json(['error' => 'Missing shop parameter'], 400);
        }
        Log::info('try');
        $installUrl = "https://{$tenant}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}&grant_options[]={$accessMode}";
        Log::info($installUrl);
        return redirect($installUrl);
    }
    
}
