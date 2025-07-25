<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Photographer;
use App\Models\Service;
use App\Models\PhotographerService;
use App\Models\PortfolioCategory;
use App\Models\PhotographerSocialNetwork;
use App\Models\PhotoSessionType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PhotographerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $photographers = Photographer::all();

        // Formatear la URL de la foto de perfil para cada fotógrafo
        $photographers->each(function ($photographer) {
            if ($photographer->profile_picture) {
                $photographer->profile_picture = asset($photographer->profile_picture);
            }
        });

        return response()->json([
            'success' => true,
            'data' => $photographers
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'last_name' => 'string|max:100',
            'department' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'phone_number' => 'required|numeric',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico ya está en uso',
            ], Response::HTTP_CONFLICT);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $request->phone_number = (string)$request->phone_number;

        // 2. Crear fotógrafo relacionado
        $photographer = Photographer::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'last_name' => $request->last_name, // Corregido: $request->last_name
            'phone_number' => $request->phone_number,
            'department' => $request->department,
            'city' => $request->city,
            'personal_description' => "Ninguna",
            'start_time_of_attention' => "08:00:00",
            'end_time_of_attention' => "20:00:00",
            'price_per_hour' => 50000, // Precio por defecto
            'profile_picture' => "images/profile_pictures/blessd2.jpg"
        ]);

        // Crear registros en photographer_services para cada servicio con precio por defecto
        $services = Service::all();
        foreach ($services as $service) {
            $data = [
                'photographer_id' => $photographer->id,
                'service_id' => $service->id,
            ];
            if ($service->id === 1) {
                $data['price'] = 0;
            } elseif ($service->id === 2) {
                $data['price'] = 10000;
            } elseif ($service->id === 3) {
                $data['price'] = 15000;
            } elseif ($service->id === 4) {
                $data['price'] = 25000;
            } else {
                $data['price'] = 10000; // Precio por defecto para otros servicios
            }
            PhotographerService::create($data);
        }

        // Crear categoría de portafolio por defecto
        PortfolioCategory::create([
            'photographer_id' => $photographer->id,
            'name' => 'Lo mejor de'
        ]);

        PhotographerSocialNetwork::create([
            'photographer_id' => $photographer->id,
            'name' => 'Whatsapp',
            'url' => 'https://wa.me/57' . $photographer->phone_number
        ]);

        PhotographerSocialNetwork::create([
            'photographer_id' => $photographer->id,
            'name' => 'Instagram',
            'url' => null
        ]);

        PhotographerSocialNetwork::create([
            'photographer_id' => $photographer->id,
            'name' => 'Facebook',
            'url' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fotografo creado con éxito',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $photographer = Photographer::with(['user' => function ($query) {
            $query->select('id', 'email');
        }])
            ->findOrFail($id)
        ;

        if (!$photographer) {
            return response()->json([
                'success' => false,
                'message' => 'Fotografo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $completedSessionsCount = $photographer->photoSessions()
            ->where('status', 'Completada')
            ->count()
        ;

        $socialNetworks = $photographer->socialNetworks;

        $photographerServices = PhotographerService::where('photographer_id', $id)
            ->with('service')
            ->get()
        ;

        // Formatear la URL de la foto de perfil
        if ($photographer->profile_picture) {
            $photographer->profile_picture = asset($photographer->profile_picture);
        }

        return response()->json([
            'success' => true,
            'photographer' => $photographer,
            'completed_sessions_count' => $completedSessionsCount,
            'social_networks' => $socialNetworks,
            'photographer_services' => $photographerServices,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatePersonalInfo(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $photographer->user->id . '|max:255',
            'name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'phone_number' => 'required|string',
            'personal_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $photographer->user;

        if ($user) {
            $user->update([
                'email' => $request->email,
            ]);
        }

        $photographer->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'department' => $request->department,
            'city' => $request->city,
            'phone_number' => $request->phone_number,
            'personal_description' => $request->personal_description ?? $photographer->personal_description
        ]);

        // Buscar las redes sociales del fotógrafo
        $socialNetworks = PhotographerSocialNetwork::where('photographer_id', $id)->get();

        foreach ($socialNetworks as $socialNetwork) {
            if ($socialNetwork->name === 'Whatsapp') {
                $socialNetwork->update(['url' => 'https://wa.me/57' . $request->phone_number]);
            }
        }

        $photographer->load('user');

        $responseData = $photographer->toArray();
        $responseData['email'] = $photographer->user->email;
        unset($responseData['user']);

        return response()->json([
            'success' => true,
            'message' => 'Información personal actualizada con éxito',
            'data' => $responseData
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Dashboard del fotógrafo autenticado
     */
    public function dashboard($id)
    {
        // Eliminar validación de autenticación
        $user = Photographer::findOrFail($id);

        $thirtyOneDaysAgo = Carbon::now()->subDays(31);
        $recentSessions = $user->photoSessions()
            ->with(['albums.albumPhotos', 'client']) // Agregar la relación del cliente
            ->where('date', '>=', $thirtyOneDaysAgo)
            ->whereNotIn('status', ['Solicitada', 'Cancelada', 'Por realizar', 'Anulada', 'Rechazada'])
            ->get();

        // Transforma cada sesión para adjuntar solo la URL de la primera foto del primer álbum
        $recentSessions->each(function ($session) {
            $firstAlbum = $session->albums->first(); // Obtiene el primer álbum de la sesión

            if ($firstAlbum && $firstAlbum->albumPhotos->isNotEmpty()) {
                // Asigna la URL de la primera foto a un nuevo atributo.
                $session->first_photo_url = asset($firstAlbum->albumPhotos->first()->url);
            } else {
                // Si no hay álbum o fotos, el atributo será nulo.
                $session->first_photo_url = null;
            }

            // Formatear el total_price en peso colombiano
            $session->total_price = '$' . number_format($session->total_price, 0, ',', '.');

            // Formatear el date con el formato solicitado en español
            $session->date = Carbon::parse($session->date)->locale('es')->translatedFormat('j \d\e F \- Y');

            // Eliminamos la relación completa para no enviarla en el JSON y mantener la respuesta ligera.
            unset($session->albums);
        });

        $pendingSessions = $user->photoSessions()
            ->with('client') // Agregar la relación del cliente
            ->where('status', 'Por realizar')
            ->get();

        // Formatear pending_sessions
        $pendingSessions->each(function ($session) {
            // Formatear el total_price en peso colombiano
            $session->total_price = '$' . number_format($session->total_price, 0, ',', '.');

            // Formatear el date con el formato solicitado en español
            $session->date = Carbon::parse($session->date)->locale('es')->translatedFormat('j \d\e F \- Y');
        });

        // Balance general
        $incomes = $user->financialMovements()->where('type', 'income')->sum('amount');
        $expenses = $user->financialMovements()->where('type', 'expense')->sum('amount');
        $balance = $incomes - $expenses;

        // Ganancias y gastos de los últimos 31 días
        $incomesLastMonth = $user->financialMovements()
            ->where('type', 'income')
            ->where('created_at', '>=', $thirtyOneDaysAgo)
            ->sum('amount');
        $expensesLastMonth = $user->financialMovements()
            ->where('type', 'expense')
            ->where('created_at', '>=', $thirtyOneDaysAgo)
            ->sum('amount');

        // Cantidad de sesiones realizadas en los últimos 31 días
        $sessionsLastMonth = $user->photoSessions()
            ->where('date', '>=', $thirtyOneDaysAgo)
            ->whereNotIn('status', ['Solicitada', 'Cancelada', 'Por realizar', 'Anulada', 'Rechazada'])
            ->count();

        // Tipo de sesión más solicitado por el fotógrafo (registro completo, de todos los tiempos)
        $topPhotoSessionTypeId = $user->photoSessions()
            ->select('photo_session_type_id')
            ->whereNotIn('status', ['Solicitada', 'Cancelada', 'Por realizar', 'Anulada', 'Rechazada'])
            ->groupBy('photo_session_type_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->pluck('photo_session_type_id')
            ->first();
        $topPhotoSessionType = null;
        if ($topPhotoSessionTypeId) {
            $topPhotoSessionType = PhotoSessionType::find($topPhotoSessionTypeId);
        }

        // Cantidad de sesiones con estado 'Por realizar' para el fotógrafo
        $pendingSessionsCount = $user->photoSessions()
            ->where('status', 'Por realizar')
            ->count();

        // Formatear la URL de la foto de perfil del fotógrafo
        $photographerProfilePicture = null;
        if ($user->profile_picture) {
            $photographerProfilePicture = asset($user->profile_picture);
        }

        // Verificar si hay notificaciones sin leer
        $hasUnreadNotifications = $user->photoSessions()
            ->whereHas('notifications', function ($query) {
                $query->where('is_read', false);
            })
            ->exists();

        return response()->json([
            'photographer_profile_picture' => $photographerProfilePicture,
            'recent_sessions' => $recentSessions,
            'sessions_last_month' => $sessionsLastMonth,
            'pending_sessions' => $pendingSessions,
            'pending_sessions_count' => $pendingSessionsCount,
            'top_session_type' => $topPhotoSessionType,
            'balance' => $balance,
            'incomes_last_month' => $incomesLastMonth,
            'expenses_last_month' => $expensesLastMonth,
            'has_unread_notifications' => $hasUnreadNotifications,
        ]);
    }

    /**
     * Cambiar contraseña del fotógrafo
     */
    public function changePassword(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $photographer->user;

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada con éxito',
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar foto de perfil del fotógrafo
    */
    public function updateProfilePicture(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($request->hasFile('profile_picture')) {
            // Eliminar la foto anterior si existe
            if ($photographer->profile_picture && $photographer->profile_picture !== 'images/profile_pictures/blessd2.jpg') {
                $oldImagePath = public_path($photographer->profile_picture);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $file = $request->file('profile_picture');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = 'images/profile_pictures/' . $filename;
            $file->move(public_path('images/profile_pictures'), $filename);

            // Actualizar la foto de perfil del fotógrafo
            $photographer->update(['profile_picture' => $filePath]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil actualizada con éxito',
                'data' => ['profile_picture' => asset($filePath)]
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se ha proporcionado una foto de perfil válida',
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Actualizar URL de la red social del fotógrafo
     */
    public function updateSocialNetworkUrls(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'instagram_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $socialNetworks = PhotographerSocialNetwork::where('photographer_id', $id)
            ->whereIn('name', ['Instagram', 'Facebook'])
            ->get();

        foreach ($socialNetworks as $socialNetwork) {
            if ($socialNetwork->name === 'Instagram' && $request->instagram_url) {
                $socialNetwork->update(['url' => $request->instagram_url]);
            }

            if ($socialNetwork->name === 'Facebook' && $request->facebook_url) {
                $socialNetwork->update(['url' => $request->facebook_url]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'URLs de redes sociales actualizadas con éxito',
            'data' => $socialNetworks
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar precios del fotógrafo y servicios
     */
    public function updatePrices(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price_per_hour' => 'required|numeric|min:0',
            'services' => 'required|array',
            'services.*.id' => 'required|exists:photographer_services,id',
            'services.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $photographer->update([
            'price_per_hour' => $request->price_per_hour,
        ]);

        foreach ($request->services as $serviceData) {
            $service = PhotographerService::where('id', $serviceData['id'])
                ->whereHas('service', function ($query) {
                    $query->whereIn('type', ['Foto', 'Edición']);
                })
                ->first();

            if ($service) {
                $service->update(['price' => $serviceData['price']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Precios actualizados con éxito',
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar horarios de atención del fotógrafo
     */
    public function updateAttentionTimes(Request $request, $id)
    {
        $photographer = Photographer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'start_time_of_attention' => 'required|date_format:H:i',
            'end_time_of_attention' => 'required|date_format:H:i|after:start_time_of_attention',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $photographer->update([
            'start_time_of_attention' => $request->start_time_of_attention,
            'end_time_of_attention' => $request->end_time_of_attention,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Horarios de atención actualizados con éxito',
            'data' => $photographer
        ], Response::HTTP_OK);
    }
}
