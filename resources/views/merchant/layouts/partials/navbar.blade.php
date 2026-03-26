<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="{{ route('home') }}" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        @if(auth()->user()->subscribed == 'no')
            <a href="{{ route('home') }}" class="nav-link">
                {{ __('dashboard.title') }}
            </a>
        @else
            <button type="button" class="btn btn-success btn-sm ml-2">Subscribed</button>
        @endif
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Wallet Balance Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" role="button">
                <i class="fas fa-wallet text-success"></i>
                <span class="font-weight-bold ml-1">
                    {{ config('app.currency_symbol', '$') }} {{ number_format(walletBalance(), 2) }}
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" style="min-width: 250px;">
                <div class="dropdown-header bg-light">
                    <strong>Wallet Balance</strong>
                </div>
                <div class="dropdown-item text-center py-3">
                    <h3 class="text-success mb-0">
                        {{ config('app.currency_symbol', '$') }} {{ number_format(walletBalance(), 2) }}
                    </h3>
                    <small class="text-muted">Available for payout</small>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('merchant.wallet.index') }}" class="dropdown-item">
                    <i class="fas fa-chart-line mr-2"></i> Wallet Dashboard
                </a>
                <a href="{{ route('merchant.wallet.payout') }}" class="dropdown-item">
                    <i class="fas fa-paper-plane mr-2"></i> Request Payout
                </a>
                <a href="{{ route('merchant.wallet.history') }}" class="dropdown-item">
                    <i class="fas fa-history mr-2"></i> Payout History
                </a>
                <div class="dropdown-divider"></div>
                <div class="dropdown-item text-muted small">
                    <i class="fas fa-info-circle"></i> Min payout: $10
                </div>
            </div>
        </li>

        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" id="notificationDropdown">
                <i class="far fa-bell"></i>
                @php
                    $notificationCount = count(notifications());
                @endphp
                @if($notificationCount > 0)
                    <span class="navbar-badge badge badge-warning">{{ $notificationCount }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notificationMenu">
                <span class="dropdown-item dropdown-header" id="notificationHeader">
                    Notifications ({{ $notificationCount }})
                </span>
                <div class="dropdown-divider"></div>
                
                <!-- Notifications List Container -->
                <div id="notificationList">
                    @php
                        $allNotifications = notifications();
                        $notificationsArray = [];
                        
                        // Handle different return types
                        if (is_array($allNotifications)) {
                            $notificationsArray = $allNotifications;
                        } elseif (is_object($allNotifications) && method_exists($allNotifications, 'toArray')) {
                            $notificationsArray = $allNotifications->toArray();
                        } elseif ($allNotifications instanceof \Illuminate\Support\Collection) {
                            $notificationsArray = $allNotifications->all();
                        }
                        
                        // Get only first 5
                        $latestNotifications = array_slice($notificationsArray, 0, 5);
                    @endphp
                    
                    @if(!empty($latestNotifications))
                        @foreach($latestNotifications as $notification)
                            @php
                                // Handle both object and array notifications
                                if (is_object($notification)) {
                                    $message = $notification->message ?? 'No message';
                                    $createdAt = isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at) : now();
                                } else {
                                    $message = $notification['message'] ?? $notification[0] ?? 'No message';
                                    $createdAt = isset($notification['created_at']) ? \Carbon\Carbon::parse($notification['created_at']) : now();
                                }
                                $timeAgo = $createdAt->diffForHumans();
                            @endphp
                            
                            <div class="dropdown-item notification-item">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-message">
                                            {{ $message }}
                                        </div>
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i> {{ $timeAgo }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="dropdown-item text-center text-muted">
                            <i class="far fa-bell-slash"></i> No notifications
                        </div>
                    @endif
                </div>
                
                <!-- View All Link -->
                <div class="dropdown-divider"></div>
                <a href="" class="dropdown-item dropdown-footer">
                    View All Notifications
                </a>
            </div>
        </li>

        <!-- Language Switcher -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-globe"></i> {{ strtoupper(app()->getLocale()) }}
            </a>
            <div id="switchLang" class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('lang.switch', ['lang' => 'en']) }}" class="dropdown-item">English</a>
                <a href="{{ route('lang.switch', ['lang' => 'es']) }}" class="dropdown-item">Español</a>
            </div>
        </li>

        <!-- User Account Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle"></i> {{ auth()->user()->getFullname() }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href=" " class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
                <a href="" class="dropdown-item">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" class="dropdown-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt mr-2"></i> {{ __('common.Logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>
<!-- /.navbar -->

<!-- Add CSS for notifications design -->
<style>
    /* Notification Dropdown Styles */
    #notificationMenu {
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-item {
        padding: 10px 15px;
        transition: background-color 0.2s;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        flex-shrink: 0;
        background-color: #f0f0f0;
    }
    
    .notification-icon i {
        font-size: 16px;
        color: #007bff;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-message {
        color: #333;
        margin-bottom: 4px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    .notification-time {
        color: #999;
        font-size: 11px;
        display: flex;
        align-items: center;
    }
    
    .notification-time i {
        margin-right: 4px;
        font-size: 10px;
    }
    
    /* Navbar badge styling */
    .navbar-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 10px;
        padding: 2px 5px;
        border-radius: 10px;
        background-color: #ffc107;
        color: #333;
    }
    
    /* Dropdown header styling */
    .dropdown-header {
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 15px;
    }
    
    /* Footer link styling */
    .dropdown-footer {
        text-align: center;
        font-size: 13px;
        padding: 10px;
        color: #007bff;
        font-weight: 500;
    }
    
    .dropdown-footer:hover {
        background-color: #f8f9fa;
        text-decoration: none;
    }
    
    /* Divider styling */
    .dropdown-divider {
        margin: 0;
        border-top: 1px solid #dee2e6;
    }
    
    /* Scrollbar styling */
    #notificationMenu::-webkit-scrollbar {
        width: 6px;
    }
    
    #notificationMenu::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #notificationMenu::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }
    
    #notificationMenu::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
    
    /* Animation for badge */
    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    
    .badge-warning {
        animation: pulse 2s infinite;
    }
    
    /* Wallet dropdown styles */
    .wallet-balance {
        transition: all 0.3s ease;
    }
    
    .wallet-balance:hover {
        transform: scale(1.05);
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        #notificationMenu {
            width: 300px;
        }
        
        .notification-icon {
            width: 32px;
            height: 32px;
        }
        
        .notification-icon i {
            font-size: 14px;
        }
        
        .notification-message {
            font-size: 12px;
        }
    }
</style>