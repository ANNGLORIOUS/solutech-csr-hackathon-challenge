import React, { useState } from 'react';
import Navbar from '../components/Navbar.jsx';
import StockCard from '../components/StockCard.jsx';

// Sample van stock
const initialVanStock = [
  { id: 1, product: 'Delmonte Pineapple Juice', quantity: 10 },
  { id: 2, product: 'Delmonte Mango Juice', quantity: 8 },
  { id: 3, product: 'Delmonte Orange Juice', quantity: 5 },
];

// Sample requests
const initialRequests = [
  {
    id: 201,
    product: 'Delmonte Pineapple Juice',
    quantity: 5,
    status: 'approved',
    date: '2025-08-22',
  },
  {
    id: 202,
    product: 'Delmonte Mango Juice',
    quantity: 3,
    status: 'pending',
    date: '2025-08-23',
  },
];

const VAN_CAPACITY = 30;

export default function VanRepDashboard() {
  const [vanStock, setVanStock] = useState(initialVanStock);
  const [requests, setRequests] = useState(initialRequests);
  const [form, setForm] = useState({ product: '', quantity: '' });
  const [toast, setToast] = useState(null);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const product = form.product;
    const quantity = Number(form.quantity);
    const today = new Date().toISOString().slice(0, 10);
    // Validation
    if (!product || !quantity) {
      setToast({ type: 'error', msg: 'Please select product and quantity.' });
      return;
    }
    if (requests.some(r => r.product === product && r.date === today)) {
      setToast({ type: 'error', msg: 'Duplicate request for this product today.' });
      return;
    }
    const currentTotal = vanStock.reduce((sum, s) => sum + s.quantity, 0);
    if (currentTotal + quantity > VAN_CAPACITY) {
      setToast({ type: 'error', msg: 'Van capacity exceeded.' });
      return;
    }
    // Add request
    setRequests([
      ...requests,
      {
        id: Math.floor(Math.random() * 10000),
        product,
        quantity,
        status: 'pending',
        date: today,
      },
    ]);
    setToast({ type: 'success', msg: 'Requisition submitted.' });
    setForm({ product: '', quantity: '' });
    setTimeout(() => setToast(null), 2000);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="van-rep" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4">Van Rep Dashboard</h2>
        {toast && toast.type === 'success' && (
          <div className="mb-4 px-4 py-2 rounded text-white bg-green-500">
            {toast.msg}
          </div>
        )}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {/* Van Stock Cards */}
          <div>
            <h3 className="text-lg font-semibold mb-2">Current Van Stock</h3>
            {vanStock.map((s) => (
              <StockCard key={s.id} title={s.product} quantity={s.quantity} icon={"🚚"} />
            ))}
          </div>
          {/* New Requisition Form */}
          <div>
            <h3 className="text-lg font-semibold mb-2">New Requisition</h3>
            <form onSubmit={handleSubmit} className="space-y-4 bg-white p-4 rounded shadow">
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
                  {vanStock.map((s) => (
                    <option key={s.id} value={s.product}>{s.product}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block mb-1 font-medium">Quantity</label>
                <input
                  type="number"
                  name="quantity"
                  min="1"
                  max="30"
                  value={form.quantity}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
                  required
                />
              </div>
              <button
                type="submit"
                className="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
              >
                Submit Request
              </button>
              {toast && toast.type === 'error' && toast.msg === 'Van capacity exceeded.' && (
                <div className="mt-2 px-4 py-2 rounded text-white bg-red-500 text-center">
                  {toast.msg}
                </div>
              )}
            </form>
          </div>
        </div>
        {/* My Requests Table */}
        <div className="mt-8">
          <h3 className="text-lg font-semibold mb-2">My Requests</h3>
          <table className="w-full bg-white rounded shadow text-sm">
            <thead>
              <tr className="bg-gray-100">
                <th className="p-2">Product</th>
                <th className="p-2">Quantity</th>
                <th className="p-2">Date</th>
                <th className="p-2">Status</th>
              </tr>
            </thead>
            <tbody>
              {requests.length === 0 ? (
                <tr><td colSpan={4} className="p-2 text-center text-gray-400">No requests yet</td></tr>
              ) : (
                requests.map((r) => (
                  <tr key={r.id}>
                    <td className="p-2">{r.product}</td>
                    <td className="p-2">{r.quantity}</td>
                    <td className="p-2">{r.date}</td>
                    <td className={`p-2 capitalize ${r.status === 'approved' ? 'text-green-600' : r.status === 'pending' ? 'text-yellow-600' : 'text-red-600'}`}>{r.status}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
