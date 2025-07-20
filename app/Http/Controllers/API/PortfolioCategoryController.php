<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PortfolioCategory;
use App\Models\Photographer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PortfolioCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($photographerId)
    {
        $categories = PortfolioCategory::where('photographer_id', $photographerId)
            ->with(['portfolioItems' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->withCount('portfolioItems')
            ->get();
        
        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Categorias no encontradas.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Formatear las URLs de las fotos de portafolio y la fecha de creación
        $formattedCategories = $categories->map(function ($category) {
            // Convertir a array para poder modificar los valores
            $categoryArray = $category->toArray();

            

            // Formatear la fecha de creación de la categoría
            $categoryArray['created_at'] = Carbon::parse($category->created_at)->format('M d, Y');
            
            // Formatear las URLs de las fotos
            if ($category->portfolioItems && $category->portfolioItems->count() > 0) {
                $categoryArray['portfolio_items'] = $category->portfolioItems->map(function ($item) {
                    $itemArray = $item->toArray();
                    if ($item->url) {
                        $itemArray['url'] = asset($item->url);
                    }
                    return $itemArray;
                });
            } else {
                $categoryArray['portfolioItems'] = [];
            }
            
            return $categoryArray;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedCategories,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($photographerId)
    {
        // Contar las categorías existentes para el fotógrafo
        $categoryCount = PortfolioCategory::where('photographer_id', $photographerId)->count();

        // Crear la categoría con un nombre dinámico
        $category = PortfolioCategory::create([
            'photographer_id' => $photographerId,
            'name' => 'Nueva categoría ' . ($categoryCount + 1)
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show($photographerId, $categoryId)
    {
        // Verificar si el fotógrafo existe
        $photographer = Photographer::find($photographerId);

        if (!$photographer) {
            return response()->json([
                'success' => false,
                'message' => 'Fotógrafo no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Obtener la categoría del portafolio con sus fotos
        $category = PortfolioCategory::with(['portfolioItems' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])
        ->withCount('portfolioItems')
        ->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Formatear la fecha de creación y las URLs de las fotos
        $category->created_at = Carbon::parse($category->created_at)->format('M d, Y');
        $category->portfolioItems->each(function ($item) {
            if ($item->url) {
                $item->url = asset($item->url);
            }
        });

        return response()->json([
            'success' => true,
            'data' => $category,
        ], Response::HTTP_OK);
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $photographerId, $categoryId)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Actualizar la categoría
        $category = PortfolioCategory::where('photographer_id', $photographerId)->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $category->update($request->only('name'));

        return response()->json([
            'success' => true,
            'data' => $category,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($photographerId, $categoryId)
    {
        // Verificar si la categoría existe
        $category = PortfolioCategory::where('photographer_id', $photographerId)->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Eliminar la categoría
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente.',
        ], Response::HTTP_OK);
    }
}
