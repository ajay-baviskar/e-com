<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric',
                'images' => 'required|array',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
            ]);

            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'product' => $product,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $products = Product::with('images')->get();

            return response()->json([
                'status' => 'success',
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with('images')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'product' => $product,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if (isset($validated['name'])) {
                $product->name = $validated['name'];
            }

            if (isset($validated['price'])) {
                $product->price = $validated['price'];
            }

            $product->save();


            if ($request->hasFile('images')) {

                foreach ($product->images as $image) {
                    if (Storage::exists('public/' . $image->image_path)) {
                        Storage::delete('public/' . $image->image_path);
                    }
                    $image->delete();
                }

                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'product' => $product->load('images'), // Include updated images in response
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            foreach ($product->images as $image) {
                if (Storage::exists('public/' . $image->image_path)) {
                    Storage::delete('public/' . $image->image_path);
                }
                $image->delete();
            }

            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
