import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';

import { CartViewComponent } from './cart-view/cart-view.component';
import { CartIconComponent } from './cart-icon/cart-icon.component';

const cartRoutes: Routes = [
  { path: 'panier', component: CartViewComponent }
];

@NgModule({
  declarations: [
    CartViewComponent,
    CartIconComponent
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(cartRoutes)
  ],
  exports: [
    CartIconComponent // Exporter pour l'utiliser dans la Navbar
  ]
})
export class CartModule { }
