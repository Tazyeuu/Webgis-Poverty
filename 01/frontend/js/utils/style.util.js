/**
 * style.util.js
 * Tanggung Jawab: Mendefinisikan style untuk GeoJSON berdasarkan properti.
 */

export const getGeoJsonStyle = (feature) => {
    // Style default untuk Kavling (Polygon)
    if (feature.geometry.type === 'Polygon') {
        return {
            color: '#10B981', // Secondary color
            weight: 2,
            opacity: 0.8,
            fillColor: '#34D399',
            fillOpacity: 0.4
        };
    }
    
    // Style default untuk Jalan (LineString)
    if (feature.geometry.type === 'LineString') {
        const type = feature.properties.jenis_jalan;
        let color = '#4F46E5'; // Primary color

        if (type === 'Arteri') color = '#EF4444'; // Danger color
        else if (type === 'Kolektor') color = '#F59E0B'; // Warning color

        return {
            color: color,
            weight: 4,
            opacity: 0.9
        };
    }

    return {};
};
