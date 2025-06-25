import { Component, OnInit } from '@angular/core';
import { OrderService, Order, PaginatedOrders } from '../../../core/services/order.service'; // Ajustez chemin
import { Observable, BehaviorSubject, switchMap, catchError, of } from 'rxjs';

@Component({
  selector: 'app-order-history',
  templateUrl: './order-history.component.html',
  styleUrls: ['./order-history.component.scss']
})
export class OrderHistoryComponent implements OnInit {
  private pageSubject = new BehaviorSubject<number>(1);
  page$ = this.pageSubject.asObservable();

  paginatedOrders$: Observable<PaginatedOrders | null>;

  isLoading: boolean = true;
  errorMessage: string | null = null;

  currentPage: number = 1;
  itemsPerPage: number = 10;
  totalItems: number = 0;
  totalPages: number = 0;

  constructor(private orderService: OrderService) {
    this.paginatedOrders$ = this.page$.pipe(
      switchMap(page => {
        this.isLoading = true;
        this.currentPage = page;
        return this.orderService.getOrders(page, this.itemsPerPage).pipe(
          catchError(err => {
            this.errorMessage = "Impossible de charger l'historique des commandes.";
            this.isLoading = false;
            console.error(err);
            return of(null);
          })
        );
      })
    );
  }

  ngOnInit(): void {
    this.paginatedOrders$.subscribe(response => {
      if (response) {
        this.totalItems = response.total;
        this.itemsPerPage = response.per_page;
        this.totalPages = response.last_page;
      }
      this.isLoading = false;
    });
    this.loadOrders();
  }

  loadOrders(page: number = 1): void {
    this.pageSubject.next(page);
  }

  onPageChange(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.loadOrders(page);
    }
  }

  get paginationControls(): (number | string)[] {
    const controls: (number | string)[] = [];
    const delta = 2;
    const left = this.currentPage - delta;
    const right = this.currentPage + delta + 1;
    let l: number | null = null;

    for (let i = 1; i <= this.totalPages; i++) {
      if (i === 1 || i === this.totalPages || (i >= left && i < right)) {
        controls.push(i);
        l = i;
      } else if (l !== null && i !== l + 1) {
        controls.push('...');
        l = null;
      }
    }
    return controls;
  }

  getStatusClass(status: string): string {
    const statusMap: { [key: string]: string } = {
      'en_attente': 'bg-yellow-100 text-yellow-800',
      'confirmee': 'bg-blue-100 text-blue-800',
      'en_preparation': 'bg-indigo-100 text-indigo-800',
      'en_livraison': 'bg-orange-100 text-orange-800',
      'livree': 'bg-green-100 text-green-800',
      'annulee': 'bg-red-100 text-red-800',
    };
    return `px-2 py-1 text-xs font-medium rounded-full inline-block ${statusMap[status] || 'bg-gray-100 text-gray-800'}`;
  }

  formatStatus(status: string): string {
    const statusText: { [key: string]: string } = {
        'en_attente': 'En attente',
        'confirmee': 'Confirmée',
        'en_preparation': 'En préparation',
        'en_livraison': 'En livraison',
        'livree': 'Livrée',
        'annulee': 'Annulée'
    };
    return statusText[status] || status;
  }
}
