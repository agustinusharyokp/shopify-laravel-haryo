<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Tenants;
use Illuminate\Support\Facades\Http;

class RedirController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Callback request data:', $request->all());

        $shop = $request->input('shop');
        $code = $request->input('code');

        if (!$shop || !$code) {
            return response()->json(['error' => 'Missing shop or code parameter'], 400);
        }

        $apiKey = env('SHOPIFY_API_KEY');
        $apiSecret = env('SHOPIFY_API_SECRET');

        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
            'code' => $code,
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];

            Tenants::updateOrCreate(
                ['domain' => $shop],
                ['token' => $accessToken],
            );

            return redirect('/');
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
            return response()->json(['error' => 'Unable to obtain access token'], $response->status());
        }
    }
}
