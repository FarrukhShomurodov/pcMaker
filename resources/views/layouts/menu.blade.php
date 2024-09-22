<div class="main-menu-area mg-tb-40">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <ul class="nav nav-tabs notika-menu-wrap menu-it-icon-pro">

                    @php
                        $currentRouteName = Route::currentRouteName() ?? 'product';
                        $productRouteNames = [
                            'product.items.index',
                            'product.edit',
                            'product.update',
                            'product.category.index',
                            'product.category.edit',
                            'product.category.update',
                            'product.sub-category.index',
                            'product.sub-category.edit',
                            'product.sub-category.update'
                        ];

                        $componentRouteNames = [
                            'component.items.index',
                            'component.edit',
                            'component.update',
                            'component.category.index',
                            'component.category.edit',
                            'component.category.update',
                            'component.type.index',
                            'component.type.edit',
                            'component.type.update',
                            'component.compatibility.index',
                            'component.compatibility.edit',
                            'component.compatibility.update'
                        ];
                    @endphp

                    <li @if(in_array($currentRouteName, $productRouteNames)) class="active" @endif>
                        <a data-toggle="tab" href="#products"><i class="fa-solid fa-box"></i> Продукты</a>
                    </li>
                    <li @if(in_array($currentRouteName, $componentRouteNames)) class="active" @endif>
                        <a data-toggle="tab" href="#components"><i class="fa-solid fa-cogs"></i> Компоненты</a>
                    </li>
                    <li><a data-toggle="tab" href="#client_assemblies"><i class="fa-solid fa-users"></i> Сборки клиента</a>
                    </li>
                    <li @if($currentRouteName == 'admin-assembly') class="active" @endif>
                        <a data-toggle="tab" href="#admin_assemblies"><i class="fa-solid fa-screwdriver-wrench"></i>
                            Сборки Админа</a>
                    </li>
                    <li><a data-toggle="tab" href="#orders"><i class="fa-solid fa-shopping-cart"></i> Заказы</a></li>
                </ul>
                <div class="tab-content custom-menu-content">
                    <div id="products"
                         class="tab-pane notika-tab-menu-bg animated fade @if(in_array($currentRouteName, $productRouteNames)) active in @endif ">
                        <ul class="notika-main-menu-dropdown">
                            <li><a href="{{ route('product.category.index') }}">Категория</a>
                            </li>
                            <li><a href="{{ route('product.sub-category.index') }}">Под Категория</a>
                            </li>
                            <li><a href="{{ route('product.items.index') }}">Продукт</a>
                            </li>
                        </ul>
                    </div>
                    <div id="components"
                         class="tab-pane notika-tab-menu-bg animated fade @if(in_array($currentRouteName, $componentRouteNames)) active in @endif ">
                        <ul class="notika-main-menu-dropdown">
                            <li><a href="{{ route('component.category.index') }}">Категория</a>
                            </li>
                            <li><a href="{{ route('component.type.index') }}">Типы</a>
                            </li>
                            <li><a href="{{ route('component.items.index') }}">Компоненты</a>
                            </li>
                            <li><a href="{{ route('component.compatibility.index') }}">Совместимости</a>
                            </li>
                        </ul>
                    </div>
                    <div id="client_assemblies" class="tab-pane notika-tab-menu-bg animated fade">
                        <ul class="notika-main-menu-dropdown">
                            <li><a href="#">Собраные</a>
                            </li>
                            <li><a href="#">Новые</a>
                            </li>
                        </ul>
                    </div>
                    <div id="admin_assemblies" class="tab-pane notika-tab-menu-bg animated fade">
                        <ul class="notika-main-menu-dropdown">
                            <li><a href="{{ route('admin-assembly.create') }}">Создать</a>
                            </li>
                            <li><a href="{{ route('admin-assembly.index') }}">Сборки</a>
                            </li>
                        </ul>
                    </div>
                    <div id="orders" class="tab-pane notika-tab-menu-bg animated fade">
                        <ul class="notika-main-menu-dropdown">
                            <li><a href="#">Все</a>
                            </li>
                            <li><a href="#">Продукты</a>
                            </li>
                            <li><a href="#">Сборки клиента</a>
                            </li>
                            <li><a href="#">Сборки админа</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
