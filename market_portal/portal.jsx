import { useEffect, useState } from "react";

export default function MarketPortal() {
  const [maps, setMaps] = useState([]);
  const [mapId, setMapId] = useState(null);
  const [map, setMap] = useState(null);
  const [stalls, setStalls] = useState([]);
  const [selectedStall, setSelectedStall] = useState(null);

  // Load maps
  useEffect(() => {
    fetch("/api/get_maps.php")
      .then(res => res.json())
      .then(data => setMaps(data));
  }, []);

  // Load stalls when map changes
  useEffect(() => {
    if (!mapId) return;
    fetch(`/api/get_map.php?id=${mapId}`)
      .then(res => res.json())
      .then(data => {
        setMap(data.map);
        setStalls(data.stalls);
      });
  }, [mapId]);

  const reserveStall = async (stallId, formData) => {
    const res = await fetch("/api/reserve_stall.php", {
      method: "POST",
      body: formData,
    });
    const data = await res.json();
    if (data.status === "success") {
      // Update stalls locally instead of reloading
      setStalls(stalls.map(s => s.id === stallId ? { ...s, status: "reserved" } : s));
      setSelectedStall(null);
      alert("Stall reserved successfully!");
    } else {
      alert("Error: " + data.message);
    }
  };

  const deleteStall = async (stallId) => {
    const res = await fetch(`/api/delete_stall.php?id=${stallId}`, { method: "DELETE" });
    const data = await res.json();
    if (data.status === "success") {
      setStalls(stalls.filter(s => s.id !== stallId));
      alert("Stall deleted successfully!");
    } else {
      alert("Error: " + data.message);
    }
  };

  return (
    <div>
      <h1>Market Portal</h1>
      <select value={mapId || ""} onChange={e => setMapId(e.target.value)}>
        <option value="">-- Choose Map --</option>
        {maps.map(m => (
          <option key={m.id} value={m.id}>{m.name}</option>
        ))}
      </select>

      {map && (
        <div
          className="market-map"
          style={{
            backgroundImage: `url(${map.image_path})`,
            position: "relative",
            width: "800px",
            height: "600px",
          }}
        >
          {stalls.map(stall => (
            <div
              key={stall.id}
              className={`stall ${stall.status}`}
              style={{
                position: "absolute",
                left: `${stall.pos_x}px`,
                top: `${stall.pos_y}px`,
              }}
              onClick={() => setSelectedStall(stall)}
            >
              {stall.name} - ₱{stall.price}
            </div>
          ))}
        </div>
      )}

      {selectedStall && (
        <div>
          <h3>Reserve {selectedStall.name}</h3>
          <button onClick={() => deleteStall(selectedStall.id)}>Delete Stall</button>
          <form
            onSubmit={e => {
              e.preventDefault();
              const formData = new FormData(e.target);
              formData.append("stall_id", selectedStall.id);
              reserveStall(selectedStall.id, formData);
            }}
          >
            <input name="full_name" placeholder="Full Name" required />
            <input name="contact_number" placeholder="Contact" />
            <input name="email" type="email" placeholder="Email" />
            <textarea name="address" placeholder="Address"></textarea>
            <button type="submit">Reserve</button>
          </form>
        </div>
      )}
    </div>
  );
}
