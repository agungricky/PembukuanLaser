<?php

namespace App\Http\Controllers;

use App\Exports\gudangStokExport;
use App\Imports\stokImport;
use App\Models\kategori;
use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\ResiPage;
use App\Models\retur;
use App\Models\stok_produk;
use App\Models\User;
use App\Services\Gudang\CustomService;
use App\Services\Gudang\DashboardService;
use App\Services\Gudang\KategoriService;
use App\Services\Gudang\ProdukService;
use App\Services\Gudang\ReturService;
use App\Services\Gudang\RiwayatAktivitasService;
use App\Services\Gudang\SampleService;
use App\Services\Gudang\TransaksiService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Fpdi;

class GudangController extends Controller
{

    protected DashboardService $dashboardService;
    protected TransaksiService $transaksiService;
    protected SampleService $sampleService;
    protected ReturService $returService;
    protected CustomService $customService;
    protected ProdukService $produkService;
    protected KategoriService $kategoriService;
    protected RiwayatAktivitasService $riwayatAktivitasService;

    public function __construct(
        DashboardService $dashboardService,
        TransaksiService $transaksiService,
        SampleService $sampleService,
        ReturService $returService,
        CustomService $customService,
        ProdukService $produkService,
        KategoriService $kategoriService,
        RiwayatAktivitasService $riwayatAktivitasService
    )
    {
        $this->dashboardService = $dashboardService;
        $this->transaksiService = $transaksiService;
        $this->sampleService = $sampleService;
        $this->returService = $returService;
        $this->customService = $customService;
        $this->produkService = $produkService;
        $this->kategoriService = $kategoriService;
        $this->riwayatAktivitasService = $riwayatAktivitasService;
    }

    // ==================================================//
    // ================== DASHBOARD =====================//
    // ==================================================//
    public function gudang()
    {
        return $this->dashboardService->gudang();
    }

    public function gudanginventory()
    {
        return $this->dashboardService->gudanginventory();
    }

    public function allindex()
    {
        return view('gudang.allpesanan');
    }

    public function allpesanan($filter)
    {
        return $this->dashboardService->allpesanan($filter);
    }

    public function detailcard($card)
    {
        return $this->dashboardService->detailcard($card);
    }

    public function detailpesanan($filter, $sku)
    {
        return $this->detailpesanan($filter, $sku);
    }

    // ==================================================//
    // ================== TRANSAKSI =====================//
    // ==================================================//
    public function perludisiapkan(){
        return view('')
    }
    public function show(string $id)
    {
        return view('gudang.transaksi', compact('id'));
    }

    public function showdata($filter)
    {
        return $this->transaksiService->showdata($filter);
    }

    public function store(Request $request)
    {
        return $this->transaksiService->store($request);
    }

    public function updateStatus(Request $request)
    {
        return $this->transaksiService->updateStatus($request);
    }

    public function updateselesai(Request $request){
        return $this->transaksiService->updateselesai($request);
    }

    public function previewResi($token)
    {
        return $this->transaksiService->previewResi($token);
    }

    public function cetakResi(Request $request)
    {
        return $this->transaksiService->cetakResi($request);
    }

    // ==================================================//
    // ============== BARANG SAMPEL =====================//
    // ==================================================//
    public function barangsampel()
    {
        return $this->sampleService->barangsampel();
    }

    public function sampelcreate(Request $request)
    {
        return $this->sampleService->sampelcreate($request);
    }

    // ==================================================//
    // =============== BARANG RETUR =====================//
    // ==================================================//
    public function barangretur(Request $request)
    {
        return $this->returService->barangretur($request);
    }

    // Data Retur View Modal
    public function detailRetur($no_pesanan)
    {
        return $this->returService->detailRetur($no_pesanan);
    }

    public function returCreate(Request $request)
    {
        return $this->returService->returCreate($request);
    }

    // ==================================================//
    // ============== PRODUK CUSTOM =====================//
    // ==================================================//
    public function produkcustom()
    {
        return $this->customService->produkcustom();
    }

    // ==================================================//
    // ================== PRODUK ========================//
    // ==================================================//
    public function produk()
    {
        return $this->produkService->produk();
    }

    public function produkShow($id)
    {
        return $this->produkService->produkShow($id);
    }

    public function updatestok(Request $request)
    {
        return $this->produkService->updatestok($request);
    }

    public function stokExport()
    {
        return $this->produkService->stokExport();
    }

    public function stokImport(Request $request)
    {
        return $this->produkService->stokImport($request);
    }

    public function importUpdate(Request $request)
    {
        return $this->produkService->importUpdate($request);
    }

    // ==================================================//
    // ================== KATEGORI ======================//
    // ==================================================//
    public function kategori()
    {
        $this->kategoriService->kategori();
    }

    public function kategorishow(string $id)
    {
        $this->kategoriService->kategorishow($id);
    }

    // ==================================================//
    // ========== RIWAYAT AKTIVITAS =====================//
    // ==================================================//
    public function riwayataktivitas()
    {
        return view('gudang.riwayat_aktivitas');
    }

    public function riwayatAktivitasData(Request $request)
    {
       $this->riwayatAktivitasService->riwayatAktivitasData($request);
    }
}
