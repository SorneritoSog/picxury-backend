<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Models\PhotographerService;
use App\Models\PhotoSessionPhotographerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AlbumController extends Controller
{
    /**
     * Ingresar al album desde la vista del cliente.
     */
    public function loginAlbum(Request $request)
    {
        // Lógica para ingresar al álbum
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|numeric|digits:6',
        ], [
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'code.required' => 'El campo código es obligatorio.',
            'code.numeric' => 'El código debe ser un número.',
            'code.digits' => 'El código debe tener exactamente 6 dígitos.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            $message = 'Error de validación';
            if ($errors->has('email')) {
                $message = $errors->first('email');
            } elseif ($errors->has('code')) {
                $message = $errors->first('code');
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Buscar el álbum por email y código
        $album = Album::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o código incorrectos.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Álbum encontrado.',
            'albumId' => $album->id,
        ], Response::HTTP_OK);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Subir una foto a un álbum específico para un fotógrafo.
     */
    public function uploadPhotos(Request $request, $photographerId, $albumId) 
    {
        // Validación de la foto
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Máximo 2MB por foto
            'edition_type' => 'nullable|string' // Tipo de edición opcional
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar que el álbum existe y pertenece al fotógrafo
        $album = Album::whereHas('photoSession', function ($query) use ($photographerId) {
            $query->where('photographer_id', $photographerId);
        })->find($albumId);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Álbum no encontrado o no pertenece al fotógrafo especificado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $photo = $request->file('photo');
        
        // Generar nombre único para la foto
        $fileName = time() . '_' . $photo->getClientOriginalName();
        
        // Definir la ruta donde se guardará la foto
        $destinationPath = 'images/albums/' . $albumId;
        
        // Mover la foto al directorio público
        $photo->move(public_path($destinationPath), $fileName);
        
        // Guardar la información en la base de datos
        $albumPhoto = AlbumPhoto::create([
            'album_id' => $albumId,
            'url' => $destinationPath . '/' . $fileName,
            'is_selected' => false,
            'edition_type' => $request->input('edition_type'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente.',
            'data' => [
                'photo' => [
                    'id' => $albumPhoto->id,
                    'url' => asset($albumPhoto->url),
                    'created_at' => Carbon::parse($albumPhoto->created_at)->format('M d, Y'),
                ]
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Muestra las fotos de un álbum específico para un fotógrafo.
     */
    public function show($photographerId, $albumId)
    {
        // Buscar el álbum específico que pertenece al fotógrafo
        $album = Album::whereHas('photoSession', function ($query) use ($photographerId) {
            $query->where('photographer_id', $photographerId);
        })->with(['albumPhotos', 'photoSession'])->find($albumId);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Álbum no encontrado o no pertenece al fotógrafo especificado.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Obtener la cantidad de fotos que el cliente compró
        $minPhotosRequired = 0;
        
        // 1. Buscar el servicio de fotos del fotógrafo (service_id = 5)
        $photographerPhotoService = PhotographerService::where('photographer_id', $photographerId)
            ->where('service_id', 5)
            ->first();

        if ($photographerPhotoService) {
            // 2. Buscar la cantidad comprada en la sesión de fotos
            $photoServicePurchased = PhotoSessionPhotographerService::where('photo_session_id', $album->photoSession->id)
                ->where('photographer_service_id', $photographerPhotoService->id)
                ->first();

            if ($photoServicePurchased) {
                $minPhotosRequired = $photoServicePurchased->quantity;
            }
        }

        // Formatear las URLs de las fotos
        $formattedPhotos = [];
        if($album->albumPhotos) {
            $formattedPhotos = $album->albumPhotos->map(function ($photo) {
                $photoArray = $photo->toArray();
                if ($photo->url) {
                    $photoArray['url'] = asset($photo->url);
                }
                $photoArray['created_at'] = Carbon::parse($photo->created_at)->format('M d, Y');
                return $photoArray;
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => $formattedPhotos,
                'min_photos_required' => $minPhotosRequired,
                'current_photos_count' => count($formattedPhotos),
                'photos_remaining' => max(0, $minPhotosRequired - count($formattedPhotos))
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Muestra las fotos de el album para ser seleccionadas. 
    */
    public function selectPhotos($albumId)
    {
        // Buscar el álbum específico con sus relaciones necesarias
        $album = Album::with([
            'albumPhotos',
            'photoSession.client',
            'photoSession.photoSessionPhotographerServices.photographerService.service'
        ])->find($albumId);

        if (!$album) {
            return response()->json([
                'success' => false,
                'message' => 'Álbum no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Formatear las URLs de las fotos
        $formattedPhotos = $album->albumPhotos->map(function ($photo) {
            $photoArray = $photo->toArray();
            if ($photo->url) {
                $photoArray['url'] = asset($photo->url);
            }
            return $photoArray;
        });

        // Calcular el total de fotos a seleccionar y separar los servicios
        $totalToSelect = 0;
        $breakdown = [];

        foreach ($album->photoSession->photoSessionPhotographerServices as $photoSessionService) {
            $serviceName = $photoSessionService->photographerService->service->name;

            if ($serviceName === 'Foto profesional') {
                $totalToSelect += $photoSessionService->quantity;
            } else {
                $breakdown[] = [
                    'service_name' => $serviceName,
                    'quantity' => $photoSessionService->quantity
                ];
            }
        }

        // Actualizar el desglose con las fotos que ya tienen un edition_type
        foreach ($album->albumPhotos as $photo) {
            if ($photo->edition_type) {
                foreach ($breakdown as &$service) {
                    if ($service['service_name'] === $photo->edition_type) {
                        $service['quantity'] -= 1; // Reducir la cantidad restante
                    }
                }
            }
        }

        // Calcular la cantidad de fotos seleccionadas
        $selectedPhotosCount = $album->albumPhotos->where('is_selected', true)->count();

        // Construir el nombre de la sesión
        $sessionName = 'Sesión de ' . $album->photoSession->client->name . ' ' . $album->photoSession->client->last_name;

        $client = $album->photoSession->client;

        return response()->json([
            'success' => true,
            'data' => [
                'session_details' => [
                    'id' => $album->photoSession->id,
                    'title' => $album->photoSession->title,
                    'date' => $album->photoSession->date,
                    'start_time' => Carbon::createFromFormat('H:i:s', $album->photoSession->start_time)->format('h:i A'),
                    'end_time' => Carbon::createFromFormat('H:i:s', $album->photoSession->end_time)->format('h:i A'),
                    'department' => $album->photoSession->department,
                    'city' => $album->photoSession->city,
                    'address' => $album->photoSession->address,
                    'place_description' => $album->photoSession->place_description,
                    'status' => $album->photoSession->status,
                    'total_price' => '$' . number_format($album->photoSession->total_price, 0, ',', '.'),
                    'photo_session_type' => $album->photoSession->type->name,
                    'payment_status' => $album->photoSession->payment_status
                ],
                'photographer' => [
                    'id' => $album->photoSession->photographer->id,
                    'name' => $album->photoSession->photographer->name . ' ' . $album->photoSession->photographer->last_name,
                    'profile_picture' => asset($album->photoSession->photographer->profile_picture)
                ],
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'phone' => $client->phone_number
                ],
                'services_purchased' => $album->photoSession->photoSessionPhotographerServices->map(function ($service) {
                    return [
                        'service_name' => $service->photographerService->service->name,
                        'quantity' => $service->quantity,
                        'unit_price' => '$' . number_format($service->unit_price, 0, ',', '.'),
                        'subtotal' => '$' . number_format($service->quantity * $service->unit_price, 0, ',', '.')
                    ];
                }),
                'photos' => $formattedPhotos,
                'selection_requirements' => [
                    'total_to_select' => $totalToSelect,
                    'selected_photos_count' => $selectedPhotosCount,
                    'breakdown' => $breakdown
                ]
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $photographerId, $albumId)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Buscar el álbum específico que pertenece al fotógrafo
        $album = Album::whereHas('photoSession', function ($query) use ($photographerId) {
            $query->where('photographer_id', $photographerId);
        })->findOrFail($albumId);
        
        // Buscar otros álbumes con el mismo email que tengan el mismo código
        $duplicateCodeExists = Album::where('email', $album->email)
            ->where('code', (string) $request->code)
            ->where('id', '!=', $albumId) // Excluir el álbum actual específico
            ->exists();

        if ($duplicateCodeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un álbum con este código para el mismo cliente.',
            ], Response::HTTP_CONFLICT);
        }

        // Actualizar el código del álbum específico
        $album->update(['code' => (string) $request->code]);

        return response()->json([
            'success' => true,
            'message' => 'Código del álbum actualizado correctamente.',
            'data' => $album,
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar una foto de un álbum específico.
     * (Seleccionar o deseleccionar una foto)
     */
    public function updatePhotoSelection(Request $request, $albumId, $photoId)
    {
        // Validar entrada
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:select,deselect',
            'edition_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Determinar acción
        $isSelected = $request->action === 'select';
        $editionType = $isSelected ? $request->edition_type : null;

        // Buscar la foto en el álbum
        $photo = AlbumPhoto::where('album_id', $albumId)
            ->where('id', $photoId)
            ->firstOrFail();

        // Actualizar el estado de selección de la foto
        $photo->update(['is_selected' => $isSelected, 'edition_type' => $editionType]);

        return response()->json([
            'success' => true,
            'message' => 'Foto actualizada correctamente.',
            'data' => [
                'photo_id' => $photo->id,
                'is_selected' => $photo->is_selected,
                'edition_type' => $photo->edition_type,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar una foto de un álbum en específico.
     */
    public function removePhoto(Request $request, $photographerId, $albumId)
    {
        $validator = Validator::make($request->all(), [
            'photo_id' => 'required|integer|exists:album_photos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Buscar el álbum específico que pertenece al fotógrafo
        $album = Album::whereHas('photoSession', function ($query) use ($photographerId) {
            $query->where('photographer_id', $photographerId);
        })->findOrFail($albumId);

        // Buscar la foto en el álbum
        $photo = $album->albumPhotos()->findOrFail($request->photo_id);

        // Eliminar el archivo físico del servidor
        $filePath = public_path($photo->url);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Eliminar el registro de la base de datos
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminada del álbum correctamente.',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
