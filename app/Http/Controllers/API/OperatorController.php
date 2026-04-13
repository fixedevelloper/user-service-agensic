<?php


namespace App\Http\Controllers\API;
use App\Http\Helpers\Helpers;
use App\Models\Country;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;

class OperatorController extends Controller
{

    /**
     * Liste des pays avec leurs opérateurs
     */
    public function index()
    {
        $operators = Operator::with('country')
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }
    public function operatorbyCountry($country_id)
    {
        $operators = Operator::with('country')
            ->where('country_id', $country_id)
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }
    public function getOperatorsList(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'country_code' => 'required|string',
        ]);

        $operators = Operator::with('country')
            ->whereHas('country', function ($query) use ($validated) {
                $query->where('iso', $validated['country_code']);
            })
            ->get();

        return Helpers::success($operators);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'country_id' => 'required|numeric',
            'status' => 'required|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('operators', 'public');
            $data['logo'] = $path;
        }

        $operator = Operator::create($data);

        return Helpers::success($operator);
    }
    public function update(Request $request, $id)
    {
        $operator = Operator::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'country_id' => 'required|numeric',
            'status' => 'required|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // supprimer ancien
            if ($operator->logo) {
                Storage::disk('public')->delete($operator->logo);
            }

            $path = $request->file('logo')->store('operators', 'public');
            $data['logo'] = $path;
        }

        $operator->update($data);

        return Helpers::success($operator);
    }
}
