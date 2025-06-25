import { Component, OnInit } from '@angular/core';
import { ProductService, Product, PaginatedProducts } from '../../../core/services/product.service'; // Ajustez chemin
import { Observable, BehaviorSubject, switchMap, catchError, of } from 'rxjs';
import { CommonModule } from '@angular/common'; // Import CommonModule
import { RouterModule } from '@angular/router'; // Import RouterModule for routerLink

@Component({
  selector: 'app-product-list',
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.scss'],
  // standalone: true, // Si vous décidez d'utiliser des standalone components
  // imports: [CommonModule, RouterModule] // Nécessaire pour standalone
})
export class ProductListComponent implements OnInit {
  // products$: Observable<PaginatedProducts | null> | undefined;

  private pageSubject = new BehaviorSubject<number>(1);
  page$ = this.pageSubject.asObservable();

  paginatedProducts$: Observable<PaginatedProducts | null>;

  // TODO: Ajouter des filtres (BehaviorSubjects pour les filtres)
  // private categoryFilterSubject = new BehaviorSubject<string | null>(null);
  // private bioFilterSubject = new BehaviorSubject<boolean | null>(null);

  isLoading: boolean = true;
  errorMessage: string | null = null;

  currentPage: number = 1;
  itemsPerPage: number = 12; // Ou ce que l'API retourne par défaut
  totalItems: number = 0;
  totalPages: number = 0;


  constructor(private productService: ProductService) {
    this.paginatedProducts$ = this.page$.pipe(
      switchMap(page => {
        this.isLoading = true;
        this.currentPage = page;
        // TODO: Passer les filtres actuels au service
        const currentFilters = {};
        return this.productService.getProducts(currentFilters, page, this.itemsPerPage).pipe(
          catchError(err => {
            this.errorMessage = "Impossible de charger les produits.";
            this.isLoading = false;
            console.error(err);
            return of(null); // Retourner null ou un objet PaginatedProducts vide en cas d'erreur
          })
        );
      })
    );
  }

  ngOnInit(): void {
    this.paginatedProducts$.subscribe(response => {
      if (response) {
        this.totalItems = response.total;
        this.itemsPerPage = response.per_page;
        this.totalPages = response.last_page;
        // this.currentPage = response.current_page; // déjà mis à jour par pageSubject
      }
      this.isLoading = false;
    });
    this.loadProducts(); // Load initial products
  }

  loadProducts(page: number = 1): void {
    this.pageSubject.next(page);
  }

  onPageChange(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.loadProducts(page);
    }
  }

  // Helper pour générer les numéros de page pour la pagination
  get paginationControls(): (number | string)[] {
    const controls: (number | string)[] = [];
    const delta = 2; // Nombre de pages de chaque côté de la page actuelle
    const left = this.currentPage - delta;
    const right = this.currentPage + delta + 1;
    let l: number | null = null;

    for (let i = 1; i <= this.totalPages; i++) {
      if (i === 1 || i === this.totalPages || (i >= left && i < right)) {
        controls.push(i);
        l = i;
      } else if (l !== null && i !== l + 1) {
        controls.push('...');
        l = null; // Pour éviter les "..." consécutifs
      }
    }
    return controls;
  }

  // TODO: Méthodes pour gérer les changements de filtres
  // onCategoryChange(category: string | null) { this.categoryFilterSubject.next(category); this.loadProducts(); }
  // onBioChange(isBio: boolean | null) { this.bioFilterSubject.next(isBio); this.loadProducts(); }
}
