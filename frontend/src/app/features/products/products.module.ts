import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common'; // Important pour *ngIf, *ngFor, etc.
import { RouterModule, Routes } from '@angular/router';
import { ProductListComponent } from './product-list/product-list.component';
// Importez ProductDetailComponent ici quand il sera créé

const productRoutes: Routes = [
  { path: 'marche', component: ProductListComponent },
  // { path: 'produit/:id', component: ProductDetailComponent }, // Route pour le détail produit
];

@NgModule({
  declarations: [
    ProductListComponent,
    // ProductDetailComponent
  ],
  imports: [
    CommonModule, // Assurez-vous que CommonModule est importé
    RouterModule.forChild(productRoutes)
  ]
})
export class ProductsModule { }
