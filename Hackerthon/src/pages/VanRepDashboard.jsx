import React, { useEffect, useState } from 'react';
import api from '../api/api.jsx';
import { useAuth } from '../context/AuthContext.jsx';

export default function VanDashboard() {
  const [stock, setStock] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const { user } = useAuth();

  useEffect(() => {
    const fetchStock = async () => {
      setLoading(true);
      setError('');
      try {
        // Adjust endpoint as per your backend API
        const response = await api.get(`/van-rep/${user.id}/stock`);
        setStock(response.data.stock);
      } catch (err) {
        setError('Failed to load van stock.');
      } finally {
        setLoading(false);
      }
    };
    if (user?.id) fetchStock();
  }, [user]);

  if (loading) return <div>Loading van stock...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div>
      <h1 className="text-xl font-bold mb-4">Van Stock</h1>
      <ul>
        {stock.map((item) => (
          <li key={item.product_id} className="mb-2">
            {item.product_name}: {item.quantity}
          </li>
        ))}
      </ul>
    </div>
  );
}