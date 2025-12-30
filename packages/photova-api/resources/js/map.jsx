import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import MarkerClusterGroup from 'react-leaflet-cluster';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix default marker icon issue with webpack/vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

const mapStyles = `
.leaflet-container {
    background: #0d1117;
}
.leaflet-control-zoom a {
    background: #161b22 !important;
    color: #c9d1d9 !important;
    border-color: #30363d !important;
}
.leaflet-control-zoom a:hover {
    background: #21262d !important;
}
.leaflet-control-attribution {
    background: rgba(13, 17, 23, 0.8) !important;
    color: #8b949e !important;
}
.leaflet-control-attribution a {
    color: #58a6ff !important;
}
.marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
    background: rgba(88, 166, 255, 0.3) !important;
}
.marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
    background: #58a6ff !important;
    color: white !important;
    font-weight: 600;
}
.custom-marker {
    border: 2px solid #58a6ff;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}
.leaflet-popup-content-wrapper {
    background: #161b22 !important;
    border: 1px solid #30363d;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5) !important;
}
.leaflet-popup-content {
    margin: 0 !important;
    color: #c9d1d9;
}
.leaflet-popup-tip {
    background: #161b22 !important;
}
`;

function createThumbnailIcon(assetId) {
    return L.divIcon({
        html: `<img src="/api/assets/${assetId}/thumb?w=48&h=48" class="custom-marker" style="width:40px;height:40px;object-fit:cover;">`,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40],
        className: ''
    });
}

function FitBounds({ bounds, onBoundsReady }) {
    const map = useMap();
    const initialFitDone = React.useRef(false);
    
    useEffect(() => {
        if (!bounds || initialFitDone.current) return;
        
        const isSinglePoint = bounds.north === bounds.south && bounds.east === bounds.west;
        
        if (isSinglePoint) {
            map.setView([bounds.north, bounds.east], 14);
        } else {
            const leafletBounds = L.latLngBounds(
                [bounds.south, bounds.west],
                [bounds.north, bounds.east]
            );
            map.fitBounds(leafletBounds, { padding: [50, 50], maxZoom: 16 });
        }
        initialFitDone.current = true;
        
        setTimeout(() => onBoundsReady?.(), 100);
    }, [bounds, map, onBoundsReady]);
    
    return null;
}

function MapEvents({ onMoveEnd }) {
    const map = useMap();
    
    useEffect(() => {
        const handleMoveEnd = () => {
            const bounds = map.getBounds();
            onMoveEnd({
                north: bounds.getNorth(),
                south: bounds.getSouth(),
                east: bounds.getEast(),
                west: bounds.getWest(),
            });
        };
        
        map.on('moveend', handleMoveEnd);
        return () => map.off('moveend', handleMoveEnd);
    }, [map, onMoveEnd]);
    
    return null;
}

function AssetPopup({ asset }) {
    return (
        <div style={{ minWidth: 200 }}>
            <img 
                src={`/api/assets/${asset.id}/thumb?w=400&h=240`} 
                alt={asset.filename}
                style={{ width: '100%', height: 120, objectFit: 'cover', borderRadius: '4px 4px 0 0' }}
            />
            <div style={{ padding: 12 }}>
                <div style={{ fontSize: 13, fontWeight: 500, marginBottom: 4, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {asset.filename}
                </div>
                <div style={{ color: '#8b949e', fontSize: 11 }}>
                    {asset.camera && <div>{asset.camera}</div>}
                    {asset.takenAt && <div>{new Date(asset.takenAt).toLocaleDateString()}</div>}
                </div>
            </div>
        </div>
    );
}

function PhotoMap() {
    const [loading, setLoading] = useState(true);
    const [assets, setAssets] = useState([]);
    const [initialBounds, setInitialBounds] = useState(null);
    const [count, setCount] = useState(0);
    const [totalCount, setTotalCount] = useState(0);
    const [mapReady, setMapReady] = useState(false);
    const fetchController = React.useRef(null);

    useEffect(() => {
        async function loadInitialBounds() {
            try {
                const res = await window.apiFetch('/api/assets/geo');
                if (res.ok) {
                    const data = await res.json();
                    setInitialBounds(data.bounds);
                    setTotalCount(data.count || 0);
                    if (!data.bounds) {
                        setAssets([]);
                        setCount(0);
                    }
                }
            } catch (e) {
                console.error('Failed to load geo assets:', e);
            }
            setLoading(false);
        }
        loadInitialBounds();
    }, []);

    const fetchAssetsInBounds = React.useCallback(async (bounds) => {
        if (fetchController.current) {
            fetchController.current.abort();
        }
        fetchController.current = new AbortController();

        try {
            const params = new URLSearchParams({
                north: bounds.north,
                south: bounds.south,
                east: bounds.east,
                west: bounds.west,
            });
            const res = await window.apiFetch(`/api/assets/geo?${params}`, {
                signal: fetchController.current.signal,
            });
            if (res.ok) {
                const data = await res.json();
                setAssets(data.assets || []);
                setCount(data.count || 0);
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error('Failed to load assets in bounds:', e);
            }
        }
    }, []);

    const handleBoundsReady = React.useCallback(() => {
        setMapReady(true);
    }, []);

    const handleMoveEnd = React.useCallback((bounds) => {
        if (mapReady) {
            fetchAssetsInBounds(bounds);
        }
    }, [mapReady, fetchAssetsInBounds]);

    if (loading) {
        return (
            <div style={{ height: 600 }} className="flex items-center justify-center rounded-lg border border-[#30363d]">
                <svg className="animate-spin h-6 w-6 text-[#8b949e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        );
    }

    if (totalCount === 0) {
        return (
            <div style={{ height: 600 }} className="flex flex-col items-center justify-center text-center rounded-lg border border-[#30363d]">
                <div className="w-16 h-16 rounded-full bg-[#21262d] flex items-center justify-center mb-4">
                    <svg className="w-8 h-8 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 className="text-[#c9d1d9] font-medium mb-1">No geotagged photos</h3>
                <p className="text-[#8b949e] text-sm max-w-sm">
                    Upload photos with GPS data in their EXIF metadata to see them on the map.
                </p>
            </div>
        );
    }

    return (
        <>
            <style>{mapStyles}</style>
            <div className="flex items-center gap-3 mb-4">
                <span className="px-2 py-0.5 bg-[#21262d] border border-[#30363d] rounded-full text-xs text-[#8b949e]">
                    {count} photo{count !== 1 ? 's' : ''} in view
                </span>
                <span className="text-xs text-[#8b949e]">
                    ({totalCount} total geotagged)
                </span>
            </div>
            <div style={{ height: 600 }} className="rounded-lg border border-[#30363d] overflow-hidden">
                <MapContainer
                    center={[0, 0]}
                    zoom={2}
                    style={{ height: '100%', width: '100%' }}
                    zoomControl={true}
                >
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/attributions">CARTO</a>'
                        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
                        subdomains="abcd"
                        maxZoom={19}
                    />
                    <FitBounds bounds={initialBounds} onBoundsReady={handleBoundsReady} />
                    <MapEvents onMoveEnd={handleMoveEnd} />
                    <MarkerClusterGroup
                        chunkedLoading
                        showCoverageOnHover={false}
                        maxClusterRadius={50}
                        spiderfyOnMaxZoom={true}
                    >
                        {assets.map(asset => (
                            <Marker 
                                key={asset.id}
                                position={[asset.lat, asset.lng]}
                                icon={createThumbnailIcon(asset.id)}
                            >
                                <Popup>
                                    <AssetPopup asset={asset} />
                                </Popup>
                            </Marker>
                        ))}
                    </MarkerClusterGroup>
                </MapContainer>
            </div>
        </>
    );
}

const container = document.getElementById('photo-map');
if (container) {
    const root = createRoot(container);
    root.render(<PhotoMap />);
}
