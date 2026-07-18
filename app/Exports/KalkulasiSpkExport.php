<?php

namespace App\Exports;

use App\Models\KalkulasiSpk;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KalkulasiSpkExport implements FromView, ShouldAutoSize
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function view(): View
    {
        $kalkulasi = KalkulasiSpk::findOrFail($this->id);
        
        return view('spk.export_laporan', [
            'kalkulasi' => $kalkulasi
        ]);
    }
}