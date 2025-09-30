// src/pages/Market/Market2/Market2.jsx
import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import "./Market2.css";

const API_BASE = "http://localhost/revenue/backend/Market/Market2";

export default function Market2() {
  const [renters, setRenters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    fetchRenters();
  }, []);

  const fetchRenters = async () => {
    try {
      setLoading(true);
      setError(null);
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
      setError(err.message);
      setRenters([]);
    } finally {
      setLoading(false);
    }
  };

  const handleView = (id) => {
    navigate(`/Market2/RenterDetails/${id}`);
  };

  const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
      case 'available':
        return 'status-available';
      case 'reserved':
        return 'status-reserved';
      case 'occupied':
        return 'status-occupied';
      default:
        return '';
    }
  };

  if (loading) {
    return (
      <div className="market2-container">
        <h1>Market Renters</h1>
        <div className="loading">Loading renters...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="market2-container">
        <h1>Market Renters</h1>
        <div className="error">
          Error loading renters: {error}
          <button onClick={fetchRenters}>Try Again</button>
        </div>
      </div>
    );
  }

  return (
    <div className="market2-container">
      <h1>Market Renters</h1>
      <div className="renters-table-container">
        <table className="renters-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Renter Ref</th>
              <th>Full Name</th>
              <th>Contact Number</th>
              <th>Email</th>
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
                  <td>{renter.date_reserved}</td>
                  <td>
                    <span className={getStatusClass(renter.status)}>
                      {renter.status}
                    </span>
                  </td>
                  <td>{renter.stall_id}</td>
                  <td>
                    <button 
                      className="btn-view"
                      onClick={() => handleView(renter.id)}
                    >
                      View Details
                    </button>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="10" className="no-data">
                  No renters found
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}