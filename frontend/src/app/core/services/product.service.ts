import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Product } from '../models/product.model'; // Interface/classe Product à définir

// Définition de l'interface Product (peut être dans un fichier séparé product.model.ts)
// (Assumant que ProducerInfo est défini ailleurs ou simplifié ici)
export interface ProducerInfo {
  id: number;
  farm_name: string;
  farm_photo_url?: string;
}
export interface Product {
  id: number;
  producer_id: number;
  name: string;
  description?: string;
  price: string; // L'API Laravel retourne des décimaux sous forme de string, convertir si besoin
  unit: string;
  stock_quantity: number;
  image_url?: string;
  is_bio: boolean;
  is_available: boolean;
  created_at: string;
  updated_at: string;
  producer?: ProducerInfo; // Eager-loaded producer info
}

export interface PaginatedProducts {
  current_page: number;
  data: Product[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}


@Injectable({
  providedIn: 'root'
})
export class ProductService {
  private apiUrl = `${environment.apiUrl}/products`;

  constructor(private http: HttpClient) { }

  getProducts(filters: any = {}, page: number = 1, perPage: number = 15): Observable<PaginatedProducts> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (filters.producer_id) {
      params = params.set('producer_id', filters.producer_id);
    }
    if (filters.is_available !== undefined) {
      params = params.set('is_available', filters.is_available.toString());
    }
    if (filters.is_bio !== undefined) {
      params = params.set('is_bio', filters.is_bio.toString());
    }
    if (filters.search) {
      params = params.set('search', filters.search);
    }
    // Ajoutez d'autres filtres ici

    return this.http.get<PaginatedProducts>(this.apiUrl, { params });
  }

  getProductById(id: number): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/${id}`);
  }

  createProduct(productData: FormData): Observable<Product> {
    // FormData est utilisé pour inclure le fichier image
    return this.http.post<Product>(this.apiUrl, productData);
  }

  updateProduct(id: number, productData: FormData): Observable<Product> {
    // Utiliser POST avec FormData pour la mise à jour si des fichiers sont impliqués
    // Laravel gère _method='PUT' dans FormData, mais POST direct est plus simple ici.
    // L'API backend a été configurée pour accepter POST pour les mises à jour de produit.
    return this.http.post<Product>(`${this.apiUrl}/${id}`, productData);
  }

  deleteProduct(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
