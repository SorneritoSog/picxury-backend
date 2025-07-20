<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FinancialMovement;
use App\Models\PhotoSession;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FinancialMovementController extends Controller
{
    /**
     * Formatea el monto a pesos colombianos
     */
    private function formatCOP($amount)
    {
        return '$' . number_format($amount, 0, ',', '.') . ' COP';
    }

    /**
     * Mapea las categorías técnicas a versiones legibles
     */
    private function formatCategory($category)
    {
        $categories = [
            // Categorías de ingresos
            'sesion-fotografica' => 'Sesión fotográfica',
            'edicion-retoque' => 'Edición y retoque',
            'venta' => 'Venta',
            'servicio-video' => 'Servicio de video',
            'alquiler-equipo' => 'Alquiler de equipo',
            'licencia-uso' => 'Licencia de uso',
            'contenido-digital' => 'Contenido digital',
            'curso-taller' => 'Curso/Taller',
            'otros' => 'Otros',
            
            // Categorías de gastos
            'alquiler-espacio' => 'Alquiler de espacio',
            'asistente' => 'Asistente',
            'compra' => 'Compra',
            'publicidad-marketing' => 'Publicidad y marketing',
            'reparacion-equipo' => 'Reparación de equipo',
            'software' => 'Software',
            'transporte' => 'Transporte',
            'vestuario' => 'Vestuario',
        ];

        return $categories[$category] ?? ucwords(str_replace('-', ' ', $category));
    }

    /**
     * Muestra una lista de los recursos.
     */
    public function index($photographerId)
    {
        // Obtener todos los ingresos del fotógrafo
        $income = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'ingreso')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ingreso) {
                $ingreso->created_at_formatted = Carbon::parse($ingreso->created_at)->format('M d, Y');
                $ingreso->amount_formatted = $this->formatCOP($ingreso->amount);
                $ingreso->category_formatted = $this->formatCategory($ingreso->category);
                return $ingreso;
            });

        // Obtener todos los gastos del fotógrafo
        $expenses = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'gasto')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($gasto) {
                $gasto->created_at_formatted = Carbon::parse($gasto->created_at)->format('M d, Y');
                $gasto->amount_formatted = $this->formatCOP($gasto->amount);
                $gasto->category_formatted = $this->formatCategory($gasto->category);
                return $gasto;
            });

        // Calcular totales
        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $balance = $totalIncome - $totalExpenses;

        // Resumen por períodos (desde el momento actual hacia atrás)
        $now = Carbon::now();
        
        // Resumen de la última semana (7 días)
        $weekIncome = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'ingreso')
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->sum('amount');
        
        $weekExpenses = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'gasto')
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->sum('amount');

        // Resumen del último mes (30 días)
        $monthIncome = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'ingreso')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->sum('amount');
        
        $monthExpenses = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'gasto')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->sum('amount');

        // Resumen del último año (365 días)
        $yearIncome = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'ingreso')
            ->where('created_at', '>=', $now->copy()->subDays(365))
            ->sum('amount');
        
        $yearExpenses = FinancialMovement::where('photographer_id', $photographerId)
            ->where('type', 'gasto')
            ->where('created_at', '>=', $now->copy()->subDays(365))
            ->sum('amount');

        // Obtener clientes deudores (con sesiones de pago pendiente)
        $debtorClients = Client::whereHas('photoSessions', function ($query) use ($photographerId) {
                $query->where('photographer_id', $photographerId)
                      ->where('payment_status', 'Pendiente')
                      ->whereIn('status', ['Por realizar', 'Subiendo', 'Selección', 'Espera', 'Completada']);
            })
            ->withSum(['photoSessions as total_debt' => function ($query) use ($photographerId) {
                $query->where('photographer_id', $photographerId)
                      ->where('payment_status', 'Pendiente')
                      ->whereIn('status', ['Por realizar', 'Subiendo', 'Selección', 'Espera', 'Completada']);
            }], 'total_price')
            ->get()
            ->map(function ($client) {
                // Formatear el total de deuda
                $client->total_debt_formatted = $this->formatCOP($client->total_debt ?? 0);
                return $client;
            })
            ->sortByDesc('total_debt')
            ->values();

        // Calcular totales de deuda
        $totalDebtAmount = $debtorClients->sum('total_debt');
        $totalDebtorClients = $debtorClients->count();

        return response()->json([
            'success' => true,
            'data' => [
                'income' => $income,
                'expenses' => $expenses,
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'balance' => $balance,
                    'total_income_formatted' => $this->formatCOP($totalIncome),
                    'total_expenses_formatted' => $this->formatCOP($totalExpenses),
                    'balance_formatted' => $this->formatCOP($balance)
                ],
                'period_summary' => [
                    'week' => [
                        'income' => $weekIncome,
                        'expenses' => $weekExpenses,
                        'balance' => $weekIncome - $weekExpenses,
                        'income_formatted' => $this->formatCOP($weekIncome),
                        'expenses_formatted' => $this->formatCOP($weekExpenses),
                        'balance_formatted' => $this->formatCOP($weekIncome - $weekExpenses),
                        'period' => 'Last 7 days'
                    ],
                    'month' => [
                        'income' => $monthIncome,
                        'expenses' => $monthExpenses,
                        'balance' => $monthIncome - $monthExpenses,
                        'income_formatted' => $this->formatCOP($monthIncome),
                        'expenses_formatted' => $this->formatCOP($monthExpenses),
                        'balance_formatted' => $this->formatCOP($monthIncome - $monthExpenses),
                        'period' => 'Last 30 days'
                    ],
                    'year' => [
                        'income' => $yearIncome,
                        'expenses' => $yearExpenses,
                        'balance' => $yearIncome - $yearExpenses,
                        'income_formatted' => $this->formatCOP($yearIncome),
                        'expenses_formatted' => $this->formatCOP($yearExpenses),
                        'balance_formatted' => $this->formatCOP($yearIncome - $yearExpenses),
                        'period' => 'Last 365 days'
                    ]
                ],
                'debtor_clients' => [
                    'clients' => $debtorClients,
                    'summary' => [
                        'total_debt_amount' => $totalDebtAmount,
                        'total_debt_amount_formatted' => $this->formatCOP($totalDebtAmount),
                        'total_debtor_clients' => $totalDebtorClients
                    ]
                ]
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Almacena un nuevo recurso creado en el almacenamiento.
     */
    public function store(Request $request, $photographerId)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:ingreso,gasto',
            'category' => 'required|string|max:100', 
            'amount' => 'required|numeric|min:0',
            'detail' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $financialMovement = FinancialMovement::create([
            'photographer_id' => $photographerId,
            'type' => $request->type,
            'category' => $request->category,
            'amount' => $request->amount,
            'detail' => $request->detail ? $request->detail : "Ninguno"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento financiero creado exitosamente.',
            'data' => $financialMovement
        ], Response::HTTP_CREATED);
    }


    /**
     * Elimina el recurso especificado del almacenamiento.
     */
    public function destroy( $photographerId, $movementId)
    {
        $financialMovement = FinancialMovement::find($movementId);

        if (!$financialMovement) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento financiero no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $financialMovement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Movimiento financiero eliminado exitosamente.'
        ], Response::HTTP_OK);
    }
}
