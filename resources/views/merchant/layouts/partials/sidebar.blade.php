<!-- Main Sidebar Container -->
<?php
 $status = representation_status();
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('merchant.dashboard') }}" class="brand-link">
        <img src="{{ asset('images/logo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('merchant.dashboard') }}" class="nav-link {{ request()->routeIs('merchant.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>{{ __('dashboard.title') }}</p>
                    </a>
                </li>

                <!-- Check if merchant status is approved -->
                @if($status == 'approved')
                    <!-- POS -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.team-members.index') }}" class="nav-link {{ request()->routeIs('merchant.team-members.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-people-group"></i>
                            <p>Team Members</p>
                        </a>
                    </li>


                    {{-- <li class="nav-item">
                        <a href="{{ route('merchant.barter-assist-pos') }}" class="nav-link {{ request()->routeIs('merchant.barter-assist-pos') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>{{ __('POS') }}</p>
                        </a>
                    </li>


                    <!-- Cash Price -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.barter-assist-cash-price') }}" class="nav-link {{ request()->routeIs('merchant.barter-assist-cash-price') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                            <p>{{ __('Cash Price') }}</p>
                        </a>
                    </li> --}}

                    <!-- External links -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.external-links') }}" class="nav-link {{ request()->routeIs('merchant.external-links') ? 'active' : '' }}">
                            <i class="fa-solid fa-link"></i>
                            <p>{{ __('External Links') }}</p>
                        </a>
                    </li>

                    <!-- Products -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.products.index') }}" class="nav-link {{ activeSegment('products') ? 'active' : '' }}">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <p>{{ __('product.title') }}</p>
                        </a>
                    </li>

                    <!-- Vouchers (commented out, uncomment if needed) -->
                    {{-- <li class="nav-item">
                        <a href="{{ route('merchant.vouchers.index') }}" class="nav-link {{ activeSegment('vouchers') ? 'active' : '' }}">
                            <i class="fa-solid fa-ticket"></i>
                            <p>Vouchers</p>
                        </a>
                    </li> --}}

                    <li class="nav-header">{{ __('Sales') }}</li>
                    
                    <!-- POS Cart -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.cart.index') }}" class="nav-link {{ activeSegment('cart') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cart-plus"></i>
                            <p>{{ __('POS') }}</p>
                        </a>
                    </li>

                    <!-- Orders -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.orders.index') }}" class="nav-link {{ activeSegment('orders') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>{{ __('Order List') }}</p>
                        </a>
                    </li>

                    <!-- Customers -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.customers.index') }}" class="nav-link {{ activeSegment('customers') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('customer.title') }}</p>
                        </a>
                    </li>

                    <li class="nav-header">{{ __('Purchases') }}</li>
                    
                    <!-- Purchases (Dropdown) -->
                    <li class="nav-item {{ request()->routeIs('merchant.purchases.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-box"></i>
                            <p>
                                {{ __('Purchases') }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('merchant.purchases.create') }}" class="nav-link {{ request()->routeIs('merchant.purchases.create') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('New Purchase') }}</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('merchant.purchases.index') }}" class="nav-link {{ request()->routeIs('merchant.purchases.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('All Purchases') }}</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Suppliers -->
                    <li class="nav-item">
                        <a href="{{ route('merchant.suppliers.index') }}" class="nav-link {{ activeSegment('suppliers') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>{{ __('Supplier') }}</p>
                        </a>
                    </li>

                    <!-- Settings Section for Merchants -->
                    <li class="nav-header">Settings</li>
                    
                @else
                    <!-- Show pending status message for unapproved merchants -->
                    <li class="nav-header">{{ __('Account Status') }}</li>
                    <li class="nav-item">
                        <div class="nav-link disabled">
                            <i class="nav-icon fas fa-info-circle"></i>
                            <p>{{ __('Your account is pending approval') }}</p>
                        </div>
                    </li>
                    
                    <!-- Optionally show limited menu items for pending merchants -->
                    <li class="nav-header">{{ __('Limited Access') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('merchant.settings.index') }}" class="nav-link {{ activeSegment('settings') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>{{ __('settings.title') }}</p>
                        </a>
                    </li>
                @endif

                <!-- Logout (Always visible) -->
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