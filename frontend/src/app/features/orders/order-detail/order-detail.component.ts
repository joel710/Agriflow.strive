import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { OrderService, Order } from '../../../core/services/order.service'; // Ajustez chemin
import { Observable, catchError, of } from 'rxjs';

@Component({
  selector: 'app-order-detail',
  templateUrl: './order-detail.component.html',
  styleUrls: ['./order-detail.component.scss']
})
export class OrderDetailComponent implements OnInit {
  order$: Observable<Order | null> | undefined;
  isLoading: boolean = true;
  errorMessage: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private orderService: OrderService,
    private router: Router
  ) {}

  ngOnInit(): void {
    const orderIdString = this.route.snapshot.paramMap.get('id');
    if (orderIdString) {
      const orderId = +orderIdString; // Convert to number
      this.order$ = this.orderService.getOrderById(orderId).pipe(
        catchError(err => {
          this.errorMessage = "Commande non trouvée ou vous n'avez pas l'autorisation de la voir.";
          this.isLoading = false;
          console.error(err);
          if (err.status === 404 || err.status === 403) {
            // Optionnel: rediriger si non trouvé/non autorisé
            // this.router.navigate(['/mes-commandes']);
          }
          return of(null);
        })
      );
      this.order$.subscribe(() => this.isLoading = false);
    } else {
      this.errorMessage = "ID de commande manquant.";
      this.isLoading = false;
      this.router.navigate(['/mes-commandes']); // Redirect if no ID
    }
  }

  // Copier depuis OrderHistoryComponent ou créer un service/pipe partagé
  getStatusClass(status: string): string {
    const statusMap: { [key: string]: string } = {
      'en_attente': 'bg-yellow-100 text-yellow-800',
      'confirmee': 'bg-blue-100 text-blue-800',
      'en_preparation': 'bg-indigo-100 text-indigo-800',
      'en_livraison': 'bg-orange-100 text-orange-800',
      'livree': 'bg-green-100 text-green-800',
      'annulee': 'bg-red-100 text-red-800',
    };
    return `px-3 py-1 text-sm font-semibold rounded-full inline-block ${statusMap[status] || 'bg-gray-100 text-gray-800'}`;
  }

  formatStatus(status: string): string {
    const statusText: { [key: string]: string } = {
        'en_attente': 'En attente de Paiement',
        'confirmee': 'Confirmée',
        'en_preparation': 'En préparation',
        'en_livraison': 'En cours de Livraison',
        'livree': 'Livrée',
        'annulee': 'Annulée'
    };
    return statusText[status] || status;
  }

  getPaymentStatusClass(status: string): string {
    const classMap: { [key: string]: string } = {
        'payee': 'text-green-600',
        'en_attente': 'text-yellow-600',
        'echec': 'text-red-600',
        'remboursee': 'text-blue-600'
    };
    return classMap[status] || 'text-gray-600';
  }
}
