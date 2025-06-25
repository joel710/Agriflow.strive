import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { PaginatedResponse } from '../models/paginated-response.model'; // Assurez-vous que cela existe

export interface WalletTransaction {
  id: number;
  wallet_id: number;
  type: 'credit' | 'debit';
  amount: string; // ou number
  description?: string;
  created_at: string;
  // related_type?: string; // Si polymorphique
  // related_id?: number;   // Si polymorphique
}

export interface Wallet {
  id: number;
  user_id: number;
  balance: string; // ou number
  currency: string;
  created_at: string;
  updated_at: string;
}

export interface WalletData {
  wallet: Wallet;
  transactions: PaginatedResponse<WalletTransaction>; // Transactions paginées
}

@Injectable({
  providedIn: 'root'
})
export class WalletService {
  private apiUrl = `${environment.apiUrl}/wallet`;

  constructor(private http: HttpClient) { }

  getWalletData(): Observable<WalletData> {
    // L'API retourne le portefeuille et les transactions paginées dans un seul objet.
    // Si vous voulez charger plus de transactions (pour la pagination des transactions),
    // vous aurez besoin d'un paramètre de page ici et l'API devra le supporter.
    // Exemple: getWalletData(page: number = 1): Observable<WalletData>
    // let params = new HttpParams().set('page', page.toString());
    // return this.http.get<WalletData>(this.apiUrl, { params });
    return this.http.get<WalletData>(this.apiUrl);
  }
}
