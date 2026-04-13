<?php


namespace App\Http\Controllers\API;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;

class CountryController extends Controller
{

    /**
     * Liste des pays avec leurs opérateurs
     */
    public function index()
    {
        $countries = Country::with('operators')
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'iso' => 'required|string',
            'iso3' => 'required|string',
            'phonecode' => 'required|string',
            'currency' => 'nullable|string',
            'status' => 'required|string',
            'flag' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('flag')) {
            $path = $request->file('flag')->store('flags', 'public');
            $data['flag'] = $path;
        }

        $data['status']= $data['status']=='active';

        $country = Country::create($data);

        return response()->json($country);
    }
    public function update(Request $request, $id)
    {
        $country = Country::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'iso' => 'required|string',
            'iso3' => 'required|string',
            'phonecode' => 'required|string',
            'currency' => 'nullable|string',
            'status' => 'required|string',
            'flag' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('flag')) {
            // supprimer ancien
            if ($country->flag) {
                Storage::disk('public')->delete($country->flag);
            }

            $path = $request->file('flag')->store('flags', 'public');
            $data['flag'] = $path;
        }
        $data['status']= $data['status']=='active';
        $country->update($data);

        return response()->json($country);
    }
}
