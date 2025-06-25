import { NgModule } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe, TitleCasePipe } from '@angular/common'; // Import des pipes
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from '../../core/guards/auth.guard';

import { OrderHistoryComponent } from './order-history/order-history.component';
import { OrderDetailComponent } from './order-detail/order-detail.component';

const orderRoutes: Routes = [
  {
    path: 'mes-commandes',
    component: OrderHistoryComponent,
    canActivate: [AuthGuard], // Protéger la route
    data: { roles: ['client', 'producer', 'admin'] } // Accessible par tous les rôles connectés, l'API filtrera
  },
  {
    path: 'mes-commandes/:id',
    component: OrderDetailComponent,
    canActivate: [AuthGuard], // Protéger la route
    data: { roles: ['client', 'producer', 'admin'] }
  },
  // TODO: Ajouter une route pour la gestion des commandes par les producteurs
  // {
  //   path: 'gestion-commandes', // Pour les producteurs
  //   component: ProducerOrderManagementComponent, // À créer
  //   canActivate: [AuthGuard],
  //   data: { roles: ['producer', 'admin'] }
  // }
];

@NgModule({
  declarations: [
    OrderHistoryComponent,
    OrderDetailComponent
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(orderRoutes)
  ],
  providers: [
    CurrencyPipe, // Rendre les pipes disponibles si pas importés globalement ou dans un shared module
    DatePipe,
    TitleCasePipe
  ]
})
export class OrdersModule { }
