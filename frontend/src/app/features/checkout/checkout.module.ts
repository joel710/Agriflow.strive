import { NgModule } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common'; // Import CurrencyPipe
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { CheckoutViewComponent } from './checkout-view/checkout-view.component';
import { AuthGuard } from '../../core/guards/auth.guard'; // Pour protéger la page checkout
import { OrderSuccessComponent } from './order-success/order-success.component'; // Importer

const checkoutRoutes: Routes = [
  {
    path: 'checkout',
    component: CheckoutViewComponent,
    canActivate: [AuthGuard]
  },
  {
    path: 'commande-succes/:id', // Route pour la page de succès
    component: OrderSuccessComponent,
    canActivate: [AuthGuard]
  }
  // Ajouter une route pour commande-echec si nécessaire
];

@NgModule({
  declarations: [
    CheckoutViewComponent,
    OrderSuccessComponent // Déclarer le nouveau composant
  ],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule.forChild(checkoutRoutes)
  ],
  providers: [
    CurrencyPipe // Fournir CurrencyPipe si pas déjà globalement
  ]
})
export class CheckoutModule { }
