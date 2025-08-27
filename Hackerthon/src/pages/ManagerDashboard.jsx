import React, { useEffect, useState } from 'react';
import Navbar from '../Components/Navbar.jsx';
import StockCard from '../Components/StockCard.jsx';
import api from '../api/api.jsx';
import { useAuth } from '../context/AuthContext.jsx';

export default function ManagerDashboard() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [stockOverview, setStockOverview] = useState([]);
  const [pendingRequisitions, setPendingRequisitions] = useState([]);
  const [stockMovements, setStockMovements] = useState([]);
  const [view, setView] = useState('manager'); // manager, distributor, van-rep

  useEffect(() => {
    if (!user || user.role !== 'manager') return;
    const fetchDashboard = async () => {
      setLoading(true);
      try {
        const [stockRes, pendingRes, movementsRes] = await Promise.all([
          api.get('/manager/dashboard/stock-overview'),
          api.get('/manager/dashboard/pending-requisitions'),
          api.get('/manager/stock-movements'),
        ]);
        setStockOverview(stockRes.data); 
        setPendingRequisitions(pendingRes.data);
        setStockMovements(movementsRes.data);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to load manager data.');
      } finally {
        setLoading(false);
      }
    };
    fetchDashboard();
    // eslint-disable-next-line
  }, [user]);

  if (!user || user.role !== 'manager') {
    return <div className="p-6 text-red-500">You are not authorized to view this page.</div>;
  }
  if (loading) return <div className="p-6 text-gray-700">Loading dashboard...</div>;
  if (error) return (
    <div className="p-6 text-red-500">
      {error}
      <button className="ml-2 px-3 py-1 bg-red-600 text-white rounded" onClick={() => window.location.reload()}>Retry</button>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="manager" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4">Manager Dashboard</h2>
        {/* View Switch */}
        <div className="mb-6 flex gap-4">
          <button
            className={`px-4 py-2 rounded font-semibold border ${view === 'manager' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600'}`}
            onClick={() => setView('manager')}
          >Manager View</button>
          <button
            className={`px-4 py-2 rounded font-semibold border ${view === 'distributor' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600'}`}
            onClick={() => setView('distributor')}
          >Distributor View</button>
          <button
            className={`px-4 py-2 rounded font-semibold border ${view === 'van-rep' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600'}`}
            onClick={() => setView('van-rep')}
          >Van Rep View</button>
        </div>
        {view === 'manager' && (
          <>
            {/* Stock Overview Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
              {stockOverview.map((s, idx) => (
                <StockCard
                  key={idx}
                  title={`${s.type} Stock`}
                  quantity={s.total}
                  icon={s.type === 'Manufacturer' ? '🏭' : s.type === 'Distributor' ? '🏢' : '🚚'}
                />
              ))}
              <StockCard
                title="Pending Requisitions"
                quantity={pendingRequisitions.filter(r => r.status === 'pending').length}
                icon="⏳"
              />
            </div>
            {/* Pending Requisitions Table */}
            <div className="mb-8">
              <h3 className="text-lg font-semibold mb-2">Pending Requisitions</h3>
              {pendingRequisitions.length === 0 ? (
                <div>No pending requisitions.</div>
              ) : (
                <table className="w-full bg-white rounded shadow text-sm">
                  <thead>
                    <tr className="bg-gray-100">
                      <th className="p-2">Requester</th>
                      <th className="p-2">Product</th>
                      <th className="p-2">Quantity</th>
                      <th className="p-2">Date</th>
                      <th className="p-2">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {pendingRequisitions.map(r => (
                      <tr key={r.id}>
                        <td className="p-2">{r.requester}</td>
                        <td className="p-2">{r.product}</td>
                        <td className="p-2">{r.quantity}</td>
                        <td className="p-2">{r.date}</td>
                        <td className={`p-2 font-semibold ${r.status === 'approved' ? 'text-green-600' : r.status === 'rejected' ? 'text-red-600' : 'text-yellow-600'}`}>
                          {r.status}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
            {/* Recent Stock Movements */}
            <div>
              <h3 className="text-lg font-semibold mb-2">Recent Stock Movements</h3>
              <ul className="bg-white rounded shadow p-4 text-sm">
                {stockMovements.length === 0 ? (
                  <li className="text-gray-400">No recent activity</li>
                ) : (
                  stockMovements.map((a) => (
                    <li key={a.id} className="mb-2">
                      <span className={`font-bold capitalize ${a.action === 'approved' ? 'text-green-600' : a.action === 'rejected' ? 'text-red-600' : 'text-yellow-600'}`}>
                        {a.action}
                      </span> {a.quantity} x {a.product} by {a.user} on {a.date}
                    </li>
                  ))
                )}
              </ul>
            </div>
          </>
        )}
        {view === 'distributor' && (
          <div className="bg-white rounded shadow p-6">
            <h3 className="text-lg font-semibold mb-4">Distributor Dashboard (Manager View)</h3>
            {/* You can fetch and display distributor stock here */}
          </div>
        )}
        {view === 'van-rep' && (
          <div className="bg-white rounded shadow p-6">
            <h3 className="text-lg font-semibold mb-4">Van Rep Dashboard (Manager View)</h3>
            {/* You can fetch and display van stock here */}
          </div>
        )}
      </div>
    </div>
  );
}