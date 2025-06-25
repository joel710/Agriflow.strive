import { Component, OnInit } from '@angular/core';
import { WalletService, WalletData, WalletTransaction } from '../../../core/services/wallet.service'; // Ajustez chemin
import { Observable, BehaviorSubject, switchMap, catchError, of } from 'rxjs';
import { PaginatedResponse } from '../../../core/models/paginated-response.model';

@Component({
  selector: 'app-wallet-view',
  templateUrl: './wallet-view.component.html',
  styleUrls: ['./wallet-view.component.scss']
})
export class WalletViewComponent implements OnInit {
  // walletData$: Observable<WalletData | null> | undefined;

  private pageSubject = new BehaviorSubject<number>(1);
  // page$ = this.pageSubject.asObservable(); // Si la pagination des transactions est gérée par le backend sur cet endpoint

  walletData: WalletData | null = null; // Pour stocker les données résolues

  isLoading: boolean = true;
  errorMessage: string | null = null;

  // Pour la pagination des transactions si elle est séparée ou si on la gère côté client à partir d'un set plus grand
  currentPage: number = 1;
  itemsPerPage: number = 15; // Doit correspondre à l'API
  totalItems: number = 0;
  totalPages: number = 0;


  constructor(private walletService: WalletService) {}

  ngOnInit(): void {
    this.loadWalletData();
    // Si la pagination des transactions est gérée par le backend via des appels distincts :
    // this.walletData$ = this.page$.pipe(
    //   switchMap(page => {
    //     this.isLoading = true;
    //     this.currentPage = page;
    //     return this.walletService.getWalletData(page).pipe( // Assurez-vous que getWalletData prend la page
    //       catchError(err => { /* ... */ return of(null); })
    //     );
    //   })
    // );
    // this.walletData$.subscribe(data => {
    //   if (data) { this.setupPagination(data.transactions); }
    //   this.isLoading = false;
    // });
  }

  loadWalletData(page: number = 1): void {
    this.isLoading = true;
    this.errorMessage = null;
    this.currentPage = page; // Si la pagination est gérée par le service getWalletData

    // Pour l'instant, l'API /wallet retourne la première page de transactions.
    // Une vraie pagination des transactions nécessiterait un endpoint /wallet/transactions?page=X
    // ou que /wallet accepte un paramètre de page pour les transactions.
    this.walletService.getWalletData(/* pass page here if service supports it */).subscribe({
      next: (data) => {
        this.walletData = data;
        if (data && data.transactions) {
            this.setupPagination(data.transactions);
        }
        this.isLoading = false;
      },
      error: (err) => {
        this.errorMessage = "Impossible de charger les informations du portefeuille.";
        this.isLoading = false;
        console.error(err);
      }
    });
  }

  private setupPagination(paginatedTransactions: PaginatedResponse<WalletTransaction>): void {
    this.totalItems = paginatedTransactions.total;
    this.itemsPerPage = paginatedTransactions.per_page;
    this.totalPages = paginatedTransactions.last_page;
    this.currentPage = paginatedTransactions.current_page;
  }

  onPageChange(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      // Pour l'instant, cette pagination ne re-fetchera pas si l'API /wallet ne prend pas de page pour les transactions.
      // Elle ne ferait que changer l'affichage si on avait toutes les transactions côté client.
      // Il faudrait appeler this.loadWalletData(page) si l'API supporte la pagination des transactions.
      console.warn("La pagination des transactions n'est pas entièrement implémentée pour re-fetcher depuis l'API sur cet exemple.");
      // Si vous implémentez la pagination des transactions côté serveur sur GET /wallet?page=X ou GET /wallet/transactions?page=X
      // this.loadWalletData(page);
    }
  }

  get paginationControls(): (number | string)[] {
    const controls: (number | string)[] = [];
    if (!this.totalPages || this.totalPages <=1) return [];
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

  getTransactionTypeClass(type: 'credit' | 'debit'): string {
    return type === 'credit' ? 'text-green-600' : 'text-red-600';
  }
}
