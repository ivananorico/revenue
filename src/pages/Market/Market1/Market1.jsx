import React, { useState, useRef, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import "./Market1.css";

export default function Market1() {
  const [mapId, setMapId] = useState(null);
  const [stallCount, setStallCount] = useState(0);
  const [stalls, setStalls] = useState([]);
  const [mapImage, setMapImage] = useState(null);
  const [isFinished, setIsFinished] = useState(false);

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
        setMapImage(data.image_path);
        setStalls([]);
        setStallCount(0);
        setIsFinished(false);
      } else {
        alert("Upload failed: " + (data.message || "Unknown"));
      }
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
    };
    setStalls([...stalls, newStall]);
  };

  // Save stalls → redirect to view page
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
        navigate(`/Market/view/${mapId}`); // redirect to MarketView
      } else {
        alert("Save failed: " + (data.message || "Unknown"));
      }
    } catch (err) {
      alert("Save error: " + err.message);
    }
  };

  // Drag stalls
  const handleDrag = (e, index) => {
    const containerRect = marketMapRef.current.getBoundingClientRect();
    const x = e.clientX - containerRect.left - 25;
    const y = e.clientY - containerRect.top - 25;

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



  return (
    <div className="market-container">
      <h1>{isFinished ? "Finished Market Map" : "Market Map Creator"}</h1>

      {/* Upload map form */}
      {!isFinished && (
        <form onSubmit={handleUpload} encType="multipart/form-data" className="upload-form">
          <input type="text" name="mapName" placeholder="Map Name" required />
          <input type="file" name="mapImage" accept="image/*" required />
          <button type="submit">Upload Map</button>
        </form>
      )}

      {/* Market map area */}
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
            style={{
              left: stall.pos_x,
              top: stall.pos_y,
            }}
            onMouseDown={isFinished ? null : (e) => handleMouseDown(e, index)}
            onContextMenu={isFinished ? null : (e) => { e.preventDefault(); changeStatus(index); }}
          >
            {stall.name}
          </div>
        ))}
      </div>

      {/* Controls */}
      {!isFinished && (
        <div className="controls">
          <button onClick={addStall} disabled={!mapId}>Add Stall</button>
          <button onClick={saveStalls} disabled={!mapId}>Save Stalls</button>
        </div>
      )}
    </div>
  );
}
