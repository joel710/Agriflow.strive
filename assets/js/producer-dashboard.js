// assets/js/producer-dashboard.js

const ProducerDashboard = {
    state: {
        producerId: null, // This should be set ideally from session or a global var
        products: [],
        orders: [],
        deliveries: [],
        stats: {},
        salesChartInstance: null,
        // productsChartInstance: null, // If we add a products chart later
    },

    init() {
        // Attempt to get producerId (e.g., from a global variable set by PHP)
        // For now, this is a placeholder. In a real scenario, this needs to be securely obtained.
        // if (typeof currentProducerId !== 'undefined') {
        //     this.state.producerId = currentProducerId;
        // } else {
        //     console.warn('Producer ID not found. Dashboard may not function correctly.');
        //     // Potentially redirect to login or show error
        // }

        this.bindEvents();
        this.loadInitialData();
        this.initSalesChart(); // Initialize chart structure, data will be loaded
        // this.initProductsChart(); // If we have one
    },

    bindEvents() {
        // Tab navigation (if not handled by inline script in HTML)
        // Example: document.querySelectorAll('.nav-tab').forEach(tab => tab.addEventListener('click', this.handleTabClick.bind(this)));

        // Add product form submission
        const addProductForm = document.getElementById('product-form');
        if (addProductForm) {
            const saveProductButton = document.getElementById('save-product-button');
            if (saveProductButton) {
                saveProductButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleAddProduct();
                });
            } else {
                console.warn("Save product button not found");
            }
        } else {
            console.warn("Product form not found");
        }

        // Listener for "Ajouter un produit" button to toggle form visibility
        const addProductBtn = document.querySelector('#mes-produits button.bg-primary'); // More specific selector
        if (addProductBtn && addProductBtn.textContent.includes('Ajouter un produit')) {
            addProductBtn.addEventListener('click', () => {
                const form = document.getElementById('product-form');
                if (form) form.classList.toggle('hidden');
            });
        }

        // Potentially listeners for edit/delete product buttons once products are rendered

        // Logout button listeners
        const logoutButton = document.getElementById('logout-button');
        if (logoutButton) {
            logoutButton.addEventListener('click', this.handleLogout);
        }
        const mobileLogoutButton = document.getElementById('mobile-logout-button');
        if (mobileLogoutButton) {
            mobileLogoutButton.addEventListener('click', this.handleLogout);
        }

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    },

    handleLogout() {
        alert('Déconnexion simulée réussie. Redirection...');
        // In a real application, you would call the actual logout endpoint:
        // window.location.href = 'auth/process.php?action=logout';
        window.location.href = 'index.html'; // Redirect to homepage for simulation
    },

    async loadInitialData() {
        console.log("Loading initial data for producer dashboard...");
        await this.fetchDashboardStats();
        await this.fetchRecentOrdersForDashboard();
        // Data for specific tabs can be loaded when tabs are clicked or all at once
        await this.fetchProducts();
        await this.fetchOrders();
        await this.fetchDeliveries();
        await this.fetchFullStats(); // For the statistics tab
    },

    // --- API Fetching Functions ---
    // These functions will use hypothetical API endpoints.
    // Backend will need to implement these for producers.

    async fetchDashboardStats() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/dashboard-stats
        // Expected response:
        // {
        //   status: "success",
        //   data: {
        //     salesToday: { amount: 50000, currency: "FCFA", trend: "+5%" },
        //     ordersToday: { count: 5, new: 2 },
        //     totalProducts: { count: 50, lowStock: 3 },
        //     activeCustomers: { count: 20, newThisWeek: 4 },
        //     monthlySalesData: [ { month: "Jan", sales: 120000 }, ... ]
        //   }
        // }
        console.log("Fetching dashboard stats...");
        try {
            // const response = await fetch('/api/producer/dashboard-stats'); // Replace with actual API call
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.state.stats.dashboard = result.data;
            //     this.updateDashboardStatsUI(result.data);
            //     if (result.data.monthlySalesData) {
            //        this.updateSalesChart(result.data.monthlySalesData);
            //     }
            // } else {
            //     console.error('Failed to fetch dashboard stats:', result.message);
            // }
            // --- MOCK DATA FOR NOW ---
            const mockData = {
                salesToday: { amount: 386000, currency: "FCFA", trend: "+12%" },
                ordersToday: { count: 24, new: 5 },
                totalProducts: { count: 42, lowStock: 3 },
                activeCustomers: { count: 156, newThisWeek: "+8" }, // Assuming 'clients' means customers who ordered from this producer
                monthlySalesData: [
                    { month: 'Jan', sales: 1200 }, { month: 'Fév', sales: 1350 }, { month: 'Mar', sales: 1800 },
                    { month: 'Avr', sales: 2100 }, { month: 'Mai', sales: 1950 }, { month: 'Juin', sales: 2300 },
                    { month: 'Juil', sales: 2450 }, { month: 'Août', sales: 2800 }, { month: 'Sep', sales: 3100 },
                    { month: 'Oct', sales: 3450 }, { month: 'Nov', sales: 3800 }, { month: 'Déc', sales: 4200 }
                ]
            };
            this.state.stats.dashboard = mockData;
            this.updateDashboardStatsUI(mockData);
            this.updateSalesChart(mockData.monthlySalesData);
            console.log("Mock dashboard stats loaded.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
            // Display error to user
        }
    },

    async fetchRecentOrdersForDashboard() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/orders?limit=5&sort=recent
        // Expected response:
        // {
        //   status: "success",
        //   data: [
        //     { id: 1, customerName: "Client A", totalAmount: 15000, currency: "FCFA", status: "En préparation", itemsPreview: "Tomates, Courgettes" }, ...
        //   ]
        // }
        console.log("Fetching recent orders for dashboard...");
        try {
            // const response = await fetch('/api/producer/orders?limit=5&sort=recent');
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.updateRecentOrdersUI(result.data);
            // } else {
            //     console.error('Failed to fetch recent orders:', result.message);
            // }
            // --- MOCK DATA FOR NOW ---
            const mockData = [
                { id: 1, customerName: "Marie Lefevre", itemsPreview: "Tomates, Courgettes, Aubergines", totalAmount: 4250, currency: "FCFA", status: "En préparation" },
                { id: 2, customerName: "Pierre Moreau", itemsPreview: "Pommes, Poires", totalAmount: 1875, currency: "FCFA", status: "En livraison" },
                { id: 3, customerName: "Restaurant Le Terroir", itemsPreview: "Carottes, Pommes de terre, Oignons", totalAmount: 8720, currency: "FCFA", status: "Livré" },
            ];
            this.updateRecentOrdersUI(mockData);
            console.log("Mock recent orders loaded.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching recent orders:', error);
        }
    },

    async fetchProducts() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/products
        // Expected response:
        // {
        //   status: "success",
        //   data: [
        //     { id: 1, name: "Tomates Bio", price: 3.50, unit: "kg", stockQuantity: 100, imageUrl: "url", status: "En stock" }, ...
        //   ]
        // }
        console.log("Fetching products...");
        try {
            // const response = await fetch('/api/producer/products');
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.state.products = result.data;
            //     this.updateProductsGridUI(result.data);
            // } else {
            //     console.error('Failed to fetch products:', result.message);
            // }
            // --- MOCK DATA FOR NOW ---
            const mockData = [
                { id: 1, name: "Tomates Bio Lot 1", price: 350, unit: "kg", stockQuantity: 100, imageUrl: "https://readdy.ai/api/search-image?query=fresh%20organic%20carrots%20on%20white%20background%2C%20high%20quality%20product%20photography%2C%20vibrant%20orange%20color&width=300&height=200&seq=5&orientation=landscape", stockStatus: "En stock" },
                { id: 2, name: "Pommes Gala", price: 250, unit: "kg", stockQuantity: 5, imageUrl: "https://readdy.ai/api/search-image?query=fresh%20organic%20apples%20on%20white%20background%2C%20high%20quality%20product%20photography%2C%20red%20and%20green%20apples&width=300&height=200&seq=6&orientation=landscape", stockStatus: "Stock faible" },
                { id: 3, name: "Laitue Fraîche", price: 150, unit: "pièce", stockQuantity: 0, imageUrl: "https://readdy.ai/api/search-image?query=fresh%20organic%20lettuce%20on%20white%20background%2C%20high%20quality%20product%20photography%2C%20crisp%20green%20leaves&width=300&height=200&seq=7&orientation=landscape", stockStatus: "Rupture" }
            ];
            this.state.products = mockData;
            this.updateProductsGridUI(mockData);
            console.log("Mock products loaded.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching products:', error);
        }
    },

    async handleAddProduct() {
        const name = document.getElementById('product-name').value;
        const category = document.getElementById('product-category').value; // Assuming this is a text or ID
        const price = document.getElementById('product-price').value;
        // const unit = document.getElementById('product-unit').value; // If unit field is added
        const quantity = document.getElementById('product-quantity-value').value; // Renamed for clarity if it's a value
        const description = document.getElementById('product-description').value;
        const imageFile = document.getElementById('product-image-file').files[0]; // Assuming input type file with id 'product-image-file'

        // Basic validation
        if (!name || !price || !quantity) {
            alert('Nom, prix et quantité sont requis.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('category', category);
        formData.append('price', parseFloat(price));
        // formData.append('unit', unit);
        formData.append('stock_quantity', parseInt(quantity));
        formData.append('description', description);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        // HYPOTHETICAL ENDPOINT: POST /api/producer/products
        // Body: FormData with product details
        // Expected response:
        // { status: "success", data: { newProductDetails }, message: "Produit ajouté" }
        // or { status: "error", message: "...", errors: { field: "message" } }
        console.log("Adding product...", Object.fromEntries(formData));
        try {
            // const response = await fetch('/api/producer/products', {
            //     method: 'POST',
            //     body: formData,
            //     // headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } // If token auth is used
            // });
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();

            // if (result.status === 'success') {
            //     alert(result.message || 'Produit ajouté avec succès!');
            //     document.getElementById('product-form').classList.add('hidden'); // Hide form
            //     document.getElementById('product-form').reset(); // Reset form fields
            //     this.fetchProducts(); // Refresh product list
            // } else {
            //     console.error('Failed to add product:', result.message, result.errors);
            //     alert(`Erreur: ${result.message}`);
            // }
            // --- MOCK SUCCESS ---
            alert('Produit ajouté avec succès (Simulation)!');
            document.getElementById('product-form-html').reset(); // Assuming form has id 'product-form-html'
            document.getElementById('product-form').classList.add('hidden');
            this.fetchProducts(); // To show it in the list (mock will just refetch same data)
            console.log("Mock product added.");
            // --- END MOCK ---
        } catch (error) {
            console.error('Error adding product:', error);
            alert('Une erreur est survenue lors de l\'ajout du produit.');
        }
    },


    async fetchOrders() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/orders
        // Expected response:
        // {
        //   status: "success",
        //   data: [
        //     { id: 1, customerName: "Client A", itemsPreview: "Produit X, Produit Y", totalAmount: 12000, status: "En attente" }, ...
        //   ]
        // }
        console.log("Fetching orders for 'Commandes' tab...");
        try {
            // const response = await fetch('/api/producer/orders');
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.state.orders = result.data;
            //     this.updateOrdersTableUI(result.data);
            // } else {
            //     console.error('Failed to fetch orders:', result.message);
            // }
             // --- MOCK DATA FOR NOW ---
            const mockData = [
                { id: 101, client: 'Alice Dupont', produits: 'Tomates, Salades', montant: '5000 FCFA', statut: 'Livré' },
                { id: 102, client: 'Bob Martin', produits: 'Pommes, Carottes', montant: '3200 FCFA', statut: 'En cours' },
                { id: 103, client: 'Charles D.', produits: 'Oeufs, Fromage', montant: '7500 FCFA', statut: 'En attente' }
            ];
            this.state.orders = mockData;
            this.updateOrdersTableUI(mockData);
            console.log("Mock orders loaded for tab.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching orders:', error);
        }
    },

    async fetchDeliveries() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/deliveries
        // Expected response:
        // {
        //   status: "success",
        //   data: [
        //     { id: 1, orderId: 101, customerName: "Client A", estimatedDate: "2024-07-20", status: "Prêt pour collecte" }, ...
        //   ]
        // }
        console.log("Fetching deliveries...");
        try {
            // const response = await fetch('/api/producer/deliveries');
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.state.deliveries = result.data;
            //     this.updateDeliveriesTableUI(result.data);
            // } else {
            //     console.error('Failed to fetch deliveries:', result.message);
            // }
            // --- MOCK DATA FOR NOW ---
            const mockData = [
                { numero: 'LIVP001', client: 'Alice Dupont', date: '2024-06-01', statut: 'Livré' },
                { numero: 'LIVP002', client: 'Bob Martin', date: '2024-06-02', statut: 'En cours de préparation' },
                { numero: 'LIVP003', client: 'Charles D.', date: '2024-06-05', statut: 'En attente de livreur' }
            ];
            this.state.deliveries = mockData;
            this.updateDeliveriesTableUI(mockData);
            console.log("Mock deliveries loaded.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching deliveries:', error);
        }
    },

    async fetchFullStats() {
        // HYPOTHETICAL ENDPOINT: GET /api/producer/statistics
        // Expected response:
        // {
        //   status: "success",
        //   data: {
        //     totalOrders: 152,
        //     totalSales: 1250000, currency: "FCFA",
        //     // ... other detailed stats for the "Statistiques" tab
        //   }
        // }
        console.log("Fetching full stats for 'Statistiques' tab...");
        try {
            // const response = await fetch('/api/producer/statistics');
            // if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            // const result = await response.json();
            // if (result.status === 'success') {
            //     this.state.stats.full = result.data;
            //     this.updateStatisticsTabUI(result.data);
            // } else {
            //     console.error('Failed to fetch full stats:', result.message);
            // }
            // --- MOCK DATA FOR NOW ---
            const mockData = {
                totalOrders: 152,
                totalSales: 1250000, currency: "FCFA",
            };
            this.state.stats.full = mockData;
            this.updateStatisticsTabUI(mockData);
            console.log("Mock full stats loaded.");
            // --- END MOCK DATA ---
        } catch (error) {
            console.error('Error fetching full stats:', error);
        }
    },

    // --- UI Update Functions ---
    updateDashboardStatsUI(data) {
        document.getElementById('stats-ventes-jour-montant').textContent = `${data.salesToday.amount.toLocaleString('fr-FR')} ${data.salesToday.currency}`;
        document.getElementById('stats-ventes-jour-variation').textContent = data.salesToday.trend;
        document.getElementById('stats-commandes-total').textContent = data.ordersToday.count;
        document.getElementById('stats-commandes-nouvelles').textContent = `${data.ordersToday.new} nouvelles aujourd'hui`;
        document.getElementById('stats-produits-total').textContent = data.totalProducts.count;
        document.getElementById('stats-produits-stock-faible').textContent = `${data.totalProducts.lowStock} stocks faibles`;
        document.getElementById('stats-clients-total').textContent = data.activeCustomers.count; // Name needs to match HTML
        document.getElementById('stats-clients-nouveaux').textContent = `${data.activeCustomers.newThisWeek} cette semaine`; // Name needs to match HTML
    },

    initSalesChart() {
        const chartDom = document.getElementById('salesChart');
        if (!chartDom || typeof echarts === 'undefined') {
            console.warn('Sales chart DOM or ECharts library not found.');
            return;
        }
        this.state.salesChartInstance = echarts.init(chartDom);
        const option = {
            tooltip: { trigger: 'axis' },
            grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: [], // Will be populated by updateSalesChart
                axisLine: { lineStyle: { color: '#ddd' } },
                axisLabel: { color: '#1f2937' }
            },
            yAxis: {
                type: 'value',
                axisLine: { show: false },
                axisLabel: { color: '#1f2937', formatter: '{value} FCFA' }, // Assuming FCFA
                splitLine: { lineStyle: { color: '#eee' } }
            },
            series: [{
                name: 'Ventes',
                type: 'line',
                smooth: true,
                data: [], // Will be populated
                lineStyle: { width: 3, color: 'rgba(76, 175, 80, 1)' }, // Primary color
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: 'rgba(76, 175, 80, 0.3)' },
                        { offset: 1, color: 'rgba(76, 175, 80, 0.05)' }
                    ])
                },
                emphasis: { focus: 'series' },
                showSymbol: false,
            }]
        };
        this.state.salesChartInstance.setOption(option);
        window.addEventListener('resize', () => this.state.salesChartInstance.resize());
    },

    updateSalesChart(monthlySalesData) { // monthlySalesData = [{ month: "Jan", sales: 1200 }, ...]
        if (!this.state.salesChartInstance || !monthlySalesData) return;
        const months = monthlySalesData.map(d => d.month);
        const sales = monthlySalesData.map(d => d.sales);
        this.state.salesChartInstance.setOption({
            xAxis: { data: months },
            series: [{ data: sales }]
        });
    },

    updateRecentOrdersUI(orders) {
        const tbody = document.getElementById('dashboard-recent-orders-tbody');
        if (!tbody) return;
        tbody.innerHTML = ''; // Clear existing
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Aucune commande récente.</td></tr>';
            return;
        }
        orders.forEach(order => {
            const row = tbody.insertRow();
            row.className = 'border-t border-gray-100';
            row.innerHTML = `
                <td class="py-3">${order.customerName}</td>
                <td class="py-3">${order.itemsPreview}</td>
                <td class="py-3">${order.totalAmount.toLocaleString('fr-FR')} ${order.currency}</td>
                <td class="py-3"><span class="px-2 py-1 ${this.getOrderStatusClass(order.status)} rounded text-xs">${order.status}</span></td>
                <td class="py-3">
                    <button class="text-primary hover:text-primary-dark" onclick="ProducerDashboard.viewOrder(${order.id})">Détails</button>
                </td>
            `;
        });
    },

    updateProductsGridUI(products) {
        const grid = document.getElementById('products-grid');
        if (!grid) return;
        grid.innerHTML = ''; // Clear existing
        if (!products || products.length === 0) {
            grid.innerHTML = '<p class="col-span-full text-center py-4">Aucun produit trouvé.</p>';
            return;
        }
        products.forEach(product => {
            const productCard = `
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    <div class="h-48 bg-gray-100 relative">
                        <img src="${product.imageUrl || 'placeholder.jpg'}" alt="${product.name}" class="w-full h-full object-cover">
                        <span class="absolute top-2 right-2 ${this.getProductStockClass(product.stockStatus)} text-white px-2 py-1 rounded text-xs">${product.stockStatus}</span>
                    </div>
                    <div class="p-4">
                        <h4 class="font-semibold text-lg">${product.name}</h4>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-primary font-bold">${product.price.toLocaleString('fr-FR')} FCFA/${product.unit}</span>
                            <div class="flex items-center">
                                <i class="ri-edit-box-line text-gray-500 hover:text-primary cursor-pointer mr-2" onclick="ProducerDashboard.editProduct(${product.id})"></i>
                                <i class="ri-delete-bin-line text-gray-500 hover:text-red-500 cursor-pointer" onclick="ProducerDashboard.deleteProduct(${product.id})"></i>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Stock: ${product.stockQuantity}</p>
                    </div>
                </div>
            `;
            grid.innerHTML += productCard;
        });
    },

    updateOrdersTableUI(orders) {
        const tbody = document.getElementById('orders-tbody');
        if (!tbody) return;
        tbody.innerHTML = ''; // Clear existing
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Aucune commande.</td></tr>';
            return;
        }
        orders.forEach(order => {
            const row = tbody.insertRow();
            row.className = 'border-t border-gray-100';
            // Columns: Client, Produits, Montant, Statut, Action
            row.innerHTML = `
                <td class="py-3">${order.client}</td>
                <td class="py-3">${order.produits}</td>
                <td class="py-3">${order.montant}</td>
                <td class="py-3"><span class="px-2 py-1 ${this.getOrderStatusClass(order.statut)} rounded text-xs">${order.statut}</span></td>
                <td class="py-3"><button class="text-primary hover:text-primary-dark" onclick="ProducerDashboard.viewOrder(${order.id})">Détails</button></td>
            `;
        });
    },

    updateDeliveriesTableUI(deliveries) {
        const tbody = document.getElementById('deliveries-tbody');
        if (!tbody) return;
        tbody.innerHTML = ''; // Clear existing
        if (!deliveries || deliveries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Aucune livraison.</td></tr>';
            return;
        }
        deliveries.forEach(delivery => {
            const row = tbody.insertRow();
            row.className = 'border-t border-gray-100';
            // Columns: Numéro, Client, Date estimée, Statut, Action
            row.innerHTML = `
                <td class="py-3">${delivery.numero}</td>
                <td class="py-3">${delivery.client}</td>
                <td class="py-3">${delivery.date}</td>
                <td class="py-3"><span class="px-2 py-1 ${this.getDeliveryStatusClass(delivery.statut)} rounded text-xs">${delivery.statut}</span></td>
                <td class="py-3"><button class="text-primary hover:text-primary-dark" onclick="ProducerDashboard.viewDeliveryDetails(${delivery.numero.replace('LIVP','')})">Détails</button></td>
            `;
        });
    },

    updateStatisticsTabUI(stats) {
        const ordersCountEl = document.getElementById('stat-orders-count');
        const salesAmountEl = document.getElementById('stat-sales-amount');
        if (ordersCountEl) ordersCountEl.textContent = stats.totalOrders.toLocaleString('fr-FR');
        if (salesAmountEl) salesAmountEl.textContent = `${stats.totalSales.toLocaleString('fr-FR')} ${stats.currency}`;
    },

    // --- Helper Functions ---
    getOrderStatusClass(status) {
        // Similar to client dashboard, adjust if producer statuses differ
        const statusMap = {
            'En préparation': 'bg-yellow-100 text-yellow-800',
            'En attente': 'bg-orange-100 text-orange-800',
            'Prêt pour collecte': 'bg-blue-100 text-blue-800',
            'En livraison': 'bg-blue-100 text-blue-800', // Consider different color
            'Livré': 'bg-green-100 text-green-800',
            'Annulé': 'bg-red-100 text-red-800'
        };
        return statusMap[status] || 'bg-gray-100 text-gray-800';
    },

    getDeliveryStatusClass(status) {
        // May need specific statuses for producer view
        const classMap = {
            'En attente de livreur': 'bg-yellow-100 text-yellow-800',
            'En cours de préparation': 'bg-purple-100 text-purple-800',
            'Prêt pour envoi': 'bg-indigo-100 text-indigo-800',
            'En transit': 'bg-blue-100 text-blue-800',
            'Livré': 'bg-green-100 text-green-800',
            'Problème': 'bg-red-100 text-red-800'
        };
        return classMap[status] || 'bg-gray-100 text-gray-800';
    },

    getProductStockClass(stockStatus) {
        const classMap = {
            'En stock': 'bg-primary', // Green
            'Stock faible': 'bg-yellow-500', // Yellow/Orange
            'Rupture': 'bg-red-500' // Red
        };
        return classMap[stockStatus] || 'bg-gray-500';
    },

    // Placeholder for actual action handlers
    viewOrder(orderId) {
        console.log(`Viewing order details for ID: ${orderId}`);
        // Implement modal or navigation to order detail page
        alert(`Afficher les détails de la commande: ${orderId} (à implémenter)`);
    },
    editProduct(productId) {
        console.log(`Editing product ID: ${productId}`);
        alert(`Modifier le produit: ${productId} (à implémenter - pré-remplir le formulaire)`);
         // 1. Fetch product details by ID
         // 2. Populate the product form with these details
         // 3. Change "Ajouter un produit" button to "Modifier le produit"
         // 4. Change form submission to call an updateProduct(productId) method
         // 5. Show the form
    },
    deleteProduct(productId) {
        console.log(`Deleting product ID: ${productId}`);
        if (confirm(`Voulez-vous vraiment supprimer le produit ID ${productId}?`)) {
            // HYPOTHETICAL ENDPOINT: DELETE /api/producer/products/${productId}
            // try {
            //    const response = await fetch(`/api/producer/products/${productId}`, { method: 'DELETE' });
            //    if (response.ok) {
            //        alert('Produit supprimé!');
            //        this.fetchProducts(); // Refresh list
            //    } else {
            //        const err = await response.json();
            //        alert(`Erreur: ${err.message}`);
            //    }
            // } catch (e) { console.error(e); alert('Erreur suppression.');}
            alert(`Produit ${productId} supprimé (Simulation)!`);
            this.fetchProducts(); // Refresh (mock)
        }
    },
    viewDeliveryDetails(deliveryId){
        console.log(`Viewing delivery details for ID: ${deliveryId}`);
        alert(`Afficher les détails de la livraison: ${deliveryId} (à implémenter)`);
    }
};

// Initialize the dashboard when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    ProducerDashboard.init();
});
