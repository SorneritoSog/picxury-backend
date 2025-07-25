<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PhotoSession;
use App\Models\Client;
use App\Models\PhotographerService;
use App\Models\PhotoSessionPhotographerService;
use App\Models\FinancialMovement;
use App\Models\Notification;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PhotoSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $photoSessions = PhotoSession::with([
            'client', 
            'type', 
            // Carga la relación anidada 'albums.albumPhotos' y limita las fotos a las primeras 3 por cada álbum
            'albums.albumPhotos' => function ($query) {
                $query->orderBy('id', 'asc')->take(3);
            },
            // Carga el conteo total de fotos por álbum
            'albums' => function ($query) {
                $query->withCount('albumPhotos');
            },
            'photoSessionPhotographerServices.photographerService.service'
        ])
            ->where('photographer_id', $id)
            ->whereNotIn('status', ['Solicitada', 'Cancelada', 'Rechazada'])
            ->orderBy('date', 'desc')
            ->get();

        
        // Formatear la fecha y el precio de cada sesión fotográfica a un formato legible.
        $photoSessions->each(function ($session) {
            $session->date = Carbon::parse($session->date)->format('M d, Y');
            $session->start_time = Carbon::createFromFormat('H:i:s', $session->start_time)->format('h:i A');
            $session->end_time = Carbon::createFromFormat('H:i:s', $session->end_time)->format('h:i A');
            $session->total_price = '$' . number_format($session->total_price, 0, ',', '.');
            
            // Formatear los precios de los servicios asociados a la sesión
            foreach ($session->photoSessionPhotographerServices as $service) {
                // Calcular el subtotal antes de formatear el precio unitario
                $subtotal = $service->quantity * $service->unit_price;
                $service->unit_price = '$' . number_format($service->unit_price, 0, ',', '.');
                $service->subtotal = '$' . number_format($subtotal, 0, ',', '.');
                // Agregar el nombre del servicio directamente al objeto
                $service->name = $service->photographer->service->name ?? null;
                // Ocultar las relaciones anidadas para limpiar la respuesta
                unset($service->photographer);
            }
            
            // Formatear las URLs de las fotos en los álbumes
            foreach ($session->albums as $album) {
                // Agregar el conteo total de fotos del álbum
                $album->total_photos = $album->album_photos_count;
                
                foreach ($album->albumPhotos as $photo) {
                    $photo->url = asset($photo->url);
                }
            }
        });

        // Calcular estadísticas desde la colección obtenida
        $realizadas = $photoSessions->whereIn('status', ['Subiendo', 'Selección', 'Espera', 'Completada'])->count();
        $porRealizar = $photoSessions->where('status', 'Por realizar')->count();
        $anuladas = $photoSessions->where('status', 'Anulada')->count();
        $total = $realizadas + $porRealizar + $anuladas;

        return response()->json([
            'success' => true,
            'data' => [
                'photoSessions' => $photoSessions,
                'stats' => [
                    'photoSessions_total' => $total,
                    'photoSessions_made' => $realizadas,
                    'photoSessions_to_do' => $porRealizar,
                    'photoSessions_annulled' => $anuladas,
                ]
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photographer_id' => 'required|integer|exists:photographers,id',
            'photo_session_type_id' => 'required|integer|exists:photo_session_types,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'order' => 'required|array',
            'order.*.photographer_service_id' => 'required|integer|exists:photographer_services,id',
            'order.*.quantity' => 'required|integer|min:1',
            'source' => 'required|string',
            'client_email' => 'required|email',
            'client_name' => 'required|string|max:255',
            'client_last_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 1. Calcular el total y preparar los servicios
        $totalAmount = 0;
        $servicesToCreate = [];
        foreach ($request->order as $orderItem) {
            $photographerService = PhotographerService::find($orderItem['photographer_service_id']);
            if ($photographerService && $orderItem['quantity'] > 0) {
                $totalAmount += $photographerService->price * $orderItem['quantity'];
                $servicesToCreate[] = [
                    'photographerService' => $photographerService,
                    'quantity' => $orderItem['quantity']
                ];
            }
        }

        $status = $request->source === 'client' ? 'Solicitada' : 'Por realizar';

        $client = Client::firstOrCreate(
            ['email' => $request->client_email],
            [
                'name' => $request->client_name,
                'last_name' => $request->client_last_name,
                'phone_number' => $request->client_phone
            ]
        );

        // 2. Crear la PhotoSession incluyendo el total y un título provisional
        $photoSession = PhotoSession::create([
            'photographer_id' => $request->photographer_id,
            'client_id' => $client->id,
            'status' => $status,
            'title' => $request->title ?? 'Sesión N/A', // Título provisional si no se provee
            'photo_session_type_id' => $request->photo_session_type_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'department' => $request->department,
            'city' => $request->city,
            'address' => $request->address,
            'place_description' => $request->place_description ?? null,
            'payment_status' => $request->payment_status ?? 'Pendiente',
            'total_price' => $totalAmount, // Corregido: usar total_price y calcular antes
        ]);

        // 3. Si no se proveyó un título, actualizarlo ahora que tenemos ID
        if (!$request->has('title')) {
            $photoSession->title = 'Sesión N.' . $photoSession->id;
            $photoSession->save();
        }

        // 4. Crear los registros de servicios asociados
        foreach ($servicesToCreate as $item) {
            PhotoSessionPhotographerService::create([
                'photo_session_id' => $photoSession->id,
                'photographer_service_id' => $item['photographerService']->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['photographerService']->price,
            ]);
        }

        // 5. Registrar el movimiento financiero si está pagado
        if($photoSession->payment_status === 'Pagada') {
            FinancialMovement::create([
                'photo_session_id' => $photoSession->id,
                'amount' => $photoSession->total_price, // Usar el total guardado
                'type' => 'Ingreso',
                'description' => 'Pago por sesión fotográfica #' . $photoSession->id,
            ]);
        }

        // 6. Si la ssesión fue desde la vista del cliente, crea la notificación
        if ($request->source === 'client') {
            Notification::create([
                'photographer_id' => $request->photographer_id,
                'photo_session_id' => $photoSession->id,
                'type' => 'Solicitud',
                'title' => 'Solicitud de sesión fotográfica',
                'is_read' => false
            ]);
        }        

        Album::create([
            'photo_session_id' => $photoSession->id,
            'email' => $client->email,
            'code' => str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión de fotos creada exitosamente.',
            'data' => $photoSession,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */

    // NO SE UTILIZA, AUNQUE SE PODRÍA UTILIZAR PARA VER UNA SESIÓN DE FOTOS EN PARTICULAR!!
    // En el frontend se está utilizando otro metodo para obtener los detalles de una sesión de fotos (Sin consumir ninguna ruta).
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Actualiza la información general de la sesión de fotos.
     * Nombre, Estado de pago, Estado de la sesión y el tipo de sesión.
     */
    public function updateGeneralInfo(Request $request, $photographerId, $photoSessionId)
    {
        // Validar que el fotógrafo sea el dueño de la sesión
        $photoSession = PhotoSession::where('id', $photoSessionId)
            ->where('photographer_id', $photographerId)
            ->first();

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la sesión de fotos o no pertenece al fotógrafo.'
            ], Response::HTTP_NOT_FOUND);
        }
    
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'payment_status' => 'required|string|in:Pendiente,Pagada',
            'status' => 'required|string|in:Por realizar,Subiendo,Selección,Espera,Completada',
            'photo_session_type_id' => 'required|integer|exists:photo_session_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Actualizar la sesión de fotos con los datos validados
        $photoSession->update($request->only('title', 'payment_status', 'status', 'photo_session_type_id'));

        if($request->payment_status === 'Pagada') {
            // Verificar si ya existe un movimiento financiero para esta sesión
            $existingMovement = FinancialMovement::where('photo_session_id', $photoSessionId)
                ->where('type', 'ingreso')
                ->first();

            // Solo crear el movimiento financiero si no existe uno previamente
            if (!$existingMovement) {
                FinancialMovement::create([
                    'photographer_id' => $photographerId,
                    'amount' => $photoSession->total_price,
                    'type' => 'ingreso',
                    'category' => 'pago-sesion-fotografica',
                    'detail' => 'Pago por sesión fotográfica #' . $photoSessionId,
                    'photo_session_id' => $photoSessionId,
                ]);
            }
        } else {
            // Si el estado de pago cambia a pendiente, eliminar el movimiento financiero si existe
            FinancialMovement::where('photo_session_id', $photoSessionId)
                ->where('type', 'ingreso')
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Información general de la sesión actualizada exitosamente.',
            'data' => $photoSession
        ], Response::HTTP_OK);
    }

    /** 
     * Actualiza la fecha, hora de inicio, hora de finalización y el lugar de la sesión.
     * 
    */
    public function updateDateAndLocation(Request $request, $photographerId, $photoSessionId)
    {
        // Validar que el fotógrafo sea el dueño de la sesión
        $photoSession = PhotoSession::where('id', $photoSessionId)
            ->where('photographer_id', $photographerId)
            ->first();

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la sesión de fotos o no pertenece al fotógrafo.'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'place_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Actualizar la sesión de fotos con los datos validados
        $photoSession->update($request->only('date', 'start_time', 'end_time', 'department', 'city', 'address', 'place_description'));

        return response()->json([
            'success' => true,
            'message' => 'Fecha y ubicación de la sesión actualizadas exitosamente.',
            'data' => $photoSession
        ], Response::HTTP_OK);
    }

    /**
     * Confirmar selección de fotos en el álbum. (Cambia el estado de la sesión de Seleeción a Espera).
     */
    public function confirmPhotoSelection($photoSessionId)
    {
        // Buscar la sesión de fotos por ID
        $photoSession = PhotoSession::find($photoSessionId);

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión de fotos no encontrada.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Cambiar el estado de la sesión de fotos a "Espera"
        $photoSession->status = 'Espera';
        $photoSession->save();

        Notification::create([
            'photographer_id' => $photoSession->photographer_id,
            'photo_session_id' => $photoSession->id,
            'type' => 'Selección',
            'title' => 'Selección de fotos confirmada',
            'is_read' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Selección de fotos confirmada.',
            'data' => $photoSession
        ], Response::HTTP_OK);
    }

    /**
     * Actualiza el estado de la sesión de fotos a "Anulada".
     */
    public function cancelPhotoSession($photoSessionId)
    {   
        // Buscar la sesión de fotos por ID
        $photoSession = PhotoSession::find($photoSessionId);

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión de fotos no encontrada.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Cambiar el estado de la sesión de fotos a "Anulada"
        $photoSession->status = 'Anulada';
        $photoSession->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesión de fotos cancelada exitosamente.',
            'data' => $photoSession
        ], Response::HTTP_OK);
    }

    /**
     * Restaura una sesión de fotos anulada, cambiando su estado a "Por realizar".
     */
    public function restorePhotoSession($photoSessionId)
    {
        // Buscar la sesión de fotos por ID
        $photoSession = PhotoSession::find($photoSessionId);

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión de fotos no encontrada.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Cambiar el estado de la sesión de fotos a "Por realizar"
        $photoSession->status = 'Por realizar';
        $photoSession->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesión de fotos restaurada exitosamente.',
            'data' => $photoSession
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
