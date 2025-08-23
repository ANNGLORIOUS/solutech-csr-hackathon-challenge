import React, { useState } from 'react';
import Navbar from '../components/Navbar.jsx';
import StockCard from '../components/StockCard.jsx';

// Sample warehouse stock
const initialStock = [
  { id: 1, product: 'Delmonte Pineapple Juice', quantity: 120 },
  { id: 2, product: 'Delmonte Mango Juice', quantity: 80 },
  { id: 3, product: 'Delmonte Orange Juice', quantity: 60 },
];

// Sample requisition requests
const initialRequests = [
  {
    id: 101,
    product: 'Delmonte Pineapple Juice',
    quantity: 30,
    requester: 'Alex',
    status: 'pending',
    date: '2025-08-22',
  },
  {
    id: 102,
    product: 'Delmonte Mango Juice',
    quantity: 20,
    requester: 'Alex',
    status: 'pending',
    date: '2025-08-23',
  },
];

export default function DistributorDashboard() {
  const [stock, setStock] = useState(initialStock);
  const [requests, setRequests] = useState(initialRequests);
  const [toast, setToast] = useState(null);

  const handleAction = (reqId, action) => {
    const req = requests.find((r) => r.id === reqId);
    const available = stock.find(s => s.product === req.product)?.quantity ?? 0;
    if (action === 'approved' && req.quantity > available) {
      setToast({ type: 'error', msg: 'Cannot approve: requested quantity exceeds available stock.' });
      setTimeout(() => setToast(null), 2500);
      return;
    }
    setRequests((prev) =>
      prev.map((r) =>
        r.id === reqId
          ? { ...r, status: action }
          : r
      )
    );
    if (action === 'approved') {
      setStock((prev) =>
        prev.map((s) =>
          s.product === req.product
            ? { ...s, quantity: s.quantity - req.quantity }
            : s
        )
      );
      setToast({ type: 'success', msg: 'Request approved and stock updated.' });
    } else {
      setToast({ type: 'error', msg: 'Request rejected.' });
    }
    setTimeout(() => setToast(null), 2000);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="distributor" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4">Distributor Dashboard</h2>
        {toast && (
          <div className={`mb-4 px-4 py-2 rounded text-white ${toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'}`}>
            {toast.msg}
          </div>
        )}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {/* Pending Requests Table */}
          <div>
            <h3 className="text-lg font-semibold mb-2">Pending Requests</h3>
            <table className="w-full bg-white rounded shadow text-sm">
              <thead>
                <tr className="bg-gray-100">
                  <th className="p-2">Product</th>
                  <th className="p-2">Quantity</th>
                  <th className="p-2">Requester</th>
                  <th className="p-2">Date</th>
                  <th className="p-2">Action</th>
                </tr>
              </thead>
              <tbody>
                {requests.filter(r => r.status === 'pending').length === 0 ? (
                  <tr><td colSpan={5} className="p-2 text-center text-gray-400">No pending requests</td></tr>
                ) : (
                  requests.filter(r => r.status === 'pending').map((r) => (
                    <tr key={r.id}>
                      <td className="p-2">{r.product}</td>
                      <td className="p-2">{r.quantity}</td>
                      <td className="p-2">{r.requester}</td>
                      <td className="p-2">{r.date}</td>
                      <td className="p-2 flex gap-2">
                        <button
                          className="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600"
                          onClick={() => handleAction(r.id, 'approved')}
                          disabled={stock.find(s => s.product === r.product)?.quantity < r.quantity}
                        >Approve</button>
                        <button
                          className="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600"
                          onClick={() => handleAction(r.id, 'rejected')}
                        >Reject</button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          {/* Warehouse Stock Cards */}
          <div>
            <h3 className="text-lg font-semibold mb-2">Warehouse Stock</h3>
            {stock.map((s) => (
              <StockCard key={s.id} title={s.product} quantity={s.quantity} icon={"📦"} />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
