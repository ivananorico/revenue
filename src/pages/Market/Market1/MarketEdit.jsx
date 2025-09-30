// src/pages/Market/Market1/MarketEdit.jsx
import React, { useState, useEffect, useRef } from "react";
import "./MarketEdit.css";

const API_BASE = "http://localhost/revenue/backend/Market/Market1";

export default function MarketEdit() {
  const [maps, setMaps] = useState([]);
  const [search, setSearch] = useState("");
  const [currentMap, setCurrentMap] = useState(null);
  const [stalls, setStalls] = useState([]);
  const [priceModal, setPriceModal] = useState({
    visible: false,
    x: 0,
    y: 0,
    index: null,
    price: "",
  });

  const marketMapRef = useRef(null);

  useEffect(() => {
    fetchMaps();
  }, []);

  const fetchMaps = async () => {
    try {
      const res = await fetch(`${API_BASE}/all_maps.php`);
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
        let imgPath = data.map.image_path;
        if (!imgPath.startsWith("http")) {
          imgPath = `http://localhost/revenue/${imgPath.replace(/^\/+/, "")}`;
        }
        setCurrentMap({ ...data.map, image_path: imgPath });
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
      price: "",
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
    const x = e.clientX - containerRect.left - 31.5; // Half of stall width
    const y = e.clientY - containerRect.top - 29; // Half of stall height

    const updated = [...stalls];
    updated[index].pos_x = Math.max(0, Math.min(containerRect.width - 63, x));
    updated[index].pos_y = Math.max(0, Math.min(containerRect.height - 58, y));
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
        <div className="maps-list">
          <input
            type="text"
            placeholder="Search maps..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="search-input"
          />
          {filteredMaps.length === 0 ? (
            <p>No maps found.</p>
          ) : (
            <table className="maps-table">
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
                      <button 
                        className="btn-view" 
                        onClick={() => handleView(map.id)}
                      >
                        View
                      </button>
                      <button 
                        className="btn-delete" 
                        onClick={() => handleDeleteMap(map.id)}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {currentMap && (
        <div className="map-editor">
          <h2>{currentMap.name}</h2>
          <div
            className="market-map"
            id="marketMap"
            ref={marketMapRef}
            style={{
              backgroundImage: currentMap.image_path
                ? `url('${currentMap.image_path}')`
                : "none",
            }}
          >
            {stalls.map((stall, index) => (
  <div
    key={index}
    className={`stall ${stall.status}`}
    style={{
      left: stall.pos_x,
      top: stall.pos_y,
    }}
    onMouseDown={(e) => handleMouseDown(e, index)}
    onContextMenu={(e) => {
  e.preventDefault();

  const marketRect = marketMapRef.current.getBoundingClientRect();
  const stallWidth = 63; // same as your CSS
  const stallHeight = 58;

  // Calculate position relative to the viewport
  let x = marketRect.left + stall.pos_x + stallWidth + 5; // 5px gap to the right
  let y = marketRect.top + stall.pos_y; // top aligned with stall

  // Optional: make sure modal doesn't go outside viewport
  const modalWidth = 150;
  const modalHeight = 80;
  x = Math.min(window.innerWidth - modalWidth, x);
  y = Math.min(window.innerHeight - modalHeight, y);

  setPriceModal({
    visible: true,
    x,
    y,
    index,
    price: stall.price || "",
  });
}}

  >
    <div className="stall-name">{stall.name}</div>
    {/* Remove price inside stall */}
    {/* <div className="stall-price">₱{stall.price}</div> */}
  </div>
))}

          </div>

          {/* Price input modal */}
          {priceModal.visible && (
            <div
              className="price-modal"
              style={{
                position: "fixed",
                top: priceModal.y,
                left: priceModal.x,
              }}
            >
              <label>
                Price:
                <input
                  type="number"
                  value={priceModal.price}
                  onChange={(e) =>
                    setPriceModal({ ...priceModal, price: e.target.value })
                  }
                />
              </label>
              <div className="price-modal-buttons">
                <button
                  className="btn-save"
                  onClick={() => {
                    const updated = [...stalls];
                    updated[priceModal.index].price = priceModal.price;
                    setStalls(updated);
                    setPriceModal({ ...priceModal, visible: false });
                  }}
                >
                  Save
                </button>
                <button
                  className="btn-cancel"
                  onClick={() =>
                    setPriceModal({ ...priceModal, visible: false })
                  }
                >
                  Cancel
                </button>
              </div>
            </div>
          )}

          <div className="controls">
            <button className="btn-add" onClick={addStall}>Add Stall</button>
            <button className="btn-save-changes" onClick={saveStalls}>Save Changes</button>
            <button className="btn-back" onClick={() => setCurrentMap(null)}>Back to Maps</button>
          </div>
        </div>
      )}
    </div>
  );
}