// src/pages/Market/Market1/MarketEdit.jsx
import React, { useState, useEffect, useRef } from "react";
import "./MarketEdit.css";

const API_BASE = "http://localhost/revenue/backend/Market/Market1";

export default function MarketEdit() {
  const [maps, setMaps] = useState([]);
  const [search, setSearch] = useState("");
  const [currentMap, setCurrentMap] = useState(null);
  const [stalls, setStalls] = useState([]);
  const marketMapRef = useRef(null);

  useEffect(() => {
    fetchMaps();
  }, []);

  const fetchMaps = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_maps.php`);
      const data = await res.json();
      if (data.status === "success") setMaps(data.maps);
      else alert("Failed to fetch maps: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Error fetching maps: " + err.message);
    }
  };

  const handleDeleteMap = async (mapId) => {
    if (!window.confirm("Are you sure you want to delete this map?")) return;
    try {
      const res = await fetch(`${API_BASE}/delete_map.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ map_id: mapId }),
      });
      const data = await res.json();
      if (data.status === "success") {
        alert("Map deleted!");
        setCurrentMap(null);
        fetchMaps();
      } else alert("Delete failed: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Delete error: " + err.message);
    }
  };

  const handleView = async (mapId) => {
    try {
      const res = await fetch(`${API_BASE}/get_map.php?map_id=${mapId}`);
      const data = await res.json();
      if (data.status === "success") {
        if (!data.map.image_path.startsWith("http")) {
          data.map.image_path = `${API_BASE}/${data.map.image_path}`;
        }
        setCurrentMap(data.map);
        setStalls(data.map.stalls);
      } else alert("Failed to load map: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Error loading map: " + err.message);
    }
  };

  const addStall = () => {
    const newStall = {
      name: `Stall ${stalls.length + 1}`,
      pos_x: 50,
      pos_y: 50,
      status: "available",
    };
    setStalls([...stalls, newStall]);
  };

  const deleteStall = (index) => {
    const updated = [...stalls];
    updated.splice(index, 1);
    setStalls(updated);
  };

  const handleDrag = (e, index) => {
    const containerRect = marketMapRef.current.getBoundingClientRect();
    const x = e.clientX - containerRect.left - 50 / 2;
    const y = e.clientY - containerRect.top - 50 / 2;

    const updated = [...stalls];
    updated[index].pos_x = Math.max(0, Math.min(containerRect.width - 50, x));
    updated[index].pos_y = Math.max(0, Math.min(containerRect.height - 50, y));
    setStalls(updated);
  };

  const handleMouseDown = (e, index) => {
    e.preventDefault();
    const onMouseMove = (ev) => handleDrag(ev, index);
    const onMouseUp = () => {
      document.removeEventListener("mousemove", onMouseMove);
      document.removeEventListener("mouseup", onMouseUp);
    };
    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseup", onMouseUp);
  };

  const toggleStatus = (index) => {
    const updated = [...stalls];
    updated[index].status =
      updated[index].status === "available" ? "occupied" : "available";
    setStalls(updated);
  };

  const saveStalls = async () => {
    if (!currentMap) return alert("No map loaded");
    try {
      const res = await fetch(`${API_BASE}/save_stalls.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ map_id: currentMap.id, stalls }),
      });
      const data = await res.json();
      if (data.status === "success") alert("Stalls saved!");
      else alert("Save failed: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Save error: " + err.message);
    }
  };

  const filteredMaps = maps.filter((map) =>
    map.name.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="market-container">
      <h1>Market Dashboard</h1>

      {!currentMap && (
        <>
          <input
            type="text"
            placeholder="Search maps..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          {filteredMaps.length === 0 ? (
            <p>No maps found.</p>
          ) : (
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredMaps.map((map) => (
                  <tr key={map.id}>
                    <td>{map.id}</td>
                    <td>{map.name}</td>
                    <td>
                      <button onClick={() => handleView(map.id)}>View</button>
                      <button onClick={() => handleDeleteMap(map.id)}>
                        Delete
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </>
      )}

      {currentMap && (
        <>
          <h2>{currentMap.name}</h2>
          <div
            id="marketMap"
            ref={marketMapRef}
            style={{ backgroundImage: `url(${currentMap.image_path})` }}
          >
            {stalls.map((stall, index) => (
              <div
                key={index}
                className={`stall ${stall.status}`}
                style={{ left: stall.pos_x, top: stall.pos_y }}
                onMouseDown={(e) => handleMouseDown(e, index)}
                onContextMenu={(e) => {
                  e.preventDefault();
                  toggleStatus(index);
                }}
              >
                {stall.name}
                <button onClick={() => deleteStall(index)}> </button>
              </div>
            ))}
          </div>
          <div className="controls">
            <button onClick={addStall}>Add Stall</button>
            <button onClick={saveStalls}>Save Changes</button>
            <button onClick={() => setCurrentMap(null)}>Back to Maps</button>
          </div>
        </>
      )}
    </div>
  );
}
