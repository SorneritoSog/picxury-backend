<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\MarkNotificationsAsRead;
use App\Models\User;
use App\Models\Notification;
use App\Models\PhotoSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $notifications = Notification::with('photoSession')
            ->whereHas('photoSession', function ($query) use ($id) {
                $query->where('photographer_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadSelectionIds = $notifications
            ->where('type', 'Selección')
            ->where('is_read', 0)
            ->pluck('id');

        if ($unreadSelectionIds->isNotEmpty()) {
            MarkNotificationsAsRead::dispatch($unreadSelectionIds);
        }

        return response()->json([
            'success' => true,
            'data' => $notifications
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // No es necesario implementar este método aquí, ya que las notificaciones se generan automáticamente.
        // La notificación se crea automáticamente cuando el cliente manda una solicitud de sesión fotográfica (PhotoSessionController)
        // O cuando el cliente termina de seleccionar las fotos de la sesión fotográfica. (PhotoSessionController)
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $photoSession = PhotoSession::with([
            'client', 
            'type', 
            'photoSessionPhotographerServices.photographerService.service'
        ])->find($notification->photo_session_id);

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión de fotos no encontrada para esta notificación'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'session' => [
                'id' => $photoSession->id,
                'status' => $photoSession->status,
                'total_price' => $photoSession->total_price,
                'payment_status' => $photoSession->payment_status,
                'title' => $photoSession->title,
                'date' => $photoSession->date,
                'start_time' => $photoSession->start_time,
                'end_time' => $photoSession->end_time,
                'department' => $photoSession->department,
                'city' => $photoSession->city,
                'address' => $photoSession->address,
                'place_description' => $photoSession->place_description,
            ],
            'client' => [
                'id' => $photoSession->client->id,
                'name' => $photoSession->client->name . ' ' . $photoSession->client->last_name,
                'email' => $photoSession->client->email,
                'phone' => $photoSession->client->phone_number,
            ],
            'type' => [
                'id' => $photoSession->type->id,
                'name' => $photoSession->type->name,
                'description' => $photoSession->type->description,
            ],
            'services' => $photoSession->photoSessionPhotographerServices->map(function ($service) {
                return [
                    'id' => $service->id,
                    'quantity' => $service->quantity,
                    'unit_price' => $service->unit_price,
                    'price' => $service->quantity * $service->unit_price,
                    'service_name' => $service->photographerService->service->name ?? null,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $notificationId)
    {
         $validator = Validator::make($request->all(), [
            'decision' => 'required|string|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación. La decisión debe ser "accepted" o "rejected".',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $notification = Notification::find($notificationId);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Marcar la notificación como leída
        $notification->update([
            'is_read' => 1
        ]);

        $photoSession = PhotoSession::find($notification->photo_session_id);

        if (!$photoSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión de fotos no encontrada para esta notificación'
            ], Response::HTTP_NOT_FOUND);
        }

        $decision = $request->input('decision');
        $newStatus = $decision === 'accepted' ? 'Por realizar' : 'Rechazada';

        $photoSession->update([
            'photographer_decision' => $decision,
            'status' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'La sesión ha sido ' . ($decision === 'accepted' ? 'aceptada' : 'rechazada') . ' correctamente.'
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
