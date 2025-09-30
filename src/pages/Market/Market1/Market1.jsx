import React, { useState, useRef } from "react";
import { useNavigate } from "react-router-dom";
import "./Market1.css";

export default function Market1() {
  const [mapId, setMapId] = useState(null);
  const [stallCount, setStallCount] = useState(0);
  const [stalls, setStalls] = useState([]);
  const [mapImage, setMapImage] = useState(null);
  const [isFinished, setIsFinished] = useState(false);

  const [modalOpen, setModalOpen] = useState(false);
  const [selectedStallIndex, setSelectedStallIndex] = useState(null);
  const [stallPrice, setStallPrice] = useState(0);
  const [modalPos, setModalPos] = useState({ x: 0, y: 0 });

  const marketMapRef = useRef(null);
  const navigate = useNavigate();
  const API_BASE = "http://localhost/revenue/backend/Market/Market1";

  // Upload map
  const handleUpload = async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      const res = await fetch(`${API_BASE}/upload_map.php`, {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (data.status === "success") {
        alert("Map uploaded!");
        setMapId(data.map_id);
        const baseUrl = "http://localhost/revenue";
        setMapImage(`${baseUrl}/${data.image_path}`);
        setStalls([]);
        setStallCount(0);
        setIsFinished(false);
      } else alert("Upload failed: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Upload error: " + err.message);
    }
  };

  // Add stall
  const addStall = () => {
    const newCount = stallCount + 1;
    setStallCount(newCount);
    const newStall = { 
      name: `Stall ${newCount}`, 
      pos_x: 50, 
      pos_y: 50, 
      status: "available", 
      price: 0 
    };
    setStalls([...stalls, newStall]);
  };

  // Save stalls
  const saveStalls = async () => {
    if (!mapId) return alert("Upload a map first.");
    try {
      const res = await fetch(`${API_BASE}/save_stalls.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ map_id: mapId, stalls }),
      });
      const data = await res.json();
      if (data.status === "success") {
        alert("Stalls saved!");
        navigate(`/Market1/view/${mapId}`);
      } else alert("Save failed: " + (data.message || "Unknown"));
    } catch (err) {
      alert("Save error: " + err.message);
    }
  };

  // Drag stalls
  const handleDrag = (e, index) => {
    const containerRect = marketMapRef.current.getBoundingClientRect();
    const x = e.clientX - containerRect.left - 31.5; // Half of stall width (63px/2)
    const y = e.clientY - containerRect.top - 29; // Half of stall height (58px/2)

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

  // Open price modal near the stall
  const openPriceModal = (index, e) => {
    e.preventDefault();
    const containerRect = marketMapRef.current.getBoundingClientRect();
    setSelectedStallIndex(index);
    setStallPrice(stalls[index].price);

    // Position modal near cursor but inside the container
    let x = e.clientX - containerRect.left;
    let y = e.clientY - containerRect.top;
    x = Math.min(containerRect.width - 150, x);
    y = Math.min(containerRect.height - 100, y);
    setModalPos({ x, y });

    setModalOpen(true);
  };

  // Save price
  const savePrice = () => {
    const updated = [...stalls];
    updated[selectedStallIndex].price = parseFloat(stallPrice) || 0;
    setStalls(updated);
    setModalOpen(false);
  };

  return (
    <div className="market-container">
      <h1>{isFinished ? "Finished Market Map" : "Market Map Creator"}</h1>

      {!isFinished && (
        <form onSubmit={handleUpload} encType="multipart/form-data" className="upload-form">
          <input type="text" name="mapName" placeholder="Map Name" required />
          <input type="file" name="mapImage" accept="image/*" required />
          <button type="submit">Upload Map</button>
        </form>
      )}

      <div
        id="marketMap"
        ref={marketMapRef}
        className="market-map"
        style={{ backgroundImage: mapImage ? `url('${mapImage}')` : "none" }}
      >
        {stalls.map((stall, index) => (
          <div
            key={index}
            className={`stall ${stall.status}`}
            style={{ left: stall.pos_x, top: stall.pos_y }}
            onMouseDown={isFinished ? null : (e) => handleMouseDown(e, index)}
            onContextMenu={(e) => openPriceModal(index, e)}
          >
            <div className="stall-content">
              <div className="stall-name">{stall.name}</div>
              <div className="stall-price">
                {stall.price > 0 ? `₱${stall.price}` : ""}
              </div>
            </div>
          </div>
        ))}

        {/* Price Modal */}
        {modalOpen && (
          <div
            className="price-modal"
            style={{ left: modalPos.x, top: modalPos.y }}
          >
            <h4>Set Stall Price</h4>
            <input
              type="number"
              value={stallPrice}
              onChange={(e) => setStallPrice(e.target.value)}
              step="0.01"
            />
            <div className="modal-buttons">
              <button onClick={savePrice}>Save</button>
              <button onClick={() => setModalOpen(false)}>Cancel</button>
            </div>
          </div>
        )}
      </div>

      {!isFinished && (
        <div className="controls">
          <button onClick={addStall} disabled={!mapId}>Add Stall</button>
          <button onClick={saveStalls} disabled={!mapId}>Save Stalls</button>
          <button onClick={() => navigate("/Market1/edit")}>
            View All Maps
          </button>
        </div>
      )}
    </div>
  );
}