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
  const modalRef = useRef(null);
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
      price: 0,
      height: 0,
      length: 0,
      width: 0,
    };
    setStalls([...stalls, newStall]);
  };

  // Delete stall
  const deleteStall = (index) => {
    if (window.confirm(`Are you sure you want to delete ${stalls[index].name}?`)) {
      const updatedStalls = stalls.filter((_, i) => i !== index);
      setStalls(updatedStalls);
      setStallCount(updatedStalls.length);
    }
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
    const x = e.clientX - containerRect.left - 31.5;
    const y = e.clientY - containerRect.top - 29;

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

  // Open modal with better positioning
  const openPriceModal = (index, e) => {
    e.preventDefault();
    const containerRect = marketMapRef.current.getBoundingClientRect();
    setSelectedStallIndex(index);
    setStallPrice(stalls[index].price);

    // Get click position relative to viewport
    const viewportX = e.clientX;
    const viewportY = e.clientY;

    // Modal dimensions (approximate - you can adjust these)
    const modalWidth = 300;
    const modalHeight = 400;

    // Calculate position to keep modal within viewport
    let x = viewportX;
    let y = viewportY;

    // Check if modal would go beyond right edge of viewport
    if (x + modalWidth > window.innerWidth) {
      x = window.innerWidth - modalWidth - 10; // 10px padding from edge
    }

    // Check if modal would go beyond bottom edge of viewport
    if (y + modalHeight > window.innerHeight) {
      y = window.innerHeight - modalHeight - 10; // 10px padding from edge
    }

    // Ensure modal doesn't go off left or top edges
    x = Math.max(10, x);
    y = Math.max(10, y);

    setModalPos({ x, y });
    setModalOpen(true);
  };

  // Close modal when clicking outside
  const handleBackdropClick = (e) => {
    if (modalRef.current && !modalRef.current.contains(e.target)) {
      setModalOpen(false);
    }
  };

  return (
    <div className="market-container">
      <h1>{isFinished ? "Finished Market Map" : "Market Map Creator"}</h1>

      {!isFinished && (
        <form
          onSubmit={handleUpload}
          encType="multipart/form-data"
          className="upload-form"
        >
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
              <div className="stall-size">
                {stall.length}m × {stall.width}m × {stall.height}m
              </div>
            </div>
            
            {/* Delete Button - appears on hover */}
            {!isFinished && (
              <button
                className="delete-stall-btn"
                onClick={(e) => {
                  e.stopPropagation();
                  deleteStall(index);
                }}
                title="Delete stall"
              >
                ×
              </button>
            )}
          </div>
        ))}
      </div>

      {/* Modal Backdrop */}
      {modalOpen && (
        <div className="modal-backdrop" onClick={handleBackdropClick}>
          {/* Stall Details Modal */}
          <div
            ref={modalRef}
            className="price-modal"
            style={{ 
              left: `${modalPos.x}px`, 
              top: `${modalPos.y}px`,
              position: 'fixed'
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <h4>Set Stall Details</h4>

            <label>Price (₱)</label>
            <input
              type="number"
              value={stalls[selectedStallIndex]?.price || 0}
              onChange={(e) => {
                const updated = [...stalls];
                updated[selectedStallIndex].price =
                  parseFloat(e.target.value) || 0;
                setStalls(updated);
                setStallPrice(e.target.value);
              }}
              step="0.01"
            />

            <label>Height (m)</label>
            <input
              type="number"
              value={stalls[selectedStallIndex]?.height || 0}
              onChange={(e) => {
                const updated = [...stalls];
                updated[selectedStallIndex].height =
                  parseFloat(e.target.value) || 0;
                setStalls(updated);
              }}
              step="0.01"
            />

            <label>Length (m)</label>
            <input
              type="number"
              value={stalls[selectedStallIndex]?.length || 0}
              onChange={(e) => {
                const updated = [...stalls];
                updated[selectedStallIndex].length =
                  parseFloat(e.target.value) || 0;
                setStalls(updated);
              }}
              step="0.01"
            />

            <label>Width (m)</label>
            <input
              type="number"
              value={stalls[selectedStallIndex]?.width || 0}
              onChange={(e) => {
                const updated = [...stalls];
                updated[selectedStallIndex].width =
                  parseFloat(e.target.value) || 0;
                setStalls(updated);
              }}
              step="0.01"
            />

            <div className="modal-buttons">
              <button onClick={() => setModalOpen(false)}>Save</button>
              <button onClick={() => setModalOpen(false)}>Cancel</button>
            </div>
          </div>
        </div>
      )}

      {!isFinished && (
        <div className="controls">
          <button onClick={addStall} disabled={!mapId}>
            Add Stall
          </button>
          <button onClick={saveStalls} disabled={!mapId}>
            Save Stalls
          </button>
          <button onClick={() => navigate("/Market1/edit")}>
            View All Maps
          </button>
        </div>
      )}
    </div>
  );
}