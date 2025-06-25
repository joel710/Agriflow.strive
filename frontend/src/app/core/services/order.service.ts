import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CartItem } from './cart.service'; // Réutiliser CartItem ou définir OrderItem si différent
import { PaginatedResponse } from '../models/paginated-response.model'; // Interface générique pour la pagination

// Interfaces spécifiques aux commandes
export interface OrderItemDetail {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: string; // ou number
  total_price: string; // ou number
  product: { // Informations produit au moment de la commande
    id: number;
    name: string;
    image_url?: string;
    producer?: {
        id: number;
        farm_name: string;
    }
  };
}

export interface CustomerInfo {
    id: number; // Customer profile ID
    user: { // Basic user info
        id: number;
        email: string;
        phone?: string;
    }
}

export interface DeliveryInfo {
    id: number;
    status: string;
    tracking_number?: string;
    estimated_delivery_date?: string;
    actual_delivery_date?: string;
    // ... autres champs de Delivery
}

export interface InvoiceInfo {
    id: number;
    invoice_number: string;
    status: string;
    amount: string; // ou number
    payment_date?: string;
    pdf_url?: string;
}

export interface Order {
  id: number;
  customer_id: number;
  total_amount: string; // ou number
  status: string;
  payment_status: string;
  payment_method?: string;
  delivery_address: string;
  delivery_notes?: string;
  created_at: string;
  updated_at: string;
  items: OrderItemDetail[];
  customer?: CustomerInfo; // Pour admin/producer view
  delivery?: DeliveryInfo;
  invoice?: InvoiceInfo;
}

export interface CreateOrderPayload {
  cart_items: { product_id: number; quantity: number }[];
  delivery_address: string;
  delivery_notes?: string;
  payment_method_slug: string; // e.g., 'paygate', 'tmoney', 'wallet'
}

export type PaginatedOrders = PaginatedResponse<Order>;


@Injectable({
  providedIn: 'root'
})
export class OrderService {
  private apiUrl = `${environment.apiUrl}/orders`;

  constructor(private http: HttpClient) { }

  createOrder(payload: CreateOrderPayload): Observable<{message: string, order: Order}> {
    return this.http.post<{message: string, order: Order}>(this.apiUrl, payload);
  }

  // Pour client, producteur, ou admin (l'API filtre selon le rôle)
  getOrders(page: number = 1, perPage: number = 10): Observable<PaginatedOrders> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());
    return this.http.get<PaginatedOrders>(this.apiUrl, { params });
  }

  getOrderById(orderId: number): Observable<Order> {
    return this.http.get<Order>(`${this.apiUrl}/${orderId}`);
  }

  // Pour producteur ou admin
  updateOrderStatus(orderId: number, status: string): Observable<Order> {
    return this.http.put<Order>(`${this.apiUrl}/${orderId}/status`, { status });
  }
}
