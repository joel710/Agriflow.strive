import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { OrderService, Order } from '../../../core/services/order.service';
import { Observable, of } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Component({
  selector: 'app-order-success',
  templateUrl: './order-success.component.html',
  // styleUrls: ['./order-success.component.scss'] // Optionnel
})
export class OrderSuccessComponent implements OnInit {
  order$: Observable<Order | null> | undefined;
  isLoading: boolean = true;
  errorMessage: string | null = null;
  orderId: number | null = null;

  constructor(
    private route: ActivatedRoute,
    private orderService: OrderService
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.orderId = +idParam;
      this.order$ = this.orderService.getOrderById(this.orderId).pipe(
        catchError(err => {
          this.errorMessage = "Détails de la commande non trouvés ou erreur de chargement.";
          console.error(err);
          return of(null);
        })
      );
      this.order$.subscribe(() => this.isLoading = false);
    } else {
        this.errorMessage = "ID de commande manquant pour la page de succès.";
        this.isLoading = false;
    }
  }
}
