<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Toko;

class TokoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $toko = Toko::query()
            ->when($search, function ($query) use ($search) {
                $query->where('nama_toko', 'like', "%{$search}%")
                      ->orWhere('marketplace', 'like', "%{$search}%")
                      ->orWhere('biaya_admin', 'like', "%{$search}%")
                      ->orWhere('biaya_tambahan', 'like', "%{$search}%");
            })
            ->orderBy('nama_toko', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('master.toko', compact('toko', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_toko' => [
                'required',
                'string',
                'max:100',
                Rule::unique('toko', 'nama_toko')
                    ->where('marketplace', $request->marketplace),
            ],
            'marketplace' => ['required', 'in:Shopee,Tiktok'],
            'biaya_admin' => ['required', 'numeric', 'min:0'],
            'biaya_tambahan' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['biaya_tambahan'] = $data['biaya_tambahan'] ?? 0;

        Toko::create($data);

        return redirect()
            ->route('toko.index')
            ->with('success', 'Toko berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $toko = Toko::findOrFail($id);

        $data = $request->validate([
            'nama_toko' => [
                'required',
                'string',
                'max:100',
                Rule::unique('toko', 'nama_toko')
                    ->where('marketplace', $request->marketplace)
                    ->ignore($toko->id_toko, 'id_toko'),
            ],
            'marketplace' => ['required', 'in:Shopee,Tiktok'],
            'biaya_admin' => ['required', 'numeric', 'min:0'],
            'biaya_tambahan' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['biaya_tambahan'] = $data['biaya_tambahan'] ?? 0;

        $toko->update($data);

        return redirect()
            ->route('toko.index')
            ->with('success', 'Data toko berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $toko = Toko::findOrFail($id);
        $toko->delete();

        return redirect()
            ->route('toko.index')
            ->with('success', 'Toko berhasil dihapus.');
    }

    public function show($id)
    {
        $toko = Toko::findOrFail($id);

        return response()->json($toko);
    }
}