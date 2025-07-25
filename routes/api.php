<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PhotographerController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PhotoSessionController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\AlbumController;
use App\Http\Controllers\API\PortfolioCategoryController;
use App\Http\Controllers\API\PortfolioPhotoController;
use App\Http\Controllers\API\FinancialMovementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Photographer;
use App\Models\PhotoSessionType;
use App\Models\PhotographerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactUsMailable;


// Ruta para el ligin del fotógrafo
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

//Ruta para la vista inicial de Picxury
Route::get('/', function () {
    $photographers = Photographer::with([
        'portfolioCategories.portfolioItems' => function ($query) {
            $query->select('id', 'url', 'portfolio_category_id');
        },
        'socialNetworks' => function ($query) {
            $query->select('id', 'url', 'photographer_id');
        }
    ])
    ->withCount(['photoSessions as completed_sessions_count' => function ($query) {
        $query->where('status', 'Completada');
    }])
    ->orderBy('completed_sessions_count', 'desc')
    ->take(5)
    ->get();

    // Formatear la URL de la foto de perfil y las fotos del portafolio
    $photographers->each(function ($photographer) {
        if ($photographer->profile_picture) {
            $photographer->profile_picture = asset($photographer->profile_picture);
        }

        $photographer->portfolioCategories->each(function ($category) {
            $category->portfolioItems->each(function ($photo) {
                $photo->url = asset($photo->url);
            });
        });
    });

    return response()->json([
        'success' => true,
        'data' => $photographers
    ], Response::HTTP_OK);
});

// Ruta para la vista para mostrar todos los fotógrafos
Route::get('/photographers', function () {
    $photographers = Photographer::with([
        'portfolioCategories.portfolioItems' => function ($query) {
            $query->select('id', 'url', 'portfolio_category_id');
        },
        'socialNetworks' => function ($query) {
            $query->select('id', 'url', 'photographer_id');
        }
    ])
    ->withCount(['photoSessions as completed_sessions_count' => function ($query) {
        $query->where('status', 'Completada');
    }])
    ->orderBy('completed_sessions_count', 'desc')
    ->get();

    // Formatear la URL de la foto de perfil y las fotos del portafolio
    $photographers->each(function ($photographer) {
        if ($photographer->profile_picture) {
            $photographer->profile_picture = asset($photographer->profile_picture);
        }

        $photographer->portfolioCategories->each(function ($category) {
            $category->portfolioItems->each(function ($photo) {
                $photo->url = asset($photo->url);
            });
        });
    });

    return response()->json([
        'success' => true,
        'data' => $photographers
    ], Response::HTTP_OK);
});

// Ruta para que el fotógrafo se registre
Route::post('register-photographer', [PhotographerController::class, 'store']);

// VISTA DEL FOTÓGRAFO
// Ruta para el dashboard del fotógrafo
Route::get('photographer/dashboard/{id}', [PhotographerController::class, 'dashboard']);

// -- RUTAS DE NOTIFICACIONES DEL FOTÓGRAFO --
// Ruta para obtener las notificaciones del fotógrafo
Route::get('photographer/{id}/notifications', [NotificationController::class, 'index']);
// Ruta para ver una notificación específica del fotógrafo
Route::get('photographer/notifications/{notificationId}', [NotificationController::class, 'show']);
// Ruta para marcar una sesión de fotos como aceptada o rechazada por el fotógrafo
Route::put('photographer/notifications/{notificationId}/decision', [NotificationController::class, 'update']);

// -- RUTAS DEL PERFIL DEL FOTÓGRAFO --
Route::get('photographer/profile/{id}', [PhotographerController::class, 'show']);
// Ruta para actualizar la foto de perfil del fotógrafo
Route::put('photographer/profile/{id}/update-profile-picture', [PhotographerController::class, 'updateProfilePicture']);
// Ruta para actualizar datos personales del fotógrafo
Route::put('photographer/profile/{id}/change-personal-information', [PhotographerController::class, 'updatePersonalInfo']);
// Ruta para actualizar la contraseña del fotógrafo
Route::post('photographer/{id}/change-password', [PhotographerController::class, 'changePassword']);
// Ruta para actualizar los links de las redes sociales del fotógrafo
Route::put('photographer/{id}/social-networks', [PhotographerController::class, 'updateSocialNetworkUrls']);
// Ruta para actualiza los precios de los servicios del fotógrafo
Route::put('photographer/{id}/prices', [PhotographerController::class, 'updatePrices']);
// Ruta para actualizar los horarios de atención del fotógrafo
Route::put('photographer/{id}/attention-times', [PhotographerController::class, 'updateAttentionTimes']);

// -- RUTAS PARA SESIONES DE FOTOS --
// Ruta para obtener las sesiones de fotos del fotógrafo
Route::get('photographer/{id}/photo-sessions', [PhotoSessionController::class, 'index']);
// Rutas para los pasos de crear una sesión de fotos
    // Ruta para obtener los tipos de sensiones de fotografía
    Route::get('/photo-session-types', function () {
    $photoSessionTypes = PhotoSessionType::all();

    return response()->json([
        'success' => true,
        'data' => $photoSessionTypes
    ], Response::HTTP_OK);
    });
    // Ruta para obtener los servicios del fotógrafo
    Route::get('photographer/{id}/services', function ($id) {
    $photographerServices = PhotographerService::where('photographer_id', $id)
    ->with('service')
    ->get();

    return response()->json([
        'success' => true,
        'data' => $photographerServices
    ], Response::HTTP_OK);
    });
// Ruta para crear una sesión de fotos
Route::post('/photo-sessions', [PhotoSessionController::class, 'store']);
// Ruta para actualizar la información general de una sesión de fotos (Nombre, Estado de pago, Estado de la sesión y el tipo de sesión).
Route::put('photographer/{photograogerId}/photo-sessions/{photoSessionId}/update-general-info', [PhotoSessionController::class, 'updateGeneralInfo']);
// Ruta para actualizar la fecha, hora y ubicación de una sesión de fotos.
Route::put('photographer/{photograogerId}/photo-sessions/{photoSessionId}/update-date-time-location', [PhotoSessionController::class, 'updateDateAndLocation']);
// Ruta para confirmar una selección de fotos para una sesión de fotos.
Route::put('photo-sessions/{photoSessionId}/confirm-photo-selection', [PhotoSessionController::class, 'confirmPhotoSelection']);
// Ruta para anular una sesión de fotos.
Route::put('photo-sessions/{photoSessionId}/cancel', [PhotoSessionController::class, 'cancelPhotoSession']);
// Ruta para restaurar una sesión de fotos anulada.
Route::put('photo-sessions/{photoSessionId}/restore', [PhotoSessionController::class, 'restorePhotoSession']);


// -- RUTAS PARA EL CLIENTE --
// Ruta para obtener los clientes del fotógrafo
Route::get('photographer/{id}/clients', [ClientController::class, 'index']);
// Ruta para actualizar la información del cliente
Route::put('photographer/{photograogerId}/clients/{clientId}/update', [ClientController::class, 'update']);

// -- RUTAS PARA LOS ÁLBUMES DE FOTOS --
// Ruta para actualizar el código de un álbum de fotos
Route::put('photographer/{photograogerId}/albums/{albumId}/update-code', [AlbumController::class, 'update']);

// -- RUTAS PARA LAS CATEGORIAS DEL PORTAFOLIO DEL FOTÓGRAFO --
// Ruta para obtener las categorías del portafolio del fotógrafo
Route::get('photographer/{photographerId}/portfolio-categories', [PortfolioCategoryController::class, 'index']);
// Ruta para crear una nueva categoría en el portafolio del fotógrafo
Route::post('photographer/{photographerId}/portfolio-categories', [PortfolioCategoryController::class, 'store']);
// Ruta para ver una categoría específica del portafolio del fotógrafo
Route::get('photographer/{photographerId}/portfolio-categories/{categoryId}', [PortfolioCategoryController::class, 'show']);
// Ruta para actualizar el nombre de una categoría del portafolio del fotógrafo
Route::put('photographer/{photographerId}/portfolio-categories/{categoryId}', [PortfolioCategoryController::class, 'update']);
// Ruta para eliminar una categoría del portafolio del fotógrafo
Route::delete('photographer/{photographerId}/portfolio-categories/{categoryId}', [PortfolioCategoryController::class, 'destroy']);

// -- RUTAS PARA LAS FOTOS DEL PORTAFOLIO DEL FOTÓGRAFO --
// Ruta para agregar una foto a una categoría del portafolio del fotógrafo
Route::post('photographer/{photographerId}/portfolio-categories/{categoryId}/photos', [PortfolioPhotoController::class, 'store']);
// Ruta para eliminar una foto del portafolio del fotógrafo
Route::delete('photographer/{photographerId}/portfolio-categories/{categoryId}/photos/{photoId}', [PortfolioPhotoController::class, 'destroy']);

// -- RUTAS PARA LOS MOVIMIENTOS FINANCIEROS DEL FOTÓGRAFO --
// Ruta para obtener los movimientos financieros del fotógrafo
Route::get('photographer/{id}/financial-movements', [FinancialMovementController::class, 'index']);
// Ruta para crear un nuevo movimiento financiero
Route::post('photographer/{photographerId}/financial-movements', [FinancialMovementController::class, 'store']);
// Ruta para eliminar un movimiento financiero
Route::delete('photographer/{photographerId}/financial-movements/{movementId}', [FinancialMovementController::class, 'destroy']);

// -- Rutas para las fotos de los álbumes del fotógrafo --
// Ruta mara obtener las fotos de un álbum específico del fotógrafo
Route::get('photographer/{photographerId}/album/{photoAlbumId}/photos', [AlbumController::class, 'show']);
// Ruta para subir una foto a un álbum específico del fotógrafo
Route::post('photographer/{photographerId}/album/{albumId}/photos', [AlbumController::class, 'uploadPhotos']);
// Ruta para eliminar una foto de un álbum específico del fotógrafo
Route::delete('photographer/{photographerId}/album/{albumId}/photos', [AlbumController::class, 'removePhoto']);
// Ruta para ingresar al álbum desde la vista del cliente
Route::post('album', [AlbumController::class, 'loginAlbum']);
// Ruta para obtener las fotos para seleccionar
Route::get('album/{albumId}/photos/select', [AlbumController::class, 'selectPhotos']);
// Ruta para seleccionar o deseleccionar una foto en el álbum
Route::put('album/{albumId}/photos/{photoId}/select', [AlbumController::class, 'updatePhotoSelection']);


// -- Rutas para el envío de correos electrónicos --
// Ruta para enviar un correo electrónico de contacto
Route::post('/contact-us', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Obtener los datos validados del validador
    $validatedData = $validator->validated();

    // Aquí se enviaría el correo utilizando ContactUsMailable
    Mail::to('spenalozavelez1@gmail.com')->send(new ContactUsMailable($validatedData));
    
    return response()->json([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
    ], Response::HTTP_OK);
});