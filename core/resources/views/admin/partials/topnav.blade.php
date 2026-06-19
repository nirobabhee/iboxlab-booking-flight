@php
    $sidenav = json_decode($sidenav);

    $settings = file_get_contents(resource_path('views/admin/setting/settings.json'));
    $settings = json_decode($settings);

    $translateSearchData = function (&$items) use (&$translateSearchData) {
        if (is_array($items) || is_object($items)) {
            foreach ($items as $key => &$item) {
                if (in_array($key, ['title', 'subtitle', 'header'], true) && is_string($item)) {
                    $item = __($item);
                    continue;
                }

                if ($key === 'keyword' && is_array($item)) {
                    foreach ($item as &$keyword) {
                        if (is_string($keyword)) {
                            $keyword = __($keyword);
                        }
                    }
                    unset($keyword);
                    continue;
                }

                if (is_array($item) || is_object($item)) {
                    $translateSearchData($item);
                }
            }
            unset($item);
        }
    };

    $translateSearchData($sidenav);
    $translateSearchData($settings);

    $routesData = [];
    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
        $name = $route->getName();
        if (strpos($name, 'admin') !== false) {
            $routeData = [
                $name => url($route->uri()),
            ];

            $routesData[] = $routeData;
        }
    }
@endphp

<!-- navbar-wrapper start -->
<nav class="navbar-wrapper bg--dark d-flex flex-wrap">
    <div class="navbar__left">
        <button type="button" class="res-sidebar-open-btn me-3"><i class="las la-bars"></i></button>
        <form class="navbar-search" autocomplete="off">
            <input type="search" name="admin_panel_search" class="navbar-search-field" id="searchInput" autocomplete="off"
                autocorrect="off" autocapitalize="off" spellcheck="false"
                placeholder="@lang('Search here...')">
            <span class="navbar-search-shortcut">
                <kbd>Ctrl</kbd>
                <span class="navbar-search-shortcut__plus">+</span>
                <kbd>K</kbd>
            </span>
            <i class="las la-search"></i>
        </form>
    </div>
    <div class="navbar__right">
        <ul class="navbar__action-list">
           
            <li>
                <button type="button" class="primary--layer" data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('Visit Website')">
                    <a href="{{ route('home') }}" target="_blank"><i class="las la-globe"></i></a>
                </button>
            </li>
            <li class="dropdown">
                <button type="button" class="primary--layer notification-bell" data-bs-toggle="dropdown" data-display="static"
                    aria-haspopup="true" aria-expanded="false">
                    <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('Unread Notifications')">
                        <i class="las la-bell @if($adminNotificationCount > 0) icon-left-right @endif"></i>
                    </span>
                    @if($adminNotificationCount > 0)
                    <span class="notification-count">{{ $adminNotificationCount <= 9 ? $adminNotificationCount : '9+'}}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu--md p-0 border-0 box--shadow1 dropdown-menu-right">
                    <div class="dropdown-menu__header">
                        <span class="caption">@lang('Notification')</span>
                        @if($adminNotificationCount > 0)
                            <p>@lang('You have') {{ $adminNotificationCount }} @lang('unread notification')</p>
                        @endif
                    </div>
                    <div class="dropdown-menu__body @if(blank($adminNotifications)) d-flex justify-content-center align-items-center @endif">
                        @forelse($adminNotifications as $notification)
                            <a href="{{ route('admin.notification.read',$notification->id) }}"
                                class="dropdown-menu__item">
                                <div class="navbar-notifi">
                                    <div class="navbar-notifi__right">
                                        <h6 class="notifi__title">{{ __($notification->title) }}</h6>
                                        <span class="time"><i class="far fa-clock"></i>
                                            {{ diffForHumans($notification->created_at) }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                        <div class="empty-notification text-center">
                            <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                            <p class="mt-3">@lang('No unread notification found')</p>
                        </div>
                        @endforelse
                    </div>
                    <div class="dropdown-menu__footer">
                        <a href="{{ route('admin.notifications') }}"
                            class="view-all-message">@lang('View all notifications')</a>
                    </div>
                </div>
            </li>
            <li>
                <button type="button" class="primary--layer" data-bs-toggle="tooltip" data-bs-placement="bottom" title="@lang('System Setting')">
                    <a href="{{ route('admin.setting.system') }}"><i class="las la-wrench"></i></a>
                </button>
            </li>
            <li class="dropdown d-flex profile-dropdown">
                <button type="button" data-bs-toggle="dropdown" data-display="static" aria-haspopup="true"
                    aria-expanded="false">
                    <span class="navbar-user">
                        <span class="navbar-user__thumb"><img src="{{ getImage(getFilePath('adminProfile').'/'. auth()->guard('admin')->user()->image,getFileSize('adminProfile'))}}" alt="image"></span>
                        <span class="navbar-user__info">
                            <span class="navbar-user__name">{{ auth()->guard('admin')->user()->username }}</span>
                        </span>
                        <span class="icon"><i class="las la-chevron-circle-down"></i></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu--sm p-0 border-0 box--shadow1 dropdown-menu-right">
                    <a href="{{ route('admin.profile') }}"
                        class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-user-circle"></i>
                        <span class="dropdown-menu__caption">@lang('Profile')</span>
                    </a>

                    <a href="{{ route('admin.password') }}"
                        class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-key"></i>
                        <span class="dropdown-menu__caption">@lang('Password')</span>
                    </a>

                    <a href="{{ route('admin.logout') }}" class="dropdown-menu__item d-flex align-items-center px-3 py-2">
                        <i class="dropdown-menu__icon las la-sign-out-alt"></i>
                        <span class="dropdown-menu__caption">@lang('Logout')</span>
                    </a>
                </div>
                <button type="button" class="breadcrumb-nav-open ms-2 d-none">
                    <i class="las la-sliders-h"></i>
                </button>
            </li>
        </ul>
    </div>
</nav>
<!-- navbar-wrapper end -->

<div class="admin-spotlight" id="adminSpotlight" aria-hidden="true">
    <div class="admin-spotlight__dialog" role="dialog" aria-modal="true" aria-label="@lang('Admin spotlight search')">
        <div class="admin-spotlight__head">
            <div class="spotlight-input-wrap">
                <i class="las la-search"></i>
                <input type="search" class="admin-spotlight__input" id="adminSpotlightInput" name="admin_spotlight_search"
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    placeholder="@lang('Search settings, menus, and admin pages')">
            </div>
        </div>
        <div class="admin-spotlight__results">
            <ul class="search-list admin-spotlight__list" id="adminSpotlightResults"></ul>
        </div>
        <div class="admin-spotlight__footer">
            <span><i class="las la-level-down-alt"></i> @lang('to select')</span>
            <span><kbd><i class="las la-arrow-up"></i></kbd><kbd><i class="las la-arrow-down"></i></kbd> @lang('to navigate')</span>
            <span><kbd>ESC</kbd> @lang('to close')</span>
        </div>
    </div>
</div>

@push('style')
<style>
    .navbar-search-shortcut {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: rgba(255, 255, 255, 0.56);
        padding: 0;
        line-height: 1;
        pointer-events: none;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .navbar-search-shortcut kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 18px;
        padding: 0 5px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        font-family: inherit;
        box-shadow: none;
    }

    .navbar-search-shortcut__plus {
        color: rgba(255, 255, 255, 0.32);
        font-size: 9px;
        font-weight: 600;
    }

    .admin-spotlight {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: block;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        background: rgba(7, 18, 81, 0.34);
        backdrop-filter: blur(1px);
        padding: 20px;
        transition: opacity .24s ease, visibility .24s ease, backdrop-filter .24s ease;
    }

    .admin-spotlight.show {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .admin-spotlight__dialog {
        width: min(560px, 100%);
        margin: 8vh auto 0;
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(70, 52, 255, 0.08);
        box-shadow: 0 24px 80px rgba(7, 18, 81, 0.18), 0 0 0 1px rgba(7, 18, 81, 0.04);
        overflow: hidden;
        transform: translateY(28px) scale(.96);
        opacity: 0;
        transition: transform .28s cubic-bezier(.22, 1, .36, 1), opacity .22s ease;
    }

    .admin-spotlight.show .admin-spotlight__dialog {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .admin-spotlight.is-closing .admin-spotlight__dialog {
        transform: translateY(16px) scale(.97);
        opacity: 0;
    }

    .admin-spotlight__head {
        padding: 14px;
        border-bottom: 1px solid #eef0f3;
        position: relative;
        background: #fff;
    }

    .admin-spotlight__head .spotlight-input-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1.5px solid #d9deea;
        border-radius: 10px;
        padding: 0 14px;
        background: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .admin-spotlight__head .spotlight-input-wrap:focus-within {
        border-color: #4634ff;
        box-shadow: 0 0 0 3px rgba(70, 52, 255, 0.10);
    }

    .admin-spotlight__head i {
        position: static;
        transform: none;
        color: #8090ab;
        font-size: 20px;
        flex-shrink: 0;
    }

    .admin-spotlight__input {
        width: 100%;
        height: 44px;
        border: none;
        outline: 0;
        font-size: 15px;
        border-radius: 0;
        padding: 0 !important;
        box-shadow: none;
        background: transparent;
    }

    .admin-spotlight__input:focus {
        border-color: transparent;
        box-shadow: none;
    }

    .admin-spotlight__results {
        max-height: 48vh;
        overflow: auto;
        background: #f6f7fb;
        scrollbar-width: thin;
        scrollbar-color: rgba(70, 52, 255, 0.25) transparent;
    }

    .admin-spotlight__results::-webkit-scrollbar {
        width: 4px;
    }

    .admin-spotlight__results::-webkit-scrollbar-track {
        background: transparent;
    }

    .admin-spotlight__results::-webkit-scrollbar-thumb {
        background: rgba(70, 52, 255, 0.25);
        border-radius: 999px;
    }

    .admin-spotlight__results::-webkit-scrollbar-thumb:hover {
        background: rgba(70, 52, 255, 0.45);
    }

    .admin-spotlight__results .search-list {
        position: static;
        box-shadow: none;
        max-height: none;
        border-radius: 0;
        min-height: 0;
        background: transparent;
        padding: 6px 0;
    }

    .admin-spotlight__results .search-list li {
        border-bottom: none;
        padding: 3px 0;
    }

    .admin-spotlight__results .search-list li .search-list-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 12px;
        margin: 2px 8px;
        border-radius: 10px;
        transition: all .18s ease;
        text-decoration: none;
        background: #fff;
    }

    .search-item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(70, 52, 255, 0.08);
        color: #4634ff;
        font-size: 18px;
        flex-shrink: 0;
        transition: all .18s ease;
    }

    .search-item-text {
        flex: 1;
        min-width: 0;
    }

    .admin-spotlight__results .search-title {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #1c2740;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-spotlight__results .search-subtitle {
        display: block;
        font-size: 12px;
        color: #8090ab;
        line-height: 1.3;
        margin-top: 1px;
    }

    .search-item-arrow {
        display: flex;
        align-items: center;
        color: #c5cdd8;
        font-size: 14px;
        flex-shrink: 0;
        opacity: 0;
        transform: translateX(-4px);
        transition: all .18s ease;
    }

    /* Hover & Active states */
    .admin-spotlight__results .search-list li.active .search-list-link,
    .admin-spotlight__results .search-list li .search-list-link:hover {
        background: #4634ff;
    }

    .admin-spotlight__results .search-list li.active .search-item-icon,
    .admin-spotlight__results .search-list li .search-list-link:hover .search-item-icon {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .admin-spotlight__results .search-list li.active .search-title,
    .admin-spotlight__results .search-list li .search-list-link:hover .search-title {
        color: #fff;
    }

    .admin-spotlight__results .search-list li.active .search-subtitle,
    .admin-spotlight__results .search-list li .search-list-link:hover .search-subtitle {
        color: rgba(255, 255, 255, 0.65);
    }

    .admin-spotlight__results .search-list li.active .search-item-arrow,
    .admin-spotlight__results .search-list li .search-list-link:hover .search-item-arrow {
        color: rgba(255, 255, 255, 0.7);
        opacity: 1;
        transform: translateX(0);
    }

    .admin-spotlight__footer {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        padding: 10px 20px 12px;
        border-top: 1px solid #eef0f3;
        background: #fafafb;
    }

    .admin-spotlight__footer span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #55627c;
        font-size: 11px;
        font-weight: 600;
    }

    .admin-spotlight__footer i {
        color: #4634ff;
        font-size: 12px;
    }

    .admin-spotlight__footer kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 18px;
        padding: 0 5px;
        border-radius: 4px;
        border: 1px solid rgba(70, 52, 255, 0.14);
        background: rgba(70, 52, 255, 0.08);
        color: #4634ff;
        font-size: 10px;
        font-weight: 700;
        box-shadow: none;
    }

    .admin-spotlight .empty-search {
        padding: 20px 16px 18px;
    }

    .admin-spotlight .empty-search img {
        width: 160px;
        margin-bottom: 10px;
        opacity: .72;
    }

    .admin-spotlight .empty-search p {
        margin-bottom: 0;
        font-size: 13px;
        color: #7b879d !important;
    }

    @media (max-width: 991px) {
        .navbar-search-shortcut {
            display: none;
        }

        .admin-spotlight {
            padding: 12px;
        }

        .admin-spotlight__dialog {
            margin-top: 4vh;
        }
    }
</style>
@endpush

@push('script')
<script>
    "use strict";
    var routes = @php echo json_encode($routesData) @endphp;
    var settings = @php echo json_encode($settings) @endphp;
    var sidenav = @php echo json_encode($sidenav) @endphp;
    var settingsData = Object.assign({}, settings , sidenav);

    $('.navbar__action-list .dropdown-menu').on('click', function(event){
        event.stopPropagation();
    });
</script>
<script>
    "use strict";
    function getEmptyMessage(){
        return `<li class="text-muted">
                <div class="empty-search text-center">
                    <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                    <p class="text-muted">No search result found</p>
                </div>
            </li>`
    }
</script>
<script src="{{ asset('assets/admin/js/search.js') }}"></script>
@endpush
