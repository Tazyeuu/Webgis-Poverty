<?php
/**
 * geo_helper.php
 * Tanggung Jawab: Menyediakan fungsi matematika spasial manual (seperti Haversine).
 */

class GeoHelper {
    /**
     * Menghitung jarak antara dua koordinat geografis menggunakan Haversine Formula.
     * Mengembalikan jarak dalam satuan Kilometer.
     */
    public static function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Jari-jari bumi dalam kilometer

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Mencari Warga Miskin dalam radius tertentu dari suatu titik (Rumah Ibadah).
     */
    public static function getWargaDalamRadius($pdo, $pusatLat, $pusatLon, $radiusKm = 1.0) {
        // Ambil semua data warga miskin
        $stmt = $pdo->query("SELECT id, nama_kk, penghasilan, jumlah_tanggungan, ST_AsGeoJSON(geom) as geojson FROM warga_miskin");
        
        $wargaDalamRadius = [];

        while ($row = $stmt->fetch()) {
            $geom = json_decode($row['geojson'], true);
            // GeoJSON Point format: [Longitude, Latitude]
            $wargaLon = $geom['coordinates'][0];
            $wargaLat = $geom['coordinates'][1];

            // Hitung jarak dengan Haversine
            $jarak = self::haversineDistance($pusatLat, $pusatLon, $wargaLat, $wargaLon);

            if ($jarak <= $radiusKm) {
                $wargaDalamRadius[] = [
                    'id' => $row['id'],
                    'nama_kk' => $row['nama_kk'],
                    'penghasilan' => $row['penghasilan'],
                    'jumlah_tanggungan' => $row['jumlah_tanggungan'],
                    'jarak_km' => round($jarak, 2),
                    'geometry' => $geom
                ];
            }
        }

        // Urutkan berdasarkan jarak terdekat
        usort($wargaDalamRadius, function($a, $b) {
            return $a['jarak_km'] <=> $b['jarak_km'];
        });

        return $wargaDalamRadius;
    }
}
?>
