// src/pages/Market/Market2/RenterDetails.jsx
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";

const API_BASE = "http://localhost/revenue/backend/Market/Market2";

export default function RenterDetails() {
  const { id } = useParams();
  const [renter, setRenter] = useState(null);

  useEffect(() => {
    const fetchRenter = async () => {
      try {
        const res = await fetch(`${API_BASE}/get_renters.php?id=${id}`);
        const data = await res.json();
        if (data.length > 0) setRenter(data[0]);
        else setRenter(null);
      } catch (err) {
        console.error("Error fetching renter:", err);
        setRenter(null);
      }
    };
    fetchRenter();
  }, [id]);

  const handleApprove = async () => {
  try {
    await fetch(`${API_BASE}/approve_renter.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: renter.id }),
    });

    // Update local state
    setRenter({
      ...renter,
      status: "active",
      stall_status: "occupied",
    });
  } catch (err) {
    console.error("Error approving renter:", err);
  }
};


  if (!renter) return <div>No renter details found.</div>;

  return (
    <div>
      <h1>Renter Details</h1>
      <table border="1" cellPadding="5">
        <tbody>
          <tr><td>ID</td><td>{renter.id}</td></tr>
          <tr><td>Reference</td><td>{renter.renter_ref}</td></tr>
          <tr><td>Full Name</td><td>{renter.full_name}</td></tr>
          <tr><td>Contact Number</td><td>{renter.contact_number}</td></tr>
          <tr><td>Email</td><td>{renter.email}</td></tr>
          <tr><td>Address</td><td>{renter.address}</td></tr>
          <tr><td>Date Reserved</td><td>{renter.date_reserved}</td></tr>
          <tr><td>Status</td><td>{renter.status}</td></tr>
          <tr><td>Stall Name</td><td>{renter.stall_name}</td></tr>
          <tr><td>Stall Status</td><td>{renter.stall_status}</td></tr>
        </tbody>
      </table>

      {/* Approve button only shows if status is pending */}
      {renter.status === "pending" && (
        <button onClick={handleApprove} style={{ marginTop: "10px" }}>
          Approve Renter
        </button>
      )}
    </div>
  );
}
