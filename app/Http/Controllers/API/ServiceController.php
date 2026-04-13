<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Liste des services actifs (API mobile)
     */
    public function index()
    {
        $services = Service::where('is_active', true)
            ->orderBy('position')
            ->get();

        return Helpers::success($services);
    }

    /**
     * Liste complète (admin)
     */
    public function all()
    {
        return response()->json(
            Service::orderBy('position')->get()
        );
    }

    /**
     * Créer un service
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'route' => 'required|string|unique:services,route',
            'icon' => 'nullable|string',
            'position' => 'nullable|integer',
            'category' => 'nullable|string'
        ]);

        $service = Service::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'route' => $request->route,
            'position' => $request->position ?? 0,
            'category' => $request->category,
            'is_active' => true
        ]);

        return response()->json($service, 201);
    }

    /**
     * Mettre à jour un service
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'route' => 'sometimes|string|unique:services,route,' . $id,
            'icon' => 'nullable|string',
            'position' => 'nullable|integer',
            'category' => 'nullable|string'
        ]);

        $service->update($request->all());

        return response()->json($service);
    }

    /**
     * Activer / Désactiver un service (🔥 important)
     */
    public function toggle($id)
    {
        $service = Service::findOrFail($id);

        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'message' => 'Service mis à jour',
            'is_active' => $service->is_active
        ]);
    }

    /**
     * Supprimer un service
     */
    public function destroy($id)
    {
        Service::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Service supprimé'
        ]);
    }
}
