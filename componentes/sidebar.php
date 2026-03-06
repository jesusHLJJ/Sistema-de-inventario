<aside id="sidebar" class="sidebar p-3 text-white bg-dark collapse d-md-flex flex-column flex-shrink-0" style="width: 280px; height: 100vh;">

    <a href="home.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="bi bi-box-seam fs-4 me-2"></i>
        <span class="fs-4 fw-bold">Inventario</span>
    </a>

    <hr>

    <ul class="nav nav-pills flex-column mb-auto">

        <li class="nav-item">
            <a href="home.php" class="nav-link <?= ($pagina == 'home') ? 'active' : 'text-white' ?>">
                <i class="bi bi-house me-2"></i> Inicio
            </a>
        </li>

        <li>
            <a href="../controladores/perfil.php" class="nav-link <?= ($pagina == 'perfil') ? 'active' : 'text-white' ?>">
                <i class="bi bi-person-circle me-2"></i> Perfil
            </a>
        </li>

        <li>
            <a href="agregar_producto.php" class="nav-link <?= ($pagina == 'agregar_producto') ? 'active' : 'text-white' ?>">
                <i class="bi bi-plus-circle me-2"></i> Agregar Producto
            </a>
        </li>

        <li>
            <a href="ventas.php" class="nav-link <?= ($pagina == 'ventas') ? 'active' : 'text-white' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Ver Ventas
            </a>
        </li>

        <li>
            <a href="venta_nueva.php" class="nav-link <?= ($pagina == 'venta_nueva') ? 'active' : 'text-white' ?>">
                <i class="bi bi-cart-plus me-2"></i> Nueva Venta
            </a>
        </li>

        <li>
            <a href="pedidos.php" class="nav-link <?= ($pagina == 'pedidos') ? 'active' : 'text-white' ?>">
                <i class="bi bi-box me-2"></i> Pedidos
            </a>
        </li>
        <li>
            <a href="analisis.php" class="nav-link <?= ($pagina == 'analisis') ? 'active' : 'text-white' ?>">
                <i class="bi bi-graph-up-arrow me-2"></i> Rendimiento
            </a>
        </li>
        <li class="nav-item d-md-none mt-3">
            <a href="logout.php" class="nav-link text-white bg-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
            </a>
        </li>
    </ul>

    <hr>

    <div class="dropdown d-none d-md-block">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
            id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://cdn-icons-png.flaticon.com/512/2541/2541986.png"
                alt="mdo" width="32" height="32" class="rounded-circle me-2">
            <strong><?= isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario' ?></strong>
        </a>

        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="../controladores/perfil.php">Perfil</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
        </ul>
    </div>


</aside>