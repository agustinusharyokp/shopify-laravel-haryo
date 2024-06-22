<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenants;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function upsertProduct(Request $request)
    {
        Log::info('upsertProduct triggered njing');
        $payload = $request->input('product');

        $tenant = Tenants::first(); 
        if (!$tenant) {
            return response()->json(['error' => 'Shop details not found'], 404);
        }
        $token = $tenant->token;
        $domain = $tenant->domain;

        $status = 'active';
        if ($payload['status'] == "Disabled") {
            $status = 'draft';
        }
        $productData = [
            'product' => [
                'title' => $payload['nama'],
                'body_html' => $payload['deskripsi'],
                'status' => $status,
                'variants' => [
                    [
                        'price' => $payload['harga'],
                        'sku' => $payload['kode'],
                        'weight' => $payload['berat'],
                        'weight_unit' => 'kg',
                        'inventory_management' => 'shopify',
                        'inventory_policy' => 'deny',
                    ],
                ],
                'images' => array_map(function ($image) {
                    return [
                        'src' => $image['image'],
                        'position' => $image['position'],
                        'alt' => $image['label'],
                    ];
                }, $payload['gambar']),
            ]
        ];

        try {
            $existingProductId = $this->getShopifyProductIdBySKU($payload['kode'], $token, $domain);
            if ($existingProductId) {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->put("https://{$domain}/admin/api/2023-04/products/{$existingProductId}.json", $productData);
            } else {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->post("https://{$domain}/admin/api/2023-04/products.json", $productData);
            }
        } catch (\Throwable $th) {
            Log::info($th->message());
        }

        if ($response->successful()) {
            Log::info($response->json());
            $this->createProduct($response->json()['product']);
            return response()->json(['success' => true, 'product' => $response->json()], 200);
        } else {
            return response()->json(['success' => false, 'error' => $response->json()], 400);
        }
    }

    public function createProduct($productData)
    {
        Products::updateOrCreate(
            ['shop_product_id' => $productData['id']],
            [
                'sku' => $productData['variants'][0]['sku'],
                'data' => json_encode($productData),
            ]
        );
    }

    public function getShopifyProductIdBySKU($sku, $token, $domain)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ])->get("https://{$domain}/admin/api/2023-04/products.json?sku={$sku}");

        if ($response->successful()) {
            $products = $response->json()['products'] ?? [];
            if (!empty($products)) {
                return $products[0]['id'];
            }
        }

        return null;
    }
}
