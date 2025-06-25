import { Component, OnInit } from '@angular/core';
import { CartService, CartState, CartItem } from '../../../core/services/cart.service'; // Ajustez chemin
import { Observable } from 'rxjs';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-cart-view',
  templateUrl: './cart-view.component.html',
  styleUrls: ['./cart-view.component.scss'],
  // standalone: true, // Si standalone
  // imports: [CommonModule, RouterModule] // Si standalone
})
export class CartViewComponent implements OnInit {
  cartState$: Observable<CartState>;
  generalErrorMessage: string | null = null;

  constructor(private cartService: CartService) {
    this.cartState$ = this.cartService.cartState$;
  }

  ngOnInit(): void {
    // Le panier est chargé initialement par le service
    // On peut s'abonner ici pour gérer les erreurs globales si besoin
    this.cartState$.subscribe({
        error: (err) => {
            // Ce ne sera pas trigger ici car le service gère l'erreur et retourne un état vide
            // Mais utile si le service propageait l'erreur directement.
            this.generalErrorMessage = "Une erreur est survenue avec le panier.";
        }
    })
  }

  increment(item: CartItem): void {
    this.generalErrorMessage = null;
    this.cartService.incrementItem(item.product_id).subscribe({
        error: err => this.generalErrorMessage = err.message || "Erreur lors de la mise à jour de la quantité."
    });
  }

  decrement(item: CartItem): void {
    this.generalErrorMessage = null;
    this.cartService.decrementItem(item.product_id).subscribe({
        error: err => this.generalErrorMessage = err.message || "Erreur lors de la mise à jour de la quantité."
    });
  }

  removeItem(item: CartItem): void {
    this.generalErrorMessage = null;
    if (confirm(`Êtes-vous sûr de vouloir retirer "${item.name}" du panier ?`)) {
      this.cartService.removeItem(item.product_id).subscribe({
        error: err => this.generalErrorMessage = err.message || "Erreur lors de la suppression de l'article."
      });
    }
  }

  clearCart(): void {
    this.generalErrorMessage = null;
    if (confirm("Êtes-vous sûr de vouloir vider tout le panier ?")) {
      this.cartService.clearCart().subscribe({
        error: err => this.generalErrorMessage = err.message || "Erreur lors du vidage du panier."
      });
    }
  }

  // Pour obtenir le nombre total de pièces
  get totalQuantityOfItemsInCart(): number {
    return this.cartService.totalQuantityOfItems;
  }
}
