import { Component, OnInit, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { CartService, CartState, CartItem } from '../../../core/services/cart.service';
import { AuthService, User } from '../../../core/services/auth.service';
import { OrderService, CreateOrderPayload, Order } from '../../../core/services/order.service';
import { Subscription, Observable, combineLatest } from 'rxjs';
import { tap, switchMap, catchError, map, startWith } from 'rxjs/operators';

@Component({
  selector: 'app-checkout-view',
  templateUrl: './checkout-view.component.html',
  styleUrls: ['./checkout-view.component.scss']
})
export class CheckoutViewComponent implements OnInit, OnDestroy {
  checkoutForm!: FormGroup;
  cartState$: Observable<CartState>;
  currentUser$: Observable<User | null>;

  private subscriptions = new Subscription();

  isLoading: boolean = false;
  errorMessage: string | null = null;
  orderPlaced: Order | null = null;

  // Exemples de méthodes de paiement (pourraient venir d'une config ou API)
  paymentMethods = [
    { slug: 'paygate', name: 'PayGate (Carte Bancaire)' },
    { slug: 'tmoney', name: 'TMoney' },
    { slug: 'moov', name: 'Moov Money' },
    { slug: 'wallet', name: 'Mon Portefeuille Agriflow' }
  ];

  constructor(
    private fb: FormBuilder,
    private cartService: CartService,
    private authService: AuthService,
    private orderService: OrderService,
    private router: Router
  ) {
    this.cartState$ = this.cartService.cartState$;
    this.currentUser$ = this.authService.currentUser$;
  }

  ngOnInit(): void {
    this.checkoutForm = this.fb.group({
      delivery_address: ['', Validators.required],
      delivery_notes: [''],
      payment_method_slug: [this.paymentMethods[0].slug, Validators.required] // Default au premier
    });

    // Pré-remplir l'adresse si l'utilisateur est connecté et a un profil client
    this.subscriptions.add(
      this.currentUser$.subscribe(user => {
        if (user && user.customer && user.customer.delivery_address) {
          this.checkoutForm.patchValue({ delivery_address: user.customer.delivery_address });
        }
      })
    );

    // Vérifier si le panier est vide, si oui, rediriger
     this.subscriptions.add(
      this.cartState$.subscribe(cart => {
        if (!cart.isLoading && cart.items.length === 0 && !this.orderPlaced) {
          this.router.navigate(['/marche']); // ou '/panier'
        }
      })
    );
  }

  ngOnDestroy(): void {
    this.subscriptions.unsubscribe();
  }

  get totalQuantityOfItemsInCart(): number {
    return this.cartService.totalQuantityOfItems;
  }

  onSubmit(): void {
    if (this.checkoutForm.invalid) {
      this.errorMessage = "Veuillez vérifier les informations de livraison et de paiement.";
      Object.values(this.checkoutForm.controls).forEach(control => control.markAsTouched());
      return;
    }

    const cartValue = this.cartService.currentCartValue;
    if (!cartValue || cartValue.items.length === 0) {
      this.errorMessage = "Votre panier est vide.";
      return;
    }

    this.isLoading = true;
    this.errorMessage = null;

    const formValues = this.checkoutForm.value;
    const payload: CreateOrderPayload = {
      cart_items: cartValue.items.map(item => ({ product_id: item.product_id, quantity: item.quantity })),
      delivery_address: formValues.delivery_address,
      delivery_notes: formValues.delivery_notes,
      payment_method_slug: formValues.payment_method_slug
    };

    this.orderService.createOrder(payload).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.orderPlaced = response.order;
        this.cartService.clearCart().subscribe(); // Vider le panier après la commande

        // Redirection vers une page de confirmation de commande ou de paiement
        // Si paiement direct (ex: wallet), on peut aller à une page de succès.
        // Si paiement externe, l'API aurait dû retourner une URL de redirection.
        // Pour l'instant, on simule une page de succès.
        this.router.navigate(['/commande-succes', response.order.id]);
      },
      error: (error) => {
        this.isLoading = false;
        this.errorMessage = error.error?.message || 'Erreur lors de la création de la commande.';
        if (error.error?.error_code === 'INSUFFICIENT_STOCK') {
            // On pourrait recharger le panier pour refléter les stocks mis à jour si l'API le permet
            this.cartService.loadInitialCart();
        }
        console.error('Order creation error:', error);
      }
    });
  }
}
