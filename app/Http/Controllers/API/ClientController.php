<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        // Obtener los clientes del fotógrafo que tengan al menos una sesión con estados específicos
        $clients = Client::whereHas('photoSessions', function ($query) use ($id) {
                $query->where('photographer_id', $id)
                      ->whereIn('status', ['Por realizar', 'Subiendo', 'Selección', 'Espera', 'Completada']);
            })
            ->withCount([
                'photoSessions' => function ($query) use ($id) {
                    $query->where('photographer_id', $id);
                }
            ])
            ->withCount([
                'photoSessions as purchased_sessions_count' => function ($query) use ($id) {
                    $query->where('photographer_id', $id)
                          ->whereNotIn('status', ['Solicitada', 'Rechazada', 'Anulada']);
                }
            ])
            ->withSum([
                'photoSessions as total_paid' => function ($query) use ($id) {
                    $query->where('photographer_id', $id)
                          ->whereNotIn('status', ['Solicitada', 'Rechazada', 'Anulada'])
                          ->where('payment_status', 'Pagada');
                }
            ], 'total_price')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($client) {
                // Formatear total_paid como peso colombiano
                $client->total_paid_formatted = $client->total_paid 
                    ? '$' . number_format($client->total_paid, 0, ',', '.') 
                    : '$0';
                return $client;
            });

        return response()->json([
            'success' => true,
            'data' => $clients,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $photographerId, $clientId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email,' . $clientId,
            'phone_number' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = Client::findOrFail($clientId);
        
        // Verificar si el email cambió
        $emailChanged = $client->email !== $request->email;
        
        $client->update($request->all());

        // Si el email cambió, actualizar todos los álbumes de las sesiones del cliente
        if ($emailChanged) {
            $client->photoSessions()
                ->with('albums')
                ->get()
                ->pluck('albums')
                ->flatten()
                ->each(function ($album) use ($request) {
                    $album->update(['email' => $request->email]);
                });
        }

        return response()->json([
            'success' => true,
            'message' => 'Información del cliente actualizada correctamente.',
            'data' => $client,
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
