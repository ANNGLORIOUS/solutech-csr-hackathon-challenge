import React, { useEffect, useState } from 'react';
import Navbar from '../Components/Navbar.jsx';
import StockCard from '../Components/StockCard.jsx';
import api from '../api/api.jsx';
import { useAuth } from '../context/AuthContext.jsx';

export default function VanRepDashboard() {
  const { user } = useAuth();
  const [vanStock, setVanStock] = useState([]);
  const [products, setProducts] = useState([]);
  const [requests, setRequests] = useState([]);
  const [capacity, setCapacity] = useState({ total: 0, used: 0, available: 0 });
  const [form, setForm] = useState({ product: '', quantity: '', distributor_id: '2' });
  const [toast, setToast] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!user?.id) return;
    fetchData();
    // eslint-disable-next-line
  }, [user]);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [stockRes, productsRes, reqRes, capRes] = await Promise.all([
        api.get('/van-rep/stock'),
        api.get('/van-rep/products'),
        api.get('/van-rep/requisitions'),
        api.get('/van-rep/capacity')
      ]);
      setVanStock(stockRes.data.van_stock || []);
      setProducts(productsRes.data.products || []);
      setRequests(reqRes.data.requisitions || []);
      const cap = capRes.data.capacity_info;
      setCapacity({
        total: cap.total_capacity || 100,
        used: cap.used_capacity || 0,
        available: cap.available_capacity || 100
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load dashboard data.');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    const quantity = Number(form.quantity);
    if (!form.product || !quantity || !form.distributor_id) {
      setToast({ type: 'error', msg: 'Please fill all fields.' });
      return;
    }
    if (quantity > capacity.available) {
      setToast({ type: 'error', msg: 'Van capacity exceeded.' });
      return;
    }
    try {
      const res = await api.post('/van-rep/requisitions', {
        product_id: form.product,
        quantity,
        distributor_id: form.distributor_id
      });
      setRequests(prev => [...prev, res.data.requisition]);
      setCapacity(prev => ({
        ...prev,
        used: prev.used + quantity,
        available: prev.available - quantity
      }));
      setToast({ type: 'success', msg: 'Requisition submitted successfully.' });
      setForm({ product: '', quantity: '', distributor_id: '2' });
    } catch (err) {
      setToast({ type: 'error', msg: err.response?.data?.message || 'Failed to submit requisition.' });
    }
    setTimeout(() => setToast(null), 3000);
  };

  if (loading) return <div className="p-6">Loading van dashboard...</div>;

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="van-rep" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4">Van Representative Dashboard</h2>
        {toast && (
          <div className={`mb-4 px-4 py-2 rounded text-white ${toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'}`}>
            {toast.msg}
          </div>
        )}
        {error && (
          <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {error}
            <button onClick={fetchData} className="ml-2 px-3 py-1 bg-red-600 text-white rounded">Retry</button>
          </div>
        )}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Van Stock */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Current Van Stock</h3>
            <div className="space-y-2">
              {vanStock.length === 0 ? (
                <div className="text-gray-500 bg-white p-4 rounded">No stock in van.</div>
              ) : (
                vanStock.map(s => (
                  <StockCard key={s.product_id} title={s.product_name} quantity={s.quantity} icon="🚚" />
                ))
              )}
            </div>
            <div className="mt-4 p-3 bg-white rounded shadow">
              <div className="text-sm text-gray-600">Van Capacity</div>
              <div className="text-lg font-semibold">
                {capacity.used}/{capacity.total} used ({capacity.available} available)
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div 
                  className="bg-blue-600 h-2 rounded-full" 
                  style={{ width: `${(capacity.used / capacity.total) * 100}%` }}
                ></div>
              </div>
            </div>
          </div>
          {/* New Requisition Form */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Request Stock</h3>
            <form onSubmit={handleSubmit} className="space-y-4 bg-white p-6 rounded shadow">
              <div>
                <label className="block mb-1 font-medium">Distributor ID</label>
                <input
                  type="number"
                  name="distributor_id"
                  value={form.distributor_id}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
                  required
                />
              </div>
              <div>
                <label className="block mb-1 font-medium">Product</label>
                <select
                  name="product"
                  value={form.product}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
                  required
                >
                  <option value="">Select product</option>
                  {products.map(p => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block mb-1 font-medium">Quantity</label>
                <input
                  type="number"
                  name="quantity"
                  min="1"
                  max={capacity.available}
                  value={form.quantity}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
                  required
                />
              </div>
              <button type="submit" className="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Submit Request
              </button>
            </form>
          </div>
        </div>
        {/* My Requests */}
        <div className="mt-8">
          <h3 className="text-lg font-semibold mb-4">My Requests</h3>
          <div className="bg-white rounded shadow overflow-hidden">
            <table className="w-full text-sm">
              <thead className="bg-gray-100">
                <tr>
                  <th className="p-3 text-left">Product</th>
                  <th className="p-3 text-left">Quantity</th>
                  <th className="p-3 text-left">Date</th>
                  <th className="p-3 text-left">Status</th>
                </tr>
              </thead>
              <tbody>
                {requests.length === 0 ? (
                  <tr><td colSpan={4} className="p-4 text-center text-gray-500">No requests yet</td></tr>
                ) : (
                  requests.map(r => (
                    <tr key={r.id} className="border-t">
                      <td className="p-3">{r.product?.name || 'Unknown Product'}</td>
                      <td className="p-3">{r.quantity}</td>
                      <td className="p-3">{r.date_requested}</td>
                      <td className={`p-3 capitalize font-medium ${
                        r.status === 'approved' ? 'text-green-600' : 
                        r.status === 'pending' ? 'text-yellow-600' : 'text-red-600'
                      }`}>
                        {r.status}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}