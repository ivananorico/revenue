import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import "./RenterDetails.css";

const API_BASE = "http://localhost/revenue/backend/Market/Market2";

export default function RenterDetails() {
  const { id } = useParams();
  const [renter, setRenter] = useState(null);
  const [monthlyRent, setMonthlyRent] = useState([]);
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);
        
        // Fetch renter info
        const resRenter = await fetch(`${API_BASE}/get_renter_details.php?id=${id}`);
        if (!resRenter.ok) throw new Error("Failed to fetch renter details");
        
        const renterData = await resRenter.json();
        console.log("Renter API Response:", renterData); // Debug log
        
        // Check if we have an error or no data
        if (renterData.error) {
          setError(renterData.error);
        } else if (Object.keys(renterData).length > 0 && !renterData.error) {
          setRenter(renterData);
          setFormData(renterData);
        } else {
          setError("No renter details found");
        }

        // Fetch monthly rent
        const resMonthly = await fetch(`${API_BASE}/get_monthly_rent.php?id=${id}`);
        if (resMonthly.ok) {
          const monthlyData = await resMonthly.json();
          setMonthlyRent(monthlyData);
        }
      } catch (err) {
        console.error("Error fetching data:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };
    
    if (id) {
      fetchData();
    }
  }, [id]);

  // Add loading state
  if (loading) {
    return <div className="loading">Loading renter details...</div>;
  }

  // Add error state
  if (error) {
    return <div className="error">Error: {error}</div>;
  }

  if (!renter) {
    return <div className="no-data">No renter details found.</div>;
  }

  // Rest of your component remains the same...
  const handleApprove = async () => {
    try {
      await fetch(`${API_BASE}/approve_renter.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: renter.user_id }),
      });
      setRenter({ ...renter, status: "active", stall_status: "occupied" });
    } catch (err) {
      console.error("Error approving renter:", err);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const handleSave = async () => {
    try {
      const payload = { ...formData, user_id: renter.user_id };
      const res = await fetch(`${API_BASE}/update_renter.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) {
        setRenter({ ...renter, ...formData });
        setIsEditing(false);
      } else {
        alert("Failed to update renter info: " + data.message);
      }
    } catch (err) {
      console.error("Error updating renter:", err);
    }
  };

  const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
      case "pending":
        return "status-pending";
      case "paid":
        return "status-paid";
      case "unpaid":
        return "status-overdue";
      default:
        return "";
    }
  };

  return (
    <div className="renter-details-container">
      <h1>Renter Details</h1>

      <div className="renter-info-section">
        <table className="renter-info-table">
          <tbody>
            <tr>
              <td>ID</td>
              <td>{renter.user_id}</td>
            </tr>
            <tr>
              <td>Reference</td>
              <td>
                {isEditing ? (
                  <input
                    name="renter_ref"
                    value={formData.renter_ref || ""}
                    onChange={handleInputChange}
                  />
                ) : (
                  renter.renter_ref
                )}
              </td>
            </tr>
            <tr>
              <td>Full Name</td>
              <td>
                {isEditing ? (
                  <input
                    name="full_name"
                    value={formData.full_name || ""}
                    onChange={handleInputChange}
                  />
                ) : (
                  renter.full_name
                )}
              </td>
            </tr>
            <tr>
              <td>Contact Number</td>
              <td>
                {isEditing ? (
                  <input
                    name="contact_number"
                    value={formData.contact_number || ""}
                    onChange={handleInputChange}
                  />
                ) : (
                  renter.contact_number
                )}
              </td>
            </tr>
            <tr>
              <td>Email</td>
              <td>
                {isEditing ? (
                  <input
                    name="email"
                    value={formData.email || ""}
                    onChange={handleInputChange}
                  />
                ) : (
                  renter.email
                )}
              </td>
            </tr>
            <tr>
              <td>Address</td>
              <td>
                {isEditing ? (
                  <input
                    name="address"
                    value={formData.address || ""}
                    onChange={handleInputChange}
                  />
                ) : (
                  renter.address
                )}
              </td>
            </tr>
            <tr>
              <td>Date Reserved</td>
              <td>{renter.date_reserved}</td>
            </tr>
            <tr>
              <td>Status</td>
              <td>{renter.status}</td>
            </tr>
            <tr>
              <td>Stall Name</td>
              <td>{renter.stall_name}</td>
            </tr>
            <tr>
              <td>Stall Width</td>
              <td>{renter.stall_width ? `${renter.stall_width} m` : "N/A"}</td>
            </tr>
            <tr>
              <td>Stall Length</td>
              <td>{renter.stall_length ? `${renter.stall_length} m` : "N/A"}</td>
            </tr>
            <tr>
              <td>Stall Height</td>
              <td>{renter.stall_height ? `${renter.stall_height} m` : "N/A"}</td>
            </tr>
            <tr>
              <td>Stall Status</td>
              <td>{renter.stall_status}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div className="action-buttons">
        {renter.status === "pending" && (
          <button className="btn-approve" onClick={handleApprove}>
            Approve Renter
          </button>
        )}
        {isEditing ? (
          <>
            <button className="btn-save" onClick={handleSave}>
              Save Changes
            </button>
            <button className="btn-cancel" onClick={() => setIsEditing(false)}>
              Cancel
            </button>
          </>
        ) : (
          <button className="btn-edit" onClick={() => setIsEditing(true)}>
            Edit Information
          </button>
        )}
      </div>

      <div className="monthly-rent-section">
        <h2>Monthly Rent Records</h2>
        <div className="rent-table-container">
          <table className="rent-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Amount Due</th>
                <th>Penalty</th>
                <th>Total Due</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Receipt No.</th>
              </tr>
            </thead>
            <tbody>
              {monthlyRent.length > 0 ? (
                monthlyRent.map((m) => (
                  <tr key={m.id}>
                    <td>{m.rent_month}</td>
                    <td>₱{m.amount_due}</td>
                    <td>₱{m.penalty}</td>
                    <td>₱{m.total_due}</td>
                    <td>
                      <span className={getStatusClass(m.status)}>{m.status}</span>
                    </td>
                    <td>{m.payment_date || "-"}</td>
                    <td>{m.receipt_no || "-"}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="7" className="no-data">
                    No monthly rent records found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}