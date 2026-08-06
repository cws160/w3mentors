const API_URL = import.meta.env.VITE_API_URL || '/api/v1';

export async function openOrderInvoice(orderId: number, subOrderId?: number): Promise<void> {
  if (!orderId || orderId < 1) {
    window.alert('Invalid order.');
    return;
  }

  const popup = window.open('about:blank', '_blank');
  if (!popup) {
    window.alert('Please allow pop-ups to view the invoice.');
    return;
  }

  popup.document.title = 'Invoice';
  popup.document.body.innerHTML =
    '<p style="font-family:sans-serif;padding:1rem">Loading invoice…</p>';

  const params = new URLSearchParams({ t: String(Date.now()) });
  if (subOrderId && subOrderId > 0) {
    params.set('sub_order_id', String(subOrderId));
  }
  const token = localStorage.getItem('admin_token');

  try {
    const response = await fetch(`${API_URL}/admin/orders/${orderId}/invoice?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    if (!response.ok) {
      throw new Error('Failed to load invoice');
    }
    const html = await response.text();
    popup.document.open();
    popup.document.write(html);
    popup.document.close();
  } catch {
    popup.close();
    window.alert('Failed to load invoice. Please try again.');
  }
}
