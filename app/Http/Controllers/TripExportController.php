<?php

namespace App\Http\Controllers;

use App\Exports\TripsExport;
use App\Models\Trip;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class TripExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subMonth()->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()    : now()->endOfDay();
        $vId  = $request->integer('vehicle_id') ?: null;
        $format = $request->query('format', 'xlsx');

        $baseName = sprintf(
            'kniha-jizd_%s_%s%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $vId ? '_v' . $vId : ''
        );

        if ($format === 'pdf') {
            $trips = Trip::query()
                ->with(['vehicle:id,name,plate,brand,fuel_type,color', 'driver:id,first_name,last_name'])
                ->when($vId, fn ($q) => $q->where('vehicle_id', $vId))
                ->where('started_at', '>=', $from)
                ->where('started_at', '<=', $to)
                ->orderBy('started_at')
                ->get();

            $vehicle = $vId ? Vehicle::find($vId) : null;
            $vehicleVin = null;
            if ($vehicle && $vehicle->device_id) {
                $vehicleVin = \App\Models\Position::where('device_id', $vehicle->device_id)
                    ->whereNotNull('io_data')
                    ->orderByDesc('recorded_at')
                    ->first()?->vin;
            }

            $company = [
                'name'    => config('firma.name', env('COMPANY_NAME', 'Acme Fleet s.r.o.')),
                'address' => config('firma.address', env('COMPANY_ADDRESS', 'Kotlářská 902/4, 602 00 Brno')),
                'ico'     => config('firma.ico', env('COMPANY_ICO', '')),
                'dic'     => config('firma.dic', env('COMPANY_DIC', '')),
            ];

            $pdf = Pdf::loadView('exports.kniha-jizd-pdf', compact('trips', 'vehicle', 'vehicleVin', 'from', 'to', 'company'))
                ->setPaper('a4', 'landscape');

            return $pdf->download("{$baseName}.pdf");
        }

        // default: XLSX
        return Excel::download(new TripsExport($from, $to, $vId), "{$baseName}.xlsx");
    }
}
