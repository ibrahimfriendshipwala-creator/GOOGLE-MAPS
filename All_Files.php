<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Application</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        #map { height: 100vh; width: 100%; }
        .search-container { position: absolute; top: 10px; left: 10px; z-index: 1000; width: 300px; }
        .directions-container { position: absolute; top: 50px; left: 10px; z-index: 1000; width: 300px; }
        .favorites-container { position: absolute; top: 170px; left: 10px; z-index: 1000; width: 300px; }
        @media (max-width: 640px) {
            .search-container, .directions-container, .favorites-container { width: 90%; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div id="map"></div>
    <div class="search-container bg-white p-4 rounded shadow">
        <input id="searchInput" type="text" placeholder="Search location..." class="w-full p-2 border rounded">
        <button onclick="searchLocation()" class="mt-2 bg-blue-500 text-white p-2 rounded w-full">Search</button>
    </div>
    <div class="directions-container bg-white p-4 rounded shadow">
        <input id="startInput" type="text" placeholder="Start location" class="w-full p-2 border rounded mb-2">
        <input id="endInput" type="text" placeholder="End location" class="w-full p-2 border rounded">
        <button onclick="getDirections()" class="mt-2 bg-green-500 text-white p-2 rounded w-full">Get Directions</button>
    </div>
    <div class="favorites-container bg-white p-4 rounded shadow">
        <h3 class="font-bold">Favorites</h3>
        <ul id="favoritesList" class="mt-2"></ul>
        <button onclick="clearFavorites()" class="mt-2 bg-red-500 text-white p-2 rounded w-full">Clear Favorites</button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        let markers = [];
        let favorites = JSON.parse(localStorage.getItem('favorites')) || [];

        // Load favorites on startup
        updateFavoritesList();

        // Search location
        async function searchLocation() {
            const query = document.getElementById('searchInput').value;
            if (!query) return;

            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
            const data = await response.json();
            if (data.length > 0) {
                const { lat, lon, display_name } = data[0];
                map.setView([lat, lon], 13);
                const marker = L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<b>${display_name}</b><br><button onclick="saveFavorite(${lat}, ${lon}, '${display_name}')">Save to Favorites</button>`);
                markers.push(marker);
            }
        }

        // Directions
        let routingControl = null;
        async function getDirections() {
            const start = document.getElementById('startInput').value;
            const end = document.getElementById('endInput').value;
            if (!start || !end) return;

            const startResp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(start)}`);
            const endResp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(end)}`);
            const startData = await startResp.json();
            const endData = await endResp.json();

            if (startData.length > 0 && endData.length > 0) {
                const startCoords = [startData[0].lat, startData[0].lon];
                const endCoords = [endData[0].lat, endData[0].lon];

                if (routingControl) map.removeControl(routingControl);
                routingControl = L.Routing.control({
                    waypoints: [
                        L.latLng(startCoords[0], startCoords[1]),
                        L.latLng(endCoords[0], endCoords[1])
                    ],
                    routeWhileDragging: true
                }).addTo(map);
            }
        }

        // Save favorite location
        function saveFavorite(lat, lon, name) {
            favorites.push({ lat, lon, name });
            localStorage.setItem('favorites', JSON.stringify(favorites));
            updateFavoritesList();
        }

        // Update favorites list
        function updateFavoritesList() {
            const list = document.getElementById('favoritesList');
            list.innerHTML = '';
            favorites.forEach((fav, index) => {
                const li = document.createElement('li');
                li.innerHTML = `${fav.name} <button onclick="goToFavorite(${fav.lat}, ${fav.lon})">Go</button> <button onclick="removeFavorite(${index})">Remove</button>`;
                list.appendChild(li);
            });
        }

        // Go to favorite location
        function goToFavorite(lat, lon) {
            map.setView([lat, lon], 13);
            L.marker([lat, lon]).addTo(map).bindPopup(`<b>${favorites.find(f => f.lat == lat && f.lon == lon).name}</b>`);
        }

        // Remove favorite
        function removeFavorite(index) {
            favorites.splice(index, 1);
            localStorage.setItem('favorites', JSON.stringify(favorites));
            updateFavoritesList();
        }

        // Clear all favorites
        function clearFavorites() {
            favorites = [];
            localStorage.setItem('favorites', JSON.stringify(favorites));
            updateFavoritesList();
        }

        // Map click to add marker
        map.on('click', function(e) {
            const marker = L.marker(e.latlng).addTo(map)
                .bindPopup(`<b>Custom Pin</b><br>Lat: ${e.latlng.lat.toFixed(4)}, Lon: ${e.latlng.lng.toFixed(4)}<br><button onclick="saveFavorite(${e.latlng.lat}, ${e.latlng.lng}, 'Custom Pin')">Save to Favorites</button>`);
            markers.push(marker);
        });
    </script>
</body>
</html>
