<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class TicketBarcodeService
{
    protected const TICKET_LENGTH = 16;

    protected const VALUE_START = 11; // posisi 1-indexed

    protected const VALUE_LENGTH = 5;

    /**
     * Parse barcode tiket dan ambil 5 digit pada posisi 11-15 dari total 16 digit.
     *
     * Contoh: 0005007728001082 (16 digit) -> posisi 11-15 -> "00108"
     *
     * PENTING: Barcode tiket sering ditempel/di-paste dari Google Sheets/Excel yang
     * menyimpannya sebagai angka, sehingga angka nol di depan otomatis hilang
     * (mis. "0005007728001082" jadi tampil "5007728001082", 13 digit). Fungsi ini
     * otomatis menambahkan kembali angka nol di depan hingga genap 16 digit
     * sebelum mengambil posisi 11-15, sehingga hasilnya tetap benar walau nol
     * di depan sudah hilang.
     *
     * @return array{code: string, value: int}
     */
    public function parse(string $barcode): array
    {
        $barcode = trim($barcode);
        $digitsOnly = preg_replace('/[^0-9]/', '', $barcode);

        if ($digitsOnly === '' || $digitsOnly === null) {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode tiket tidak mengandung angka yang valid.',
            ]);
        }

        // Normalisasi ke 16 digit: tambahkan nol di depan jika lebih pendek
        // (mengembalikan nol yang hilang akibat konversi ke angka di spreadsheet),
        // atau ambil 16 digit terakhir jika lebih panjang dari 16.
        $normalized = strlen($digitsOnly) >= self::TICKET_LENGTH
            ? substr($digitsOnly, -self::TICKET_LENGTH)
            : str_pad($digitsOnly, self::TICKET_LENGTH, '0', STR_PAD_LEFT);

        $rawCode = substr($normalized, self::VALUE_START - 1, self::VALUE_LENGTH);
        $code = str_pad(substr($rawCode, -self::VALUE_LENGTH), self::VALUE_LENGTH, '0', STR_PAD_LEFT);

        return [
            'code' => $code,
            'value' => (int) $code,
        ];
    }
}
