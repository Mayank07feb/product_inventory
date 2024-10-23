<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    protected $jsonFilePath = 'products.json';
    protected $xmlFilePath = 'products.xml'; // Add XML file path

    // Display all products
    public function index()
    {
        $products = $this->getAllProducts();

        // Filter out products without 'created_at' key
        $products = array_filter($products, function ($product) {
            return isset($product['created_at']);
        });

        // Sort products by datetime submitted in descending order
        usort($products, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return view('product_inventory.index', compact('products'));
    }

    // Store a new product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        $product = $validated;
        $product['created_at'] = Carbon::now()->toDateTimeString();
        $product['total_value'] = $product['quantity'] * $product['price'];

        $products = $this->getAllProducts();
        $products[] = $product;

        $this->saveProductsToJson($products);
        $this->saveProductsToXml($products); // Save to XML as well

        return response()->json($products);
    }

    // Delete a product
    public function destroy($index)
    {
        $products = $this->getAllProducts();

        if (isset($products[$index])) {
            unset($products[$index]);
            $this->saveProductsToJson(array_values($products)); // Reindex array
            $this->saveProductsToXml(array_values($products)); // Update XML as well
        }

        return response()->json($products);
    }

    // Show the edit form for a specific product
    public function edit($index)
    {
        $products = $this->getAllProducts();

        if (isset($products[$index])) {
            return response()->json($products[$index]);
        }

        return response()->json(['error' => 'Product not found'], 404);
    }

    // Update a specific product
    public function update(Request $request, $index)
    {
        $products = $this->getAllProducts();

        if (isset($products[$index])) {
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:0',
                'price' => 'required|numeric|min:0',
            ]);

            $products[$index] = array_merge($products[$index], $validated);
            $products[$index]['total_value'] = $products[$index]['quantity'] * $products[$index]['price'];

            $this->saveProductsToJson($products);
            $this->saveProductsToXml($products); // Update XML as well

            return response()->json($products);
        }

        return response()->json(['error' => 'Product not found'], 404);
    }

    // Retrieve all products from the JSON file
    private function getAllProducts()
    {
        if (Storage::exists($this->jsonFilePath)) {
            $json = Storage::get($this->jsonFilePath);
            return json_decode($json, true) ?: [];
        }

        return [];
    }

    // Save the products back to the JSON file
    private function saveProductsToJson($products)
    {
        Storage::put($this->jsonFilePath, json_encode($products, JSON_PRETTY_PRINT));
    }

    // Save the products back to the XML file
    private function saveProductsToXml($products)
    {
        $xml = new \SimpleXMLElement('<products/>');

        foreach ($products as $product) {
            $productXml = $xml->addChild('product');
            foreach ($product as $key => $value) {
                $productXml->addChild($key, htmlspecialchars($value));
            }
        }

        Storage::put($this->xmlFilePath, $xml->asXML());
    }

    // Show JSON data for all products
    public function showJson()
    {
        $products = $this->getAllProducts();
        return response()->json($products);
    }

    // Show XML data for all products
    public function showXml()
    {
        if (Storage::exists($this->xmlFilePath)) {
            return response()->file(Storage::path($this->xmlFilePath), [
                'Content-Type' => 'application/xml',
            ]);
        }

        return response()->json(['error' => 'XML file not found'], 404);
    }
}
