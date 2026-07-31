<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('plat');
    }
    
    public function gancinama()
    {
        return view('gancinama');
    }

    public function emblem()
    {
        $fonts = [
            ['value' => 'f1', 'css' => 'AdriaDeco', 'label' => 'AdriaDeco'],
            ['value' => 'f2', 'css' => 'Aeroblade DEMO', 'label' => 'Aeroblade DEMO'],
            ['value' => 'f3', 'css' => 'airstrike', 'label' => 'airstrike'],
            ['value' => 'f4', 'css' => 'BernardMTStd-Condensed', 'label' => 'BernardMTStd-Condensed'],
            ['value' => 'f5', 'css' => 'Bismillah Script', 'label' => 'Bismillah Script'],
            ['value' => 'f6', 'css' => 'COOPER BL', 'label' => 'COOPER BL'],
            ['value' => 'f7', 'css' => 'Crizen', 'label' => 'Crizen'],
            ['value' => 'f8', 'css' => 'Design System C W01 900R', 'label' => 'Design System C W01 900R'],
            ['value' => 'f9', 'css' => 'Ductus W01 Bold', 'label' => 'Ductus W01 Bold'],
            ['value' => 'f10', 'css' => 'flamenco-d', 'label' => 'flamenco-d'],
            ['value' => 'f11', 'css' => 'gang of three', 'label' => 'gang of three'],
            ['value' => 'f12', 'css' => 'Helvetica Black Condensed', 'label' => 'Helvetica Black Condensed'],
            ['value' => 'f13', 'css' => 'hemi head bd it', 'label' => 'hemi head bd it'],
            ['value' => 'f14', 'css' => 'Henshin', 'label' => 'Henshin'],
            ['value' => 'f15', 'css' => 'Jacksilver', 'label' => 'Jacksilver'],
            ['value' => 'f16', 'css' => 'JAPANESE_2020', 'label' => 'JAPANESE 2020'],
            ['value' => 'f17', 'css' => 'La Macchina', 'label' => 'La Macchina'],
            ['value' => 'f18', 'css' => 'LTAtomatic', 'label' => 'LTAtomatic'],
            ['value' => 'f19', 'css' => 'MACHINEN', 'label' => 'MACHINEN'],
            ['value' => 'f20', 'css' => 'Marmellata(Jam)_demo', 'label' => 'Marmellata(Jam)_demo'],
            ['value' => 'f21', 'css' => 'No Seven Bold', 'label' => 'No Seven Bold'],
            ['value' => 'f22', 'css' => 'Osaka San', 'label' => 'Osaka San'],
            ['value' => 'f23', 'css' => 'Planet Kosmos', 'label' => 'Planet Kosmos'],
            ['value' => 'f24', 'css' => 'Rockabilly', 'label' => 'Rockabilly'],
            ['value' => 'f25', 'css' => 'Sketter DEMO', 'label' => 'Sketter DEMO'],
            ['value' => 'f26', 'css' => 'Slantblaze Pro', 'label' => 'Slantblaze Pro'],
            ['value' => 'f27', 'css' => 'Transformers Movie', 'label' => 'Transformers Movie'],
            ['value' => 'f28', 'css' => 'VANGO-Regular', 'label' => 'VANGO Regular'],
            ['value' => 'f29', 'css' => 'VerminVibes', 'label' => 'Vermin Vibes'],
            ['value' => 'f30', 'css' => 'vespa font', 'label' => 'vespa font'],
        ];

        return view('emblem', compact('fonts'));
    }


    public function coba()
    {
        return view('coba');
    }
}
