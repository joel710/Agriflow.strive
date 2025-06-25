import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, catchError, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Product } from './product.service'; // Assurez-vous que Product est bien défini

// Interface pour un article du panier détaillé (ce que l'API /cart retourne)
export interface CartItem {
  product_id: number;
  name: string;
  price: number;
  quantity: number;
  unit: string;
  image_url?: string;
  item_total: number;
  producer_name?: string;
  stock_quantity?: number;
  is_available?: boolean;
}

// Interface pour l'état global du panier
export interface CartState {
  items: CartItem[];
  total_items: number; // Nombre total d'articles distincts ou somme des quantités
  total_amount: number;
  isLoading?: boolean; // Optionnel: pour gérer les états de chargement
}

@Injectable({
  providedIn: 'root'
})
export class CartService {
  private apiUrl = `${environment.apiUrl}/cart`;
  private cartStateSubject = new BehaviorSubject<CartState>({ items: [], total_items: 0, total_amount: 0, isLoading: true });
  public cartState$ = this.cartStateSubject.asObservable();

  constructor(private http: HttpClient) {
    this.loadInitialCart(); // Charger le panier au démarrage du service
  }

  private updateCartState(cartData: CartState | null) {
    if (cartData) {
      this.cartStateSubject.next({...cartData, isLoading: false});
    } else {
      this.cartStateSubject.next({ items: [], total_items: 0, total_amount: 0, isLoading: false });
    }
  }

  public get currentCartValue(): CartState {
    return this.cartStateSubject.value;
  }

  loadInitialCart(): void {
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    this.http.get<CartState>(this.apiUrl).pipe(
      tap(cartData => this.updateCartState(cartData)),
      catchError(err => {
        console.error("Erreur de chargement du panier initial:", err);
        this.updateCartState(null); // Réinitialiser le panier en cas d'erreur
        return of(null); // ou throwError si vous voulez que le composant gère l'erreur
      })
    ).subscribe();
  }

  addItem(productId: number, quantity: number = 1): Observable<CartState> {
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    return this.http.post<CartState>(`${this.apiUrl}/items`, { product_id: productId, quantity }).pipe(
      tap(cartData => this.updateCartState(cartData)),
      catchError(this.handleError.bind(this))
    );
  }

  incrementItem(productId: number): Observable<CartState> {
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    return this.http.post<CartState>(`${this.apiUrl}/items/${productId}/increment`, {}).pipe(
      tap(cartData => this.updateCartState(cartData)),
      catchError(this.handleError.bind(this))
    );
  }

  decrementItem(productId: number): Observable<CartState> {
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    return this.http.post<CartState>(`${this.apiUrl}/items/${productId}/decrement`, {}).pipe(
      tap(cartData => this.updateCartState(cartData)),
      catchError(this.handleError.bind(this))
    );
  }

  removeItem(productId: number): Observable<CartState> {
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    return this.http.delete<CartState>(`${this.apiUrl}/items/${productId}`).pipe(
      tap(cartData => this.updateCartState(cartData)),
      catchError(this.handleError.bind(this))
    );
  }

  clearCart(): Observable<CartState> { // L'API retourne le panier vide
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: true });
    return this.http.delete<CartState>(this.apiUrl).pipe(
      tap(cartData => this.updateCartState(cartData)), // API retourne un panier vidé
      catchError(this.handleError.bind(this))
    );
  }

  // Helper pour recalculer le nombre total de pièces dans le panier (si total_items de l'API est le nombre de lignes)
  get totalQuantityOfItems(): number {
    return this.currentCartValue.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  private handleError(error: any): Observable<never> {
    console.error('Erreur API du panier:', error);
    // Mettre à jour l'état pour indiquer qu'il n'y a pas de chargement et potentiellement afficher une erreur
    this.cartStateSubject.next({ ...this.currentCartValue, isLoading: false });
    // Afficher un message d'erreur à l'utilisateur via un service de notification ou autre.
    // Pour l'instant, on propage l'erreur pour que le composant puisse la gérer.
    return throwError(() => new Error(error.error?.message || 'Erreur lors de la mise à jour du panier.'));
  }
}
