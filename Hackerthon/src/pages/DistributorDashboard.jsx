import React, { useEffect, useState } from 'react';
import Navbar from '../Components/Navbar.jsx';
import api from '../api/api.jsx';
import { useAuth } from '../context/AuthContext.jsx';

export default function DistributorDashboard() {
  const { user } = useAuth();
  const [stock, setStock] = useState([]);
  const [pendingRequisitions, setPendingRequisitions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [toast, setToast] = useState(null);
  const [error, setError] = useState('');

  const fetchDashboardData = async () => {
    if (!user?.id) return;
    setLoading(true);
    setError('');
    try {
      const [stockRes, reqRes] = await Promise.all([
        api.get('/distributor/stock'),
        api.get('/distributor/pending-requisitions'),
      ]);
      setStock(stockRes.data.stock || []);
      setPendingRequisitions(reqRes.data.pending_requisitions || []);
    } catch (err) {
      setError(
        err.response?.status === 500
          ? 'Server error occurred. Please try again.'
          : err.response?.data?.message || 'Failed to load dashboard data.'
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
    // eslint-disable-next-line
  }, [user]);

  const handleApprove = async (id) => {
    try {
      await api.put(`/distributor/requisitions/${id}/approve`);
      setPendingRequisitions(prev => prev.filter(r => r.id !== id));
      setToast({ type: 'success', msg: 'Requisition approved.' });
    } catch (err) {
      setToast({ type: 'error', msg: err.response?.data?.message || 'Failed to approve.' });
    } finally {
      setTimeout(() => setToast(null), 2000);
    }
  };

  const handleReject = async (id) => {
    try {
      await api.put(`/distributor/requisitions/${id}/reject`);
      setPendingRequisitions(prev => prev.filter(r => r.id !== id));
      setToast({ type: 'success', msg: 'Requisition rejected.' });
    } catch (err) {
      setToast({ type: 'error', msg: err.response?.data?.message || 'Failed to reject.' });
    } finally {
      setTimeout(() => setToast(null), 2000);
    }
  };

  if (loading) return <div className="p-6 text-center text-gray-700">Loading dashboard...</div>;

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="distributor" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4 text-gray-800">Distributor Dashboard</h2>
        {toast && (
          <div className={`mb-4 px-4 py-2 rounded text-white ${toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'}`}>
            {toast.msg}
          </div>
        )}
        {error && (
          <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p>{error}</p>
            <button
              onClick={fetchDashboardData}
              className="mt-2 bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition"
            >
              Retry
            </button>
          </div>
        )}
        {/* Distributor Stock */}
        <div className="mb-8">
          <h3 className="text-lg font-semibold mb-2 text-gray-700">My Stock</h3>
          {stock.length === 0 ? (
            <div className="text-gray-500">No stock available.</div>
          ) : (
            <table className="w-full bg-white rounded shadow text-sm">
              <thead>
                <tr className="bg-gray-100">
                  <th className="p-2 text-left">Product</th>
                  <th className="p-2 text-left">Quantity</th>
                </tr>
              </thead>
              <tbody>
                {stock.map(s => (
                  <tr key={s.product_id} className="border-b">
                    <td className="p-2">{s.product_name}</td>
                    <td className="p-2">{s.quantity}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
        {/* Pending Requisitions */}
        <div>
          <h3 className="text-lg font-semibold mb-2 text-gray-700">Pending Requisitions</h3>
          {pendingRequisitions.length === 0 ? (
            <div className="text-gray-500">No pending requisitions.</div>
          ) : (
            <table className="w-full bg-white rounded shadow text-sm">
              <thead>
                <tr className="bg-gray-100">
                  <th className="p-2 text-left">Van Rep</th>
                  <th className="p-2 text-left">Product</th>
                  <th className="p-2 text-left">Quantity</th>
                  <th className="p-2 text-left">Date Requested</th>
                  <th className="p-2 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                {pendingRequisitions.map(r => (
                  <tr key={r.id} className="border-b">
                    <td className="p-2">{r.van_rep.name}</td>
                    <td className="p-2">{r.product.name}</td>
                    <td className="p-2">{r.quantity}</td>
                    <td className="p-2">{r.date_requested}</td>
                    <td className="p-2 space-x-2">
                      <button
                        className="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 transition"
                        onClick={() => handleApprove(r.id)}
                      >
                        Approve
                      </button>
                      <button
                        className="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 transition"
                        onClick={() => handleReject(r.id)}
                      >
                        Reject
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}