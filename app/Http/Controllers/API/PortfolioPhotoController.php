<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PortfolioPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PortfolioPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $photographerId, $categoryId)
    {
        // Validación de la foto
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048' // Máximo 2MB por foto
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $photo = $request->file('photo');

        // Generar nombre único para la foto
        $fileName = time() . '_' . $photo->getClientOriginalName();

        // Definir la ruta donde se guardará la foto
        $destinationPath = 'images/categories/' . $categoryId;

        // Mover la foto al directorio público
        $photo->move(public_path($destinationPath), $fileName);

        // Guardar la información de la foto en la base de datos
        $portfolioPhoto = PortfolioPhoto::create([
            'url' => $destinationPath . '/' . $fileName,
            'portfolio_category_id' => $categoryId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto agregada exitosamente',
            'data' => $portfolioPhoto,
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $photographerId, $categoryId, $photoId)
    {
        // Buscar la foto en la base de datos
        $portfolioPhoto = PortfolioPhoto::find($photoId);

        if (!$portfolioPhoto) {
            return response()->json([
                'success' => false,
                'message' => 'Foto no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        // Eliminar el archivo físico del servidor
        $filePath = public_path($portfolioPhoto->url);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Eliminar el registro de la base de datos
        $portfolioPhoto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminada exitosamente',
        ], Response::HTTP_OK);
    }
}
