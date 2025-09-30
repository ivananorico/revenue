// src/pages/Market/Market2/Market2.jsx
import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";

const API_BASE = "http://localhost/revenue/backend/Market/Market2";

export default function Market2() {
  const [renters, setRenters] = useState([]);
  const navigate = useNavigate();

  useEffect(() => {
    fetchRenters();
  }, []);

  const fetchRenters = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_renters.php`);
      if (!res.ok) throw new Error("Network response was not ok");

      const data = await res.json();

      if (!Array.isArray(data)) {
        console.error("Invalid data format:", data);
        setRenters([]);
        return;
      }

      setRenters(data);
    } catch (err) {
      console.error("Error fetching renters:", err);
      setRenters([]);
    }
  };

  const handleView = (id) => {
    navigate(`/Market/RenterDetails/${id}`);
  };

  return (
    <div>
      <h1>Market Renters</h1>
      <table border="1" cellPadding="5">
        <thead>
          <tr>
            <th>ID</th>
            <th>Renter Ref</th>
            <th>Full Name</th>
            <th>Contact Number</th>
            <th>Email</th>
            <th>Address</th>
            <th>Date Reserved</th>
            <th>Status</th>
            <th>Stall ID</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {renters.length > 0 ? (
            renters.map((renter) => (
              <tr key={renter.id}>
                <td>{renter.id}</td>
                <td>{renter.renter_ref}</td>
                <td>{renter.full_name}</td>
                <td>{renter.contact_number}</td>
                <td>{renter.email}</td>
                <td>{renter.address}</td>
                <td>{renter.date_reserved}</td>
                <td>{renter.status}</td>
                <td>{renter.stall_id}</td>
                <td>
                  <button onClick={() => handleView(renter.id)}>View</button>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="10" style={{ textAlign: "center" }}>
                No renters found
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
