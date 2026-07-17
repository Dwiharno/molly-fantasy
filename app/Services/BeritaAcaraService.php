<?php

namespace App\Services;

use App\Models\StockOpname;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class BeritaAcaraService
{
    public function generate(StockOpname $opname): string
    {
        $opname->load(['details.item', 'user']);

        $pdf = Pdf::loadView('exports.berita-acara', ['opname' => $opname])->setPaper('a4', 'portrait');

        $path = 'berita-acara/'.$opname->code.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $opname->update(['berita_acara_path' => $path]);

        return $path;
    }
}
