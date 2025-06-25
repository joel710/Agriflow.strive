import { Component, OnInit } from '@angular/core';
import { CartService, CartState } from '../../../core/services/cart.service'; // Ajustez chemin
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

@Component({
  selector: 'app-cart-icon',
  templateUrl: './cart-icon.component.html',
  styleUrls: ['./cart-icon.component.scss']
})
export class CartIconComponent implements OnInit {
  cartItemCount$: Observable<number>;

  constructor(private cartService: CartService) {
    this.cartItemCount$ = this.cartService.cartState$.pipe(
      // map(cart => cart.items.reduce((acc, item) => acc + item.quantity, 0)) // Somme des quantités
      map(cart => cart.total_items) // Ou utiliser total_items si c'est le nombre de lignes
    );
  }

  ngOnInit(): void {}

}
