<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;

class ShopifyController extends Controller
{
    public function auth(Request $request)
    {
        Log::info('authenticate triggered');
        $shop = $request->input('shop');
        $apiKey = env('SHOPIFY_API_KEY');
        $scopes = env('SHOPIFY_API_SCOPES');
        $redirectUri = env('SHOPIFY_REDIRECT_URI');

        if (!$shop) {
            return response()->json(['error' => 'Missing shop parameter'], 400);
        }

        $installUrl = "https://{$shop}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}";

        return redirect($installUrl);
    }

    public function callback(Request $request)
    {
        // Log the entire request data
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
            
            // Save the shop and access token to the database
            Shop::updateOrCreate(
                ['shop' => $shop],
                ['access_token' => $accessToken]
            );

            return redirect('/products'); // Redirect to the product list or any other page
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
            return response()->json(['error' => 'Unable to obtain access token'], $response->status());
        }
    }

    public function showProducts(Request $request)
    {
        $shop = Shop::first(); // Assuming you only have one shop for now
        if (!$shop) {
            return redirect('/auth'); // Redirect to the auth process if no shop is found
        }

        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shopUrl}/admin/api/2023-04/products.json");

        if ($response->successful()) {
            $products = $response->json()['products'];
            return view('products', compact('products'));
        } else {
            Log::error('Failed to fetch products', ['response' => $response->body()]);
            return response()->json(['error' => 'Unable to fetch products'], $response->status());
        }
    }


    public function upsertProduct(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'id' => 'nullable|string', // Optional for creation
            'title' => 'required|string',
            'body_html' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'required|array', // Ensure the image data is provided
            'image.src' => 'required|url', // Validate the image URL
            // Add other fields as per Shopify REST API documentation
        ]);

        // Fetch the shop details from the database (assuming you have stored them)
        $shop = Shop::first(); // Retrieve the first shop record
        if (!$shop) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }

        // Extract the access token and shop URL from the retrieved shop record
        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;

        // Prepare the product data
        $productData = [
            'product' => [
                'title' => $validatedData['title'],
                'body_html' => $validatedData['body_html'],
                'variants' => [
                    [
                        'price' => $validatedData['price'],
                        // Add other variant fields as per Shopify REST API documentation
                    ]
                ],
                'images' => [
                    [
                        'src' => $validatedData['image']['src']
                        // Add other image fields as per Shopify REST API documentation
                    ]
                ]
            ]
        ];

        // Determine whether to create or update the product based on the presence of an ID
        if (isset($validatedData['id'])) {
            // Update existing product
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->put("https://{$shopUrl}/admin/api/2023-04/products/{$validatedData['id']}.json", $productData);
        } else {
            // Create new product
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://{$shopUrl}/admin/api/2023-04/products.json", $productData);
        }

        // Check if the request was successful
        if ($response->successful()) {
            return response()->json(['message' => 'Product upserted successfully', 'product' => $response->json()], 200);
        } else {
            // Log an error if the request to Shopify API failed
            Log::error('Failed to upsert product', ['response' => $response->body()]);
            return response()->json(['error' => 'Failed to upsert product'], $response->status());
        }
    }

    public function deleteProduct($id)
    {
        // Fetch the shop details from the database (assuming you have stored them)
        $shop = Shop::first(); // Retrieve the first shop record
        if (!$shop) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }

        // Extract the access token and shop URL from the retrieved shop record
        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;

        // Make an HTTP DELETE request to Shopify Admin API to delete the product
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->delete("https://{$shopUrl}/admin/api/2023-04/products/{$id}.json");

        // Check if the request was successful
        if ($response->successful()) {
            return response()->json(['message' => 'Product deleted successfully'], $response->status());
        } else {
            // Log an error if the request to Shopify API failed
            Log::error('Failed to delete product', ['response' => $response->body()]);
            return response()->json(['error' => 'Failed to delete product'], $response->status());
        }
    }

    public function createProduct(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'id' => 'nullable|string', // Optional for creation
            'title' => 'required|string',
            'body_html' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'required|url', // Validate the image URL
            // Add other fields as per Shopify REST API documentation
        ]);

        // Fetch the shop details from the database (assuming you have stored them)
        $shop = Shop::first(); // Retrieve the first shop record
        if (!$shop) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }

        // Extract the access token and shop URL from the retrieved shop record
        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;

        // Prepare the product data
        $productData = [
            'product' => [
                'title' => $validatedData['title'],
                'body_html' => $validatedData['body_html'],
                'variants' => [
                    [
                        'price' => $validatedData['price'],
                        // Add other variant fields as per Shopify REST API documentation
                    ]
                ],
                'images' => [
                    [
                        'src' => $validatedData['image']
                        // Add other image fields as per Shopify REST API documentation
                    ]
                ]
            ]
        ];

        if (isset($validatedData['id'])) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->put("https://{$shopUrl}/admin/api/2023-04/products/{$validatedData['id']}.json", $productData);
        } else {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://{$shopUrl}/admin/api/2023-04/products.json", $productData);
        }

        // Check if the request was successful
        if ($response->successful()) {
            return redirect()->back()->with('success', 'Product upserted successfully');
        } else {
            Log::error('Failed to upsert product', ['response' => $response->body()]);
            return redirect()->back()->with('error', 'Failed to upsert product');
        }
    }

    public function deleteProducts($id)
    {
        $shop = Shop::first(); // Retrieve the first shop record
        if (!$shop) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }

        // Extract the access token and shop URL from the retrieved shop record
        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->delete("https://{$shopUrl}/admin/api/2023-04/products/{$id}.json");

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Product deleted successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to delete product.');
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $shop = Shop::first(); // Retrieve the first shop record
        if (!$shop) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }
        // Extract the access token and shop URL from the retrieved shop record
        $accessToken = $shop->access_token;
        $shopUrl = $shop->shop;
 

        $data = [
            'product' => [
                'id' => $id,
                'title' => $request->input('title'),
                'body_html' => $request->input('body_html'),
                'variants' => [
                    [
                        'id' => $request->input('variant_id'),
                        'price' => $request->input('price'),
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->put("https://{$shopUrl}/admin/api/2023-04/products/{$id}.json", $data);

        if ($response->successful()) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }
}
