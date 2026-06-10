export const setupDrawControls = (map, onGeometryCreated) => {
    const drawControl = new L.Control.Draw({
        draw: { marker: true, polyline: true, polygon: true, circle: false, circlemarker: false, rectangle: false }
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, function (event) {
        const layer = event.layer;
        const type = event.layerType;
        
        // Peta draw default tipe: marker -> spbu, polyline -> jalan, polygon -> kavling
        let customType = type;
        if(type === 'marker') customType = 'spbu';
        if(type === 'polyline') customType = 'jalan';
        if(type === 'polygon') customType = 'kavling';
        if(window.currentDrawType) customType = window.currentDrawType; // Untuk P2/P3

        onGeometryCreated(customType, layer.toGeoJSON().geometry, layer);
    });

    // Custom Menu Handlers
    const markerDrawer = new L.Draw.Marker(map, drawControl.options.draw.marker);
    const polylineDrawer = new L.Draw.Polyline(map, drawControl.options.draw.polyline);
    const polygonDrawer = new L.Draw.Polygon(map, drawControl.options.draw.polygon);

    document.getElementById('btn-draw-spbu')?.addEventListener('click', () => { window.currentDrawType='spbu'; markerDrawer.enable(); });
    document.getElementById('btn-draw-jalan')?.addEventListener('click', () => { window.currentDrawType='jalan'; polylineDrawer.enable(); });
    document.getElementById('btn-draw-kavling')?.addEventListener('click', () => { window.currentDrawType='kavling'; polygonDrawer.enable(); });
    
    return drawControl;
};
