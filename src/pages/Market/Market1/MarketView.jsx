// src/pages/Market/MarketView.jsx
import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import "./MarketView.css"; // Make sure this file exists

export default function MarketView() {
  const { id } = useParams(); // map_id from URL
  const [mapImage, setMapImage] = useState(null);
  const [stalls, setStalls] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  // Backend URL
  const API_BASE = "http://localhost/revenue/backend/Market/Market1";

  useEffect(() => {
    async function fetchData() {
      try {
        const res = await fetch(`${API_BASE}/map_display.php?map_id=${id}`);
        if (!res.ok) throw new Error("Network error");

        const data = await res.json();
        console.log("MarketView response:", data);

        if (data.status === "success") {
          // Fix double uploads issue
          setMapImage(`${API_BASE}/${data.map.image_path}`);
          setStalls(data.stalls || []);
        } else {
          throw new Error(data.message || "Unknown error");
        }
      } catch (err) {
        console.error(err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }

    fetchData();
  }, [id]);

  if (loading) return <div>Loading market map...</div>;
  if (error) return <div style={{ color: "red" }}>Error: {error}</div>;

  return (
    <div className="market-container">
      <h1>{`Market Map: ${id}`}</h1>

      <div
        className="market-map"
        style={{
          backgroundImage: mapImage ? `url('${mapImage}')` : "none",
        }}
      >
        {stalls.map((stall) => (
          <div
            key={stall.id}
            className={`stall ${stall.status}`}
            style={{
              left: `${stall.pos_x}px`,
              top: `${stall.pos_y}px`,
            }}
          >
            {stall.name}
          </div>
        ))}
      </div>

      <div className="controls">
        <button onClick={() => navigate("/Market/Market1/Market1")}>
          Back to Market Dashboard
        </button>
      </div>
    </div>
  );
}
