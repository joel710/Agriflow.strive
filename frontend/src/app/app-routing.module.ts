import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
// import { HomeComponent } from './features/home/home.component'; // À créer si besoin

const routes: Routes = [
  // { path: '', component: HomeComponent, pathMatch: 'full' }, // Page d'accueil
  {
    path: '', // Routes d'authentification (login, register)
    loadChildren: () => import('./features/auth/auth.module').then(m => m.AuthModule)
  },
  {
    path: '', // Routes des produits (marche, produit/:id)
    loadChildren: () => import('./features/products/products.module').then(m => m.ProductsModule)
  },
  {
    path: '', // Routes du panier (panier)
    loadChildren: () => import('./features/cart/cart.module').then(m => m.CartModule)
  },
  {
    path: '', // Routes du checkout
    loadChildren: () => import('./features/checkout/checkout.module').then(m => m.CheckoutModule)
  },
  {
    path: '', // Routes des commandes (mes-commandes, etc.)
    loadChildren: () => import('./features/orders/orders.module').then(m => m.OrdersModule)
  },
  {
    path: '', // Route du portefeuille
    loadChildren: () => import('./features/wallet/wallet.module').then(m => m.WalletModule)
  },
  // TODO: Ajouter des routes pour les tableaux de bord client et producteur (protégées par AuthGuard)
  // {
  //   path: 'tableau-client',
  //   loadChildren: () => import('./features/client-dashboard/client-dashboard.module').then(m => m.ClientDashboardModule),
  //   canActivate: [AuthGuard], data: { roles: ['client'] }
  // },
  // {
  //   path: 'tableau-producteur',
  //   loadChildren: () => import('./features/producer-dashboard/producer-dashboard.module').then(m => m.ProducerDashboardModule),
  //   canActivate: [AuthGuard], data: { roles: ['producteur'] }
  // },
  // {
  //   path: 'admin',
  //   loadChildren: () => import('./features/admin/admin.module').then(m => m.AdminModule),
  //   canActivate: [AuthGuard], data: { roles: ['admin'] }
  // },


  // Redirect to a default page (e.g., marketplace or home)
  { path: '', redirectTo: '/marche', pathMatch: 'full' }, // Redirection par défaut
  { path: '**', redirectTo: '/marche' } // Wildcard route for a 404 page or redirect (ou une page 404 dédiée)
];

@NgModule({
  imports: [RouterModule.forRoot(routes, {
    // enableTracing: true, // <-- debugging purposes only
    scrollPositionRestoration: 'enabled' // Restaure la position de défilement lors de la navigation
  })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
