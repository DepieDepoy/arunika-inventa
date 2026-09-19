<aside class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all">

    {{-- =====================================================
         SIDEBAR HEADER
    ====================================================== --}}
    <div class="sidebar-header d-flex align-items-center justify-content-start">

        <a href="{{ url('/dashboard/home') }}" class="navbar-brand">

            <div class="logo-main vasetra-logo">

                {{-- LOGO NORMAL --}}
                <div class="logo-normal">
                    <img src="{{ asset('assets/images/auth/vasetra.png') }}"
                         alt="VASETRA">
                </div>

                {{-- LOGO MINI --}}
                <div class="logo-mini">
                    <img src="{{ asset('assets/images/auth/v-vasetra.png') }}"
                         alt="VASETRA">
                </div>

            </div>

        </a>

        <div class="sidebar-toggle"
             data-toggle="sidebar"
             data-active="true">

            <i class="icon">

                <svg width="20"
                     height="20"
                     viewBox="0 0 24 24"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg">

                    <path d="M4.25 12.2744L19.25 12.2744"
                          stroke="currentColor"
                          stroke-width="1.5"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>

                    <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976"
                          stroke="currentColor"
                          stroke-width="1.5"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>

                </svg>

            </i>

        </div>

    </div>


    {{-- =====================================================
         SIDEBAR BODY
    ====================================================== --}}
    <div class="sidebar-body pt-0 data-scrollbar">

        <div class="sidebar-list">

            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">


                {{-- =================================================
                     HOME
                ================================================== --}}
                <li class="nav-item static-item">

                    <a class="nav-link static-item disabled"
                       href="#"
                       tabindex="-1">

                        <span class="default-icon">Home</span>
                        <span class="mini-icon">-</span>

                    </a>

                </li>


                {{-- =================================================
                     DASHBOARD
                ================================================== --}}
                <li class="nav-item">

                    <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('dashboard*') ? 'page' : 'false' }}"
                       href="{{ url('/dashboard/home') }}">

                        <i class="icon">

                            <svg width="20"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 xmlns="http://www.w3.org/2000/svg"
                                 class="icon-20">

                                <path opacity="0.4"
                                      d="M16.0756 2H19.4616C20.8639 2 22.0001 3.14585 22.0001 4.55996V7.97452C22.0001 9.38864 20.8639 10.5345 19.4616 10.5345H16.0756C14.6734 10.5345 13.5371 9.38864 13.5371 7.97452V4.55996C13.5371 3.14585 14.6734 2 16.0756 2Z"
                                      fill="currentColor"/>

                                <path fill-rule="evenodd"
                                      clip-rule="evenodd"
                                      d="M4.53852 2H7.92449C9.32676 2 10.463 3.14585 10.463 4.55996V7.97452C10.463 9.38864 9.32676 10.5345 7.92449 10.5345H4.53852C3.13626 10.5345 2 9.38864 2 7.97452V4.55996C2 3.14585 3.13626 2 4.53852 2ZM4.53852 13.4655H7.92449C9.32676 13.4655 10.463 14.6114 10.463 16.0255V19.44C10.463 20.8532 9.32676 22 7.92449 22H4.53852C3.13626 22 2 20.8532 2 19.44V16.0255C2 13.6114 3.13626 13.4655 4.53852 13.4655ZM19.4615 13.4655H16.0755C13.537 13.4655 13.537 14.6114 13.537 16.0255V19.44C13.537 20.8532 14.6732 22 16.0755 22H19.4615C22 22 22 19.44V16.0255C22 13.6114 20.8637 13.4655 19.4615 13.4655Z"
                                      fill="currentColor"/>

                            </svg>

                        </i>

                        <span class="item-name">Dashboard</span>

                    </a>

                </li>


                <li>
                    <hr class="hr-horizontal">
                </li>


                {{-- =================================================
                     PAGES
                ================================================== --}}
                <li class="nav-item static-item">

                    <a class="nav-link static-item disabled"
                       href="#"
                       tabindex="-1">

                        <span class="default-icon">Pages</span>
                        <span class="mini-icon">-</span>

                    </a>

                </li>


                {{-- =================================================
                     MASTER DATA
                ================================================== --}}
                @if(
                    auth()->user()->hasPermission('category.view') ||
                    auth()->user()->hasPermission('subcategory.view') ||
                    auth()->user()->hasPermission('vendor.view')
                )

                    @php
                        $masterDataActive =
                            request()->routeIs('category.*') ||
                            request()->routeIs('subcategory.*') ||
                            request()->routeIs('vendor.*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $masterDataActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-master-data"
                           role="button"
                           aria-expanded="{{ $masterDataActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-master-data">

                            <i class="icon">

                                <svg class="icon-20"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">

                                    <path opacity="0.4"
                                          d="M13.3051 5.88243V6.06547C12.8144 6.05584 12.3237 6.05584 11.8331 6.05584V5.89206C11.8331 5.22733 11.2737 4.68784 10.6064 4.68784H9.63482C8.52589 4.68784 7.62305 3.80152 7.62305 2.72254C7.62305 2.32755 7.95671 2 8.35906 2C8.77123 2 9.09508 2.32755 9.09508 2.72254C9.09508 3.01155 9.34042 3.24276 9.63482 3.24276H10.6064C12.0882 3.2524 13.2953 4.43736 13.3051 5.88243Z"
                                          fill="currentColor"/>

                                    <path fill-rule="evenodd"
                                          clip-rule="evenodd"
                                          d="M15.164 6.08279C15.4791 6.08712 15.7949 6.09145 16.1119 6.09469C19.5172 6.09469 22 8.52241 22 11.875V16.1813C22 19.5339 19.5172 21.9616 16.1119 21.9616C14.7478 21.9905 13.3837 22.0001 12.0098 22.0001C10.6359 22.0001 9.25221 21.9905 7.88813 21.9616C4.48283 21.9616 2 19.5339 2 16.1813V11.875C2 8.52241 4.48283 6.09469 7.89794 6.09469C9.18351 6.07542 10.4985 6.05615 11.8332 6.05615C12.3238 6.05615 12.8145 6.05615 13.3052 6.06579C13.9238 6.06579 14.5425 6.07427 15.164 6.08279Z"
                                          fill="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">Master Data</span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $masterDataActive ? 'show' : '' }}"
                            id="sidebar-master-data"
                            data-bs-parent="#sidebar-menu">


                            {{-- CATEGORY --}}
                            @if(auth()->user()->hasPermission('category.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('category.*') ? 'active' : '' }}"
                                       href="{{ route('category.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">B</i>

                                        <span class="item-name">Category</span>

                                    </a>

                                </li>

                            @endif


                            {{-- SUB CATEGORY --}}
                            @if(auth()->user()->hasPermission('subcategory.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('subcategory.*') ? 'active' : '' }}"
                                       href="{{ route('subcategory.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">B</i>

                                        <span class="item-name">Sub Category</span>

                                    </a>

                                </li>

                            @endif


                            {{-- VENDOR --}}
                            @if(auth()->user()->hasPermission('vendor.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('vendor.*') ? 'active' : '' }}"
                                       href="{{ route('vendor.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">B</i>

                                        <span class="item-name">Vendor</span>

                                    </a>

                                </li>

                            @endif

                        </ul>

                    </li>

                @endif


                {{-- =================================================
                     ASSET
                ================================================== --}}
                @if(
                    auth()->user()->hasPermission('asset.view') ||
                    auth()->user()->hasPermission('asset.import')
                )

                    @php
                        $assetActive = request()->routeIs('assets.*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $assetActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-asset"
                           role="button"
                           aria-expanded="{{ $assetActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-asset">

                            <i class="icon">

                                <svg class="icon-20"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">

                                    <path opacity="0.4"
                                          d="M11.9912 18.6215L5.49945 21.864C5.00921 22.1302 4.39768 21.9525 4.12348 21.4643C4.0434 21.3108 4.00106 21.1402 4 20.9668V13.7087C4 14.4283 4.40573 14.8725 5.47299 15.37L11.9912 18.6215Z"
                                          fill="currentColor"/>

                                    <path fill-rule="evenodd"
                                          clip-rule="evenodd"
                                          d="M8.89526 2H15.0695C17.7773 2 19.9735 3.06605 20 5.79337V20.9668C19.9989 21.1374 19.9565 21.3051 19.8765 21.4554C19.7479 21.7007 19.5259 21.8827 19.2615 21.9598C18.997 22.0368 18.7128 22.0023 18.4741 21.8641L11.9912 18.6215L5.47299 15.3701C4.40573 14.8726 4 14.4284 4 13.7088V5.79337C4 3.06605 6.19625 2 8.89526 2Z"
                                          fill="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">Asset</span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $assetActive ? 'show' : '' }}"
                            id="sidebar-asset"
                            data-bs-parent="#sidebar-menu">


                            {{-- ASSET VIEW --}}
                            @if(auth()->user()->hasPermission('asset.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('assets.index') || request()->routeIs('assets.show') || request()->routeIs('assets.edit') ? 'active' : '' }}"
                                       href="{{ route('assets.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <span class="item-name">View</span>

                                    </a>

                                </li>

                            @endif


                            {{-- ASSET IMPORT --}}
                            @if(auth()->user()->hasPermission('asset.import'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('assets.import*') ? 'active' : '' }}"
                                       href="{{ route('assets.import') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <span class="item-name">Import</span>

                                    </a>

                                </li>

                            @endif

                        </ul>

                    </li>

                @endif


                {{-- =================================================
                     MAINTENANCE
                ================================================== --}}
                @if(
                    auth()->user()->hasPermission('maintenance.view') ||
                    auth()->user()->hasPermission('maintenance.create') ||
                    auth()->user()->hasPermission('maintenance.history')
                )

                    @php
                        $maintenanceActive =
                            request()->routeIs('maintenance.requests.*') ||
                            request()->routeIs('my-assets.*') ||
                            request()->routeIs('maintenance.history');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $maintenanceActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-maintenance"
                           role="button"
                           aria-expanded="{{ $maintenanceActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-maintenance">

                            <i class="icon">

                                <svg class="icon-20"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">

                                    <path d="M21.73 18.27L16.56 13.1C17.09 12.19 17.39 11.12 17.39 9.98C17.39 6.67 14.72 4 11.41 4C10.34 4 9.33 4.28 8.46 4.77L12.12 8.43L10.43 10.12L6.77 6.46C6.28 7.33 6 8.34 6 9.41C6 12.72 8.67 15.39 11.98 15.39C13.12 15.39 14.19 15.09 15.1 14.56L20.27 19.73C20.66 20.12 21.29 20.12 21.68 19.73C22.07 19.34 22.07 18.66 21.73 18.27Z"
                                          fill="currentColor"/>

                                    <path opacity="0.4"
                                          d="M5.5 18.5C5.5 19.3284 4.82843 20 4 20C3.17157 20 2.5 19.3284 2.5 18.5C2.5 17.6716 3.17157 17 3.5 18.5C3.5 17.6716 4.17157 17 5 17C5.82843 17 6.5 17.6716 6.5 18.5Z"
                                          fill="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">
                                Maintenance
                            </span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $maintenanceActive ? 'show' : '' }}"
                            id="sidebar-maintenance"
                            data-bs-parent="#sidebar-menu">


                            {{-- =================================================
                                 MAINTENANCE REQUESTS
                            ================================================== --}}
                            @if(auth()->user()->hasPermission('maintenance.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('maintenance.requests.*') ? 'active' : '' }}"
                                       href="{{ route('maintenance.requests.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">R</i>

                                        <span class="item-name">
                                            Maintenance Requests
                                        </span>

                                    </a>

                                </li>

                            @endif


                            {{-- =================================================
                                 MY ASSETS
                            ================================================== --}}
                            @if(auth()->user()->hasPermission('maintenance.create'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('my-assets.*') ? 'active' : '' }}"
                                       href="{{ route('my-assets.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">A</i>

                                        <span class="item-name">
                                            My Assets
                                        </span>

                                    </a>

                                </li>

                            @endif


                            {{-- =================================================
                                 MAINTENANCE HISTORY
                            ================================================== --}}
                            @if(auth()->user()->hasPermission('maintenance.history'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('maintenance.history') ? 'active' : '' }}"
                                       href="{{ route('maintenance.history') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">H</i>

                                        <span class="item-name">
                                            Maintenance History
                                        </span>

                                    </a>

                                </li>

                            @endif


                        </ul>

                    </li>

                @endif


                {{-- =================================================
                     HISTORY
                ================================================== --}}
                @if(auth()->user()->hasPermission('history.view'))

                    @php
                        $historyActive = request()->routeIs('import.history*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $historyActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-history"
                           role="button"
                           aria-expanded="{{ $historyActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-history">

                            <i class="icon">

                                <svg class="icon-20"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none">

                                    <path d="M2 5C2 4.44772 2.44772 4 3 4H8.66667H21C21.5523 4 22 4.44772 22 5V8H15.3333H8.66667H2V5Z"
                                          fill="currentColor"
                                          stroke="currentColor"/>

                                    <path d="M6 8H2V11M6 8V20M6 8H14M6 20H3C2.44772 20 2 19.5523 2 19V11M6 20H14M14 8H22V11M14 8V20M14 20H21C21.5523 20 22 19.5523 22 19V11M2 11H22M2 14H22M2 17H22M10 8V20M18 8V20"
                                          stroke="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">History</span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $historyActive ? 'show' : '' }}"
                            id="sidebar-history"
                            data-bs-parent="#sidebar-menu">

                            <li class="nav-item">

                                <a class="nav-link {{ request()->routeIs('import.history*') ? 'active' : '' }}"
                                   href="{{ route('import.history') }}">

                                    <i class="icon">

                                        <svg class="icon-10"
                                             xmlns="http://www.w3.org/2000/svg"
                                             width="10"
                                             viewBox="0 0 24 24"
                                             fill="currentColor">

                                            <circle cx="12"
                                                    cy="12"
                                                    r="8"
                                                    fill="currentColor"/>

                                        </svg>

                                    </i>

                                    <i class="sidenav-mini-icon">D</i>

                                    <span class="item-name">Import</span>

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


                {{-- =================================================
                     SUBSCRIPTION
                ================================================== --}}
                @if(
                    auth()->user()->hasPermission('subscription.view') ||
                    auth()->user()->hasPermission('subscription.create') ||
                    auth()->user()->hasPermission('subscription.history')
                )

                    @php
                        $subscriptionActive =
                            request()->routeIs('subscription.*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $subscriptionActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-subscription"
                           role="button"
                           aria-expanded="{{ $subscriptionActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-subscription">

                            <i class="icon">

                                <svg class="icon-20"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">

                                    <path d="M3 7.5C3 6.67157 3.67157 6 4.5 6H19.5C20.3284 6 21 6.67157 21 7.5V18.5C21 19.3284 20.3284 20 19.5 20H4.5C3.67157 20 3 19.3284 3 18.5V7.5Z"
                                          fill="currentColor"/>

                                    <path opacity="0.4"
                                          d="M3 8V5.5C3 4.67157 3.67157 4 4.5 4H17.5C18.3284 4 19 4.67157 19 4.5V6H4.5C3.67157 6 3 6.67157 3 7.5V8Z"
                                          fill="currentColor"/>

                                    <path d="M16 13C16 12.4477 16.4477 12 17 12H21V16H17C16.4477 16 16 15.5523 16 15V13Z"
                                          fill="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">
                                Subscription
                            </span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $subscriptionActive ? 'show' : '' }}"
                            id="sidebar-subscription"
                            data-bs-parent="#sidebar-menu">


                            {{-- =================================================
                                 SUBSCRIBE
                            ================================================== --}}
                            @if(
                                auth()->user()->hasPermission('subscription.view') ||
                                auth()->user()->hasPermission('subscription.create')
                            )

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('subscription.index') ? 'active' : '' }}"
                                       href="{{ route('subscription.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">S</i>

                                        <span class="item-name">
                                            Subscribe
                                        </span>

                                    </a>

                                </li>

                            @endif


                            {{-- =================================================
                                 SUBSCRIPTION HISTORY
                            ================================================== --}}
                            @if(auth()->user()->hasPermission('subscription.history'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('subscription.history') ? 'active' : '' }}"
                                       href="{{ route('subscription.history') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">H</i>

                                        <span class="item-name">
                                            History
                                        </span>

                                    </a>

                                </li>

                            @endif


                        </ul>

                    </li>

                @endif


                <li>
                    <hr class="hr-horizontal">
                </li>


                {{-- =================================================
                     MASTER
                ================================================== --}}
                <li class="nav-item static-item">

                    <a class="nav-link static-item disabled"
                       href="#"
                       tabindex="-1">

                        <span class="default-icon">Master</span>
                        <span class="mini-icon">-</span>

                    </a>

                </li>


                {{-- =================================================
                     ROLE
                ================================================== --}}
                @if(auth()->user()->hasPermission('role.view'))

                    @php
                        $roleActive = request()->routeIs('roles.*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $roleActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-role"
                           role="button"
                           aria-expanded="{{ $roleActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-role">

                            <i class="icon">

                                <svg class="icon-20"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none">

                                    <path d="M2 5C2 4.44772 2.44772 3 4 3H8.66667H21C21.5523 3 22 3.44772 22 4V8H15.3333H8.66667H2V5Z"
                                          fill="currentColor"
                                          stroke="currentColor"/>

                                    <path d="M6 8H2V11M6 8V20M6 8H3C2.44772 20 2 19.5523 2 19V11M6 20H14M14 8H22V11M14 8V20M14 20H21C21.5523 20 22 19.5523 22 19V11M2 11H22M2 14H22M2 17H22M10 8V20M18 8V20"
                                          stroke="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">Role</span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $roleActive ? 'show' : '' }}"
                            id="sidebar-role"
                            data-bs-parent="#sidebar-menu">

                            <li class="nav-item">

                                <a class="nav-link {{ request()->routeIs('roles.index') ? 'active' : '' }}"
                                   href="{{ route('roles.index') }}">

                                    <i class="icon">

                                        <svg class="icon-10"
                                             xmlns="http://www.w3.org/2000/svg"
                                             width="10"
                                             viewBox="0 0 24 24"
                                             fill="currentColor">

                                            <circle cx="12"
                                                    cy="12"
                                                    r="8"
                                                    fill="currentColor"/>

                                        </svg>

                                    </i>

                                    <i class="sidenav-mini-icon">D</i>

                                    <span class="item-name">View</span>

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


                {{-- =================================================
                     USERS
                ================================================== --}}
                @if(auth()->user()->hasPermission('user.view'))

                    @php
                        $userActive = request()->routeIs('users.*');
                    @endphp

                    <li class="nav-item">

                        <a class="nav-link {{ $userActive ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#sidebar-users"
                           role="button"
                           aria-expanded="{{ $userActive ? 'true' : 'false' }}"
                           aria-controls="sidebar-users">

                            <i class="icon">

                                <svg class="icon-20"
                                     width="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">

                                    <path d="M11.9488 14.54C8.49884 14.54 5.58789 15.1038 5.58789 17.2795C5.58789 19.4562 8.51765 20.0001 11.9488 20.0001C15.3988 20.0001 18.3098 19.4364 18.3098 17.2606C18.3098 15.084 15.38 14.54 11.9488 14.54Z"
                                          fill="currentColor"/>

                                    <path opacity="0.4"
                                          d="M11.949 12.467C14.2851 12.467 16.1583 10.5831 16.1583 8.23351C16.1583 5.88306 14.2851 4 11.949 4C9.61293 4 7.73975 5.88306 7.73975 8.23351C7.73975 10.5831 9.61293 12.467 11.949 12.467Z"
                                          fill="currentColor"/>

                                </svg>

                            </i>

                            <span class="item-name">Users</span>

                            <i class="right-icon">

                                <svg class="icon-18"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="18"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </i>

                        </a>


                        <ul class="sub-nav collapse {{ $userActive ? 'show' : '' }}"
                            id="sidebar-users"
                            data-bs-parent="#sidebar-menu">


                            {{-- USERS VIEW --}}
                            @if(auth()->user()->hasPermission('user.view'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}"
                                       href="{{ route('users.index') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <i class="sidenav-mini-icon">D</i>

                                        <span class="item-name">View</span>

                                    </a>

                                </li>

                            @endif


                            {{-- USERS IMPORT --}}
                            @if(auth()->user()->hasPermission('user.import'))

                                <li class="nav-item">

                                    <a class="nav-link {{ request()->routeIs('users.import*') ? 'active' : '' }}"
                                       href="{{ route('users.import') }}">

                                        <i class="icon">

                                            <svg class="icon-10"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 width="10"
                                                 viewBox="0 0 24 24"
                                                 fill="currentColor">

                                                <circle cx="12"
                                                        cy="12"
                                                        r="8"
                                                        fill="currentColor"/>

                                            </svg>

                                        </i>

                                        <span class="item-name">Import</span>

                                    </a>

                                </li>

                            @endif

                        </ul>

                    </li>

                @endif


            </ul>

        </div>

    </div>


    {{-- =====================================================
         SIDEBAR FOOTER
    ====================================================== --}}
    <div class="sidebar-footer"></div>

</aside>