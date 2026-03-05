<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{route('home')}}" class="brand-link">
        <img src="{{ asset('images/logo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
             style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>{{ __('dashboard.title') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.dashboard') }}" class="nav-link {{ request()->routeIs('merchant.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>{{ __('dashboard.title') }}</p>
                        </a>
                    @endif
                </li>

                <!-- Merchant Specific Menu Items -->
                @if(auth()->user()->role_id == 2)
                    <li class="nav-item">
                        <a href="{{ route('merchant.barter-assist-pos') }}" class="nav-link {{ request()->routeIs('merchant.barter-assist-pos') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('POS') }}</p>
                        </a>
                    </li>
                
                    <!-- Cash Price -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.barter-assist-cash-price') }}" class="nav-link {{ request()->routeIs('merchant.barter-assist-cash-price') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('Cash Price') }}</p>
                        </a>
                    </li>
                
                    <!-- External links -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.external-links') }}" class="nav-link {{ request()->routeIs('merchant.external-links') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('External Links') }}</p>
                        </a>
                    </li>
                @endif
            
                <!-- Products -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('products.index') }}" class="nav-link {{ activeSegment('products') }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('product.title') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.products.index') }}" class="nav-link {{ activeSegment('products') }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('product.title') }}</p>
                        </a>
                    @endif
                </li>

                <!-- Governance Layers (Admin Only) -->
                @if(auth()->user()->role_id == 1)
                    <li class="nav-item">
                        <a href="{{ route('admin.governance-layers') }}" class="nav-link {{ activeSegment('governance-layers') }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('Governance Layers') }}</p>
                        </a>
                    </li>


                    <li class="nav-item">
                        <a href="{{ route('admin.vouchers.index') }}" class="nav-link {{ activeSegment('governance-layers') }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>Vouchers</p>
                        </a>
                    </li>
                @endif

                <li class="nav-header">{{ __('Sales') }}</li>
                
                <!-- POS Cart -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('cart.index') }}" class="nav-link {{ activeSegment('cart') }}">
                            <i class="nav-icon fas fa-cart-plus"></i>
                            <p>{{ __('POS') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.cart.index') }}" class="nav-link {{ activeSegment('cart') }}">
                            <i class="nav-icon fas fa-cart-plus"></i>
                            <p>{{ __('POS') }}</p>
                        </a>
                    @endif
                </li>

                <!-- Orders -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('orders.index') }}" class="nav-link {{ activeSegment('orders') }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>{{ __('Order List') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.orders.index') }}" class="nav-link {{ activeSegment('orders') }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>{{ __('Order List') }}</p>
                        </a>
                    @endif
                </li>

                <!-- Customers -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('customers.index') }}" class="nav-link {{ activeSegment('customers') }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('customer.title') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.customers.index') }}" class="nav-link {{ activeSegment('customers') }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('customer.title') }}</p>
                        </a>
                    @endif
                </li>

                <li class="nav-header">{{ __('Purchases') }}</li>
                
                <!-- Purchases (Dropdown) -->
                <li class="nav-item {{ request()->routeIs('purchases.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ activeSegment('purchases') }}">
                        <i class="nav-icon fas fa-box"></i>
                        <p>
                            {{ __('Purchases') }}
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            @if(auth()->user()->role_id == 1)
                                <a href="{{ route('purchases.create') }}" class="nav-link {{ request()->routeIs('purchases.create') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('New Purchase') }}</p>
                                </a>
                            @elseif(auth()->user()->role_id == 2)
                                <a href="{{ route('merchant.purchases.create') }}" class="nav-link {{ request()->routeIs('purchases.create') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('New Purchase') }}</p>
                                </a>
                            @endif
                        </li>
                        <li class="nav-item">
                            @if(auth()->user()->role_id == 1)
                                <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('All Purchases') }}</p>
                                </a>
                            @elseif(auth()->user()->role_id == 2)
                                <a href="{{ route('merchant.purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('All Purchases') }}</p>
                                </a>
                            @endif
                        </li>
                    </ul>
                </li>
                
                <!-- Suppliers -->
                <li class="nav-item">
                    @if(auth()->user()->role_id == 1)
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ activeSegment('suppliers') }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>{{ __('Supplier') }}</p>
                        </a>
                    @elseif(auth()->user()->role_id == 2)
                        <a href="{{ route('merchant.suppliers.index') }}" class="nav-link {{ activeSegment('suppliers') }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>{{ __('Supplier') }}</p>
                        </a>
                    @endif
                </li>

                <!-- Admin Only Section -->
                @if(auth()->user()->role_id == 1)
                    <li class="nav-header">{{ __('Extra') }}</li>
                    
                    <!-- Settings -->
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}" class="nav-link {{ activeSegment('settings') }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>{{ __('settings.title') }}</p>
                        </a>
                    </li>
                @endif

                <!-- Settings for Merchant -->
                @if(auth()->user()->role_id == 2)
                    <li class="nav-header">Settings</li>
                    <li class="nav-item">
                        <a href="{{ route('merchant.settings.index') }}" class="nav-link {{ activeSegment('settings') }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>{{ __('settings.title') }}</p>
                        </a>
                    </li>
                @endif

                <!-- Logout (Common for all users) -->
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>{{ __('common.Logout') }}</p>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" id="logout-form" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>