import React, { useState } from 'react';
import Navbar from '../components/Navbar.jsx';
import StockCard from '../components/StockCard.jsx';

// Sample stock data
const manufacturerStock = [
  { id: 1, product: 'Delmonte Pineapple Juice', quantity: 900 },
  { id: 2, product: 'Delmonte Mango Juice', quantity: 700 },
  { id: 3, product: 'Delmonte Orange Juice', quantity: 600 },
];
const distributorStock = [
  { id: 1, product: 'Delmonte Pineapple Juice', quantity: 120 },
  { id: 2, product: 'Delmonte Mango Juice', quantity: 80 },
  { id: 3, product: 'Delmonte Orange Juice', quantity: 60 },
];
const vanStock = [
  { id: 1, product: 'Delmonte Pineapple Juice', quantity: 10 },
  { id: 2, product: 'Delmonte Mango Juice', quantity: 8 },
  { id: 3, product: 'Delmonte Orange Juice', quantity: 5 },
];
const requisitions = [
  { id: 301, product: 'Delmonte Pineapple Juice', quantity: 30, status: 'pending', requester: 'Alex', date: '2025-08-22' },
  { id: 302, product: 'Delmonte Mango Juice', quantity: 20, status: 'approved', requester: 'Alex', date: '2025-08-21' },
  { id: 303, product: 'Delmonte Orange Juice', quantity: 10, status: 'rejected', requester: 'Alex', date: '2025-08-20' },
];

export default function ManagerDashboard() {
  const [activity] = useState([
    { id: 1, action: 'approved', product: 'Delmonte Mango Juice', quantity: 20, user: 'Rayyidh', date: '2025-08-21' },
    { id: 2, action: 'rejected', product: 'Delmonte Orange Juice', quantity: 10, user: 'Rayyidh', date: '2025-08-20' },
    { id: 3, action: 'approved', product: 'Delmonte Pineapple Juice', quantity: 30, user: 'Rayyidh', date: '2025-08-19' },
  ]);
  const [view, setView] = useState('manager');

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar role="manager" />
      <div className="p-6">
        <h2 className="text-2xl font-bold mb-4">Manager Dashboard</h2>
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
              <StockCard title="Manufacturer Stock" quantity={manufacturerStock.reduce((sum, s) => sum + s.quantity, 0)} icon={"🏭"} />
              <StockCard title="Distributor Stock" quantity={distributorStock.reduce((sum, s) => sum + s.quantity, 0)} icon={"🏢"} />
              <StockCard title="Van Stock" quantity={vanStock.reduce((sum, s) => sum + s.quantity, 0)} icon={"🚚"} />
              <StockCard title="Pending Requisitions" quantity={requisitions.filter(r => r.status === 'pending').length} icon={"⏳"} />
            </div>
            {/* Stock Distribution Table */}
            <div className="mb-8">
              <h3 className="text-lg font-semibold mb-2">Stock Distribution</h3>
              <table className="w-full bg-white rounded shadow text-sm">
                <thead>
                  <tr className="bg-gray-100">
                    <th className="p-2">Product</th>
                    <th className="p-2">Manufacturer</th>
                    <th className="p-2">Distributor</th>
                    <th className="p-2">Van</th>
                  </tr>
                </thead>
                <tbody>
                  {manufacturerStock.map((m, idx) => (
                    <tr key={m.id}>
                      <td className="p-2">{m.product}</td>
                      <td className="p-2">{m.quantity}</td>
                      <td className="p-2">{distributorStock[idx]?.quantity ?? '-'} </td>
                      <td className="p-2">{vanStock[idx]?.quantity ?? '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {/* Recent Activity Feed */}
            <div>
              <h3 className="text-lg font-semibold mb-2">Recent Activity</h3>
              <ul className="bg-white rounded shadow p-4 text-sm">
                {activity.length === 0 ? (
                  <li className="text-gray-400">No recent activity</li>
                ) : (
                  activity.map((a) => (
                    <li key={a.id} className="mb-2">
                      <span className={`font-bold capitalize ${a.action === 'approved' ? 'text-green-600' : 'text-red-600'}`}>{a.action}</span> {a.quantity} x {a.product} by {a.user} on {a.date}
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
            {/* Example: Show distributor stock */}
            {distributorStock.map((s) => (
              <StockCard key={s.id} title={s.product} quantity={s.quantity} icon={"🏢"} />
            ))}
          </div>
        )}

        {view === 'van-rep' && (
          <div className="bg-white rounded shadow p-6">
            <h3 className="text-lg font-semibold mb-4">Van Rep Dashboard (Manager View)</h3>
            {/* Example: Show van stock */}
            {vanStock.map((s) => (
              <StockCard key={s.id} title={s.product} quantity={s.quantity} icon={"🚚"} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
