<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private function validateProduct(Request $request, $id = null)
    {
        $rules = [
            'barcode' => 'required|unique:products' . ($id ? ',barcode,' . $id : ''),
            'description' => 'required',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'category' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ];

        return Validator::make($request->all(), $rules);
    }

    public function index()
    {
        $products = Product::all();
        return response()->json([
            'data' => $products->map(fn($product) => array_merge(
                $product->toArray(),
                ['image_url' => $product->image_url]
            )),
            'success' => true
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validateProduct($request);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('image');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);
        return response()->json(array_merge(
            $product->toArray(),
            ['image_url' => $product->image_url]
        ), 201);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json(array_merge(
            $product->toArray(),
            ['image_url' => $product->image_url]
        ));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validator = $this->validateProduct($request, $id);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('image');
        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return response()->json(array_merge(
            $product->toArray(),
            ['image_url' => $product->image_url]
        ), 200);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $products = Product::where('description', 'like', "%{$query}%")
                          ->orWhere('category', 'like', "%{$query}%")
                          ->get();

        return response()->json([
            'data' => $products->map(fn($product) => array_merge(
                $product->toArray(),
                ['image_url' => $product->image_url]
            )),
            'success' => true
        ]);
    }
}
