<?php

namespace Database\Seeders;

use App\Models\InstrumentPrice;
use Illuminate\Database\Seeder;

class InstrumentPriceSeeder extends Seeder
{
    public function run(): void
    {
        $instruments = [
            ['name' => 'Analisis Kadar Gula-Gula pereduksi metode Luff Shrool', 'unit' => 'Per hasil pengujian', 'price_regular' => 200000, 'price_uns' => null, 'note' => null, 'sort_order' => 1],
            ['name' => 'Analisis Kadar Gula-Gula pereduksi metode Lane-Eynon', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => null, 'note' => null, 'sort_order' => 2],
            ['name' => 'Analisis Sukrosa (Luff Shrool)', 'unit' => 'Per hasil pengujian', 'price_regular' => 200000, 'price_uns' => null, 'note' => null, 'sort_order' => 3],
            ['name' => 'Analisis Sukrosa (Lane-Eynon)', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => null, 'note' => null, 'sort_order' => 4],
            ['name' => 'Analisis Kadar Protein (Kjehldal)', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => null, 'note' => null, 'sort_order' => 5],
            ['name' => 'Analisis Vitamin C', 'unit' => 'Per hasil pengujian', 'price_regular' => 100000, 'price_uns' => null, 'note' => null, 'sort_order' => 6],
            ['name' => 'Analisis Kadar Air', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => 100000, 'note' => null, 'sort_order' => 7],
            ['name' => 'Analisis Kadar Garam', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => 100000, 'note' => null, 'sort_order' => 8],
            ['name' => 'Pengujian Anti Bakteri (Metode difusi cakram)', 'unit' => '4 hasil pengujian/cawan', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => null, 'sort_order' => 9],
            ['name' => 'Pengujian Anti Bakteri (Metode difusi sumuran)', 'unit' => '4 hasil pengujian/cawan', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => null, 'sort_order' => 10],
            ['name' => 'Pengujian ALT', 'unit' => 'Per hasil pengujian', 'price_regular' => 200000, 'price_uns' => 150000, 'note' => null, 'sort_order' => 11],
            ['name' => 'Pengujian E Coli metode Most Probable Number (MPN)', 'unit' => 'Per hasil pengujian', 'price_regular' => 200000, 'price_uns' => 150000, 'note' => null, 'sort_order' => 12],
            ['name' => 'Pengujian E Coli metode Filtrasi Membran', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => null, 'note' => null, 'sort_order' => 13],
            ['name' => 'Pengujian Lignin (metode chesson)', 'unit' => 'Per hasil pengujian', 'price_regular' => 150000, 'price_uns' => null, 'note' => null, 'sort_order' => 14],
            ['name' => 'Selulosa (metode chesson)', 'unit' => 'Per hasil pengujian', 'price_regular' => 110000, 'price_uns' => null, 'note' => null, 'sort_order' => 15],
            ['name' => 'Hemiselulosa (metode chesson)', 'unit' => 'Per hasil pengujian', 'price_regular' => 110000, 'price_uns' => null, 'note' => null, 'sort_order' => 16],
            ['name' => 'Lignin, Selulosa, dan Hemiselulosa (chesson)', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => null, 'note' => null, 'sort_order' => 17],
            ['name' => 'Analisis antioksidan (senyawa standar Quercetin dan metode DPPH)', 'unit' => 'Per hasil pengujian', 'price_regular' => 250000, 'price_uns' => null, 'note' => null, 'sort_order' => 18],
            ['name' => 'Analisis Fitokimia metabolit sekunder (Flavonoid)', 'unit' => 'Per hasil pengujian', 'price_regular' => 120000, 'price_uns' => null, 'note' => null, 'sort_order' => 19],
            ['name' => 'Analisis dengan GC', 'unit' => 'Per hasil pengujian', 'price_regular' => 100000, 'price_uns' => 60000, 'note' => null, 'sort_order' => 20],
            ['name' => 'Analisis dengan GC-MS', 'unit' => 'Per hasil pengujian', 'price_regular' => 500000, 'price_uns' => 400000, 'note' => null, 'sort_order' => 21],
            ['name' => 'Analisis dengan HPLC', 'unit' => 'Per hasil pengujian', 'price_regular' => 100000, 'price_uns' => 60000, 'note' => null, 'sort_order' => 22],
            ['name' => 'Analisis dengan UV-VIS Spektrometer (kualitatif, 200-900 nm)', 'unit' => 'Per hasil pengujian', 'price_regular' => 60000, 'price_uns' => 50000, 'note' => null, 'sort_order' => 23],
            ['name' => 'Analisis dengan UV-VIS Spektrometer (kuantitatif)', 'unit' => 'Per hasil pengujian', 'price_regular' => 25000, 'price_uns' => 5000, 'note' => null, 'sort_order' => 24],
            ['name' => 'Analisis Mercury/Hg dengan DMA (cair, padat dan gas)', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => null, 'sort_order' => 25],
            ['name' => 'Pengukuran Ukuran Partikel', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => 'PSA Nano - zeta potensial', 'sort_order' => 26],
            ['name' => 'Pengukuran Zeta Potensial', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => 'PSA Nano - zeta potensial', 'sort_order' => 27],
            ['name' => 'Pengukuran Bobot Molekul', 'unit' => 'Per hasil pengujian', 'price_regular' => 600000, 'price_uns' => 500000, 'note' => 'PSA Nano - zeta potensial', 'sort_order' => 28],
            ['name' => 'Analisis kualitatif ATR FTIR', 'unit' => 'Per hasil pengujian', 'price_regular' => 100000, 'price_uns' => 50000, 'note' => null, 'sort_order' => 29],
            ['name' => 'Analisis dengan TLC Scanner (20x20)', 'unit' => 'Per plate (20x20)', 'price_regular' => 300000, 'price_uns' => 150000, 'note' => 'KLT', 'sort_order' => 30],
            ['name' => 'Analisis dengan TLC Scanner (10x10)', 'unit' => 'Per plate (10x10)', 'price_regular' => 200000, 'price_uns' => 100000, 'note' => 'KLT', 'sort_order' => 31],
            ['name' => 'Pengukuran Viskometer', 'unit' => 'Per hasil pengujian', 'price_regular' => 75000, 'price_uns' => 50000, 'note' => null, 'sort_order' => 32],
            ['name' => 'Impact tester', 'unit' => 'Per hasil pengujian', 'price_regular' => 60000, 'price_uns' => 50000, 'note' => null, 'sort_order' => 33],
            ['name' => 'AAS', 'unit' => 'Per hasil pengujian', 'price_regular' => 25000, 'price_uns' => 10000, 'note' => null, 'sort_order' => 34],
            ['name' => 'Konduktivitas Termal', 'unit' => 'Per hasil pengujian', 'price_regular' => 120000, 'price_uns' => 100000, 'note' => null, 'sort_order' => 35],
            ['name' => 'XRF', 'unit' => 'Per hasil pengujian', 'price_regular' => 300000, 'price_uns' => 200000, 'note' => null, 'sort_order' => 36],
            ['name' => 'Oral/Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 1000, 'price_uns' => null, 'note' => null, 'sort_order' => 37],
            ['name' => 'Cek Kolesterol/Cek Glukosa Hewan coba', 'unit' => 'Per ekor', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 38],
            ['name' => 'Pengambilan Darah Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 4000, 'price_uns' => null, 'note' => null, 'sort_order' => 39],
            ['name' => 'Penimbangan/pemberian kode Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 1000, 'price_uns' => null, 'note' => null, 'sort_order' => 40],
            ['name' => 'Pengambilan Organ/Pembedahan Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 10000, 'price_uns' => null, 'note' => null, 'sort_order' => 41],
            ['name' => 'Pengambilan Kulit Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 10000, 'price_uns' => null, 'note' => null, 'sort_order' => 42],
            ['name' => 'Induksi/Injeksi/Anastesi', 'unit' => 'Per ekor', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 43],
            ['name' => 'Pengecekan Suhu Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 44],
            ['name' => 'Pembuatan pakan ternak tinggi lemak', 'unit' => 'Per Kg', 'price_regular' => 100000, 'price_uns' => null, 'note' => null, 'sort_order' => 45],
            ['name' => 'Pembuatan pakan ternak rendah lemak', 'unit' => 'Per Kg', 'price_regular' => 100000, 'price_uns' => null, 'note' => null, 'sort_order' => 46],
            ['name' => 'Pembuatan model hewan coba Anemia', 'unit' => 'Per ekor', 'price_regular' => 20000, 'price_uns' => null, 'note' => null, 'sort_order' => 47],
            ['name' => 'Pembuatan ekstrak Simplisia 0-100 gram', 'unit' => 'gram', 'price_regular' => 400000, 'price_uns' => 350000, 'note' => null, 'sort_order' => 48],
            ['name' => 'Penanganan limbah Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 49],
            ['name' => 'Pengambilan hasil pengujian kotoran Hewan Coba', 'unit' => 'Per ekor', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 50],
            ['name' => 'Pemisahan serum Hewan Coba menggunakan centrifuge', 'unit' => 'Per tube', 'price_regular' => 2000, 'price_uns' => null, 'note' => null, 'sort_order' => 51],
            ['name' => 'Perawatan Kandang, Tempat pakan dan Minum Hewan coba', 'unit' => 'Per minggu', 'price_regular' => 20000, 'price_uns' => null, 'note' => null, 'sort_order' => 52],
            ['name' => 'Daya serap Iod (Titrimetri)', 'unit' => 'Per hasil pengujian', 'price_regular' => 100000, 'price_uns' => null, 'note' => 'Dalam UNS', 'sort_order' => 53],
        ];

        foreach ($instruments as $data) {
            InstrumentPrice::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
