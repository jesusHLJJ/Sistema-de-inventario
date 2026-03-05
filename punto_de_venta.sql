-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-03-2026 a las 07:20:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `punto_de_venta`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id_caja` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `dinero_inicial` decimal(10,2) NOT NULL,
  `total_ventas` decimal(10,2) DEFAULT 0.00,
  `total_final` decimal(10,2) DEFAULT 0.00,
  `estatus` enum('abierta','cerrada') DEFAULT 'abierta',
  `id_empleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`id_caja`, `fecha_apertura`, `fecha_cierre`, `dinero_inicial`, `total_ventas`, `total_final`, `estatus`, `id_empleado`) VALUES
(1, '2026-02-13 15:52:32', '2026-02-13 21:42:15', 200.00, 180.00, 380.00, 'cerrada', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_venta`
--

CREATE TABLE `detalles_venta` (
  `id_detalles_venta` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_producto` varchar(50) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `cantidad` decimal(10,3) DEFAULT NULL,
  `totalproducto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_venta`
--

INSERT INTO `detalles_venta` (`id_detalles_venta`, `id_venta`, `id_producto`, `id_empleado`, `cantidad`, `totalproducto`) VALUES
(1, 2, '793573264107', 7, 1.000, 14.00),
(2, 3, '793573264107', 7, 2.000, 28.00),
(3, 4, '793573264107', 7, 1.000, 14.00),
(4, 5, '793573264107', 7, 1.000, 14.00),
(5, 7, '793573264107', 7, 1.000, 14.00),
(6, 7, '793573264107', 7, 1.000, 14.00),
(7, 8, '793573264107', 7, 3.000, 42.00),
(8, 9, '7501032922856', 7, 1.000, 50.00),
(9, 10, '7501032922856', 7, 1.000, 50.00),
(10, 10, '793573264107', 7, 2.000, 28.00),
(11, 11, '7501032922856', 7, 1.000, 50.00),
(12, 12, '793573264107', 7, 1.000, 14.00),
(13, 13, '793573264107', 7, 1.000, 14.00),
(14, 14, '793573264107', 7, 2.000, 28.00),
(15, 16, '7501032922856', 7, 1.000, 50.00),
(16, 16, '7501032922856', 7, 2.000, 100.00),
(17, 20, '7501032922856', 7, 1.000, 50.00),
(18, 22, '7501032922856', 7, 1.000, 50.00),
(19, 23, '7501032922856', 7, 1.000, 50.00),
(20, 24, '7501032922856', 7, 1.000, 50.00),
(22, 26, '793573264107', 7, 1.000, 15.00),
(23, 27, '793573264107', 7, 1.000, 15.00),
(24, 27, '7501032922856', 7, 1.000, 50.00),
(25, 27, '793573264107', 7, 1.000, 15.00),
(26, 28, '793573264107', 7, 1.000, 15.00),
(27, 28, '7501032922856', 7, 1.000, 50.00),
(28, 28, '793573264107', 7, 1.000, 15.00),
(29, 28, '7501032922856', 7, 1.000, 50.00),
(30, 29, '793573264107', 7, 1.000, 15.00),
(32, 30, '793573264107', 7, 1.000, 15.00),
(33, 31, '793573264107', 7, 1.000, 15.00),
(34, 32, '793573264107', 7, 1.000, 15.00),
(35, 33, '793573264107', 7, 1.000, 15.00),
(36, 34, '793573264107', 7, 1.000, 15.00),
(37, 35, '793573264107', 7, 1.000, 15.00),
(38, 36, '793573264104', 7, 0.250, 10.00),
(39, 37, '793573264107', 7, 1.000, 15.00),
(40, 38, '793573264107', 7, 2.000, 30.00),
(41, 39, '793573264107', 7, 1.000, 15.00),
(42, 40, '793573264104', 7, 0.250, 10.00),
(43, 41, '793573264107', 7, 1.000, 15.00),
(44, 42, '793573264104', 7, 0.250, 10.00),
(45, 43, '793573264107', 7, 1.000, 15.00),
(46, 44, '793573264107', 7, 1.000, 15.00),
(47, 45, '793573264107', 7, 1.000, 15.00),
(48, 46, '793573264107', 7, 1.000, 15.00),
(49, 47, '793573264107', 7, 1.000, 15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` varchar(50) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_compra` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_compra`, `subtotal`) VALUES
(1, 1, '793573264107', 10.00, 0.00, 0.00),
(2, 1, '7501032922856', 2.00, 0.00, 0.00),
(3, 6, '793573264104', 1.00, 1.00, 0.00),
(4, 7, '2222', 10.00, 20.00, 0.00),
(5, 8, '123654', 10.00, 20.00, 0.00),
(6, 9, '793573264104', 10.00, 30.00, 0.00),
(7, 9, '222222', 10.00, 50.00, 0.00),
(8, 10, '123654', 10.00, 20.00, 0.00),
(9, 11, '1111111111111', 10.00, 10.00, 0.00),
(10, 11, '793573264104', 10.00, 20.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `ap_paterno` varchar(100) DEFAULT NULL,
  `ap_materno` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `correo` text NOT NULL,
  `pass` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`id_empleado`, `nombre`, `ap_paterno`, `ap_materno`, `edad`, `sexo`, `correo`, `pass`) VALUES
(3, 'JESUS', 'HERNANDEZ', 'LUIS', 22, 'M', 'Prueba@prueba.com', '$2y$10$1pM59IhQGyDfFTtVNGa2LuCcSGmOPxGc0cpb6rmcWLZTvD1BbjuBe'),
(6, 'paty', 'fernandez', 'Ruiz', 40, 'F', 'paty@gmail.com', '$2y$10$AhSL8oEC/Lu.EhGU3YXKbehN4Zpmw0gf9fiAEMrV7ioYjWQBbgG6K'),
(7, 'si', 'no', 's', 22, 'M', 'jesus@jesus.com', '$2y$10$U/zHGoZORzLoeJdJRF1VleIyT7rWsu32VYC4Tz8LtGcMUfln12G/S');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus`
--

CREATE TABLE `estatus` (
  `id_estatus` int(11) NOT NULL,
  `estatus` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estatus`
--

INSERT INTO `estatus` (`id_estatus`, `estatus`) VALUES
(1, 'En proceso'),
(2, 'Finalizada'),
(3, 'Cancelada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `proveedor` varchar(30) DEFAULT NULL,
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  `total_pago` decimal(10,2) DEFAULT 0.00,
  `estatus` enum('Pendiente','Recibido','cancelado') DEFAULT 'Pendiente',
  `id_empleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `proveedor`, `fecha_pedido`, `fecha_entrega`, `total_pago`, `estatus`, `id_empleado`) VALUES
(1, 'Jarritos', '2026-02-13 22:31:50', '2026-02-13 23:31:34', 0.00, 'Recibido', 7),
(6, 'dfdf', '2026-02-13 22:53:09', NULL, 0.00, 'Pendiente', 7),
(7, 'Jarritos', '2026-02-13 22:59:19', NULL, 200.00, 'Pendiente', 7),
(8, 'dfdf', '2026-02-13 23:01:29', NULL, 20.00, 'Pendiente', 7),
(9, 'bimbo', '2026-02-13 23:02:00', NULL, 80.00, 'Pendiente', 7),
(10, 'sdsd', '2026-02-13 23:08:58', '2026-02-13 23:30:06', 20.00, 'Recibido', 7),
(11, 'dfdf', '2026-02-13 23:11:42', '2026-02-13 23:24:39', 30.00, 'Recibido', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` varchar(50) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `piezas` decimal(10,3) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `marca`, `nombre`, `contenido`, `piezas`, `precio`) VALUES
('037836040306', 'Grisi', 'Crema corporal neutro', '400ml', 10.000, 55.00),
('1111111111111', 'Regio', 'Papel higiénico regio (SUELTO)', '1 ROLLO ', 10.000, 13.00),
('123654', 'Prueba', 'Prueba', '1', 12.000, 1.00),
('2222', 'Gamesa', 'Chokis', '25g', 10.000, 20.00),
('222222', 'Gamesa', 'Emperador', '20g', 4.000, 19.00),
('54125', 'Lala', 'Leche entera', '1L', 6.000, 29.00),
('55555', 'a', 'add', '5', 5.000, 2.00),
('7501032922856', 'glade', 'cubo', '180g', 4.000, 50.00),
('7501032922859', 'RedCola', 'RedCola', '500ml', 10.000, 16.00),
('793573264091', 'Skartch', 'Agua Skartch', '600ml', 3.000, 10.00),
('793573264104', 'SABR', 'Huevo', '1', 9.750, 40.00),
('793573264107', 'Skartch ', 'Agua skartch', '1.5L', 11.000, 15.00),
('999', 'asssd', 'sdsd', '50', 1.000, 100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_estatus` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`id_venta`, `fecha`, `id_estatus`, `monto`) VALUES
(2, '2025-12-14 00:00:00', 2, 14.00),
(3, '2025-12-14 00:00:00', 2, 28.00),
(4, '2025-12-14 00:00:00', 2, 14.00),
(5, '2025-12-14 00:00:00', 2, 14.00),
(6, '2025-12-14 00:00:00', 1, 0.00),
(7, '2025-12-14 00:00:00', 2, 28.00),
(8, '2025-12-14 00:00:00', 2, 42.00),
(9, '2025-12-14 00:00:00', 2, 50.00),
(10, '2025-12-14 00:00:00', 2, 78.00),
(11, '2025-12-14 00:00:00', 2, 50.00),
(12, '2025-12-14 00:00:00', 2, 14.00),
(13, '2025-12-14 00:00:00', 2, 14.00),
(14, '2025-12-14 00:00:00', 2, 28.00),
(15, '2025-12-14 00:00:00', 1, 0.00),
(16, '2025-12-14 00:00:00', 2, 150.00),
(17, '2025-12-14 00:00:00', 1, 0.00),
(18, '2025-12-15 00:00:00', 1, 0.00),
(19, '2025-12-15 00:00:00', 1, 0.00),
(20, '2025-12-28 00:00:00', 2, 50.00),
(21, '2025-12-28 00:00:00', 1, 0.00),
(22, '2025-12-28 00:00:00', 1, 50.00),
(23, '2025-12-28 00:00:00', 2, 50.00),
(24, '2025-12-28 00:00:00', 1, 50.00),
(25, '2025-12-28 00:00:00', 1, 0.00),
(26, '2026-01-20 00:00:00', 2, 15.00),
(27, '2026-01-20 00:00:00', 2, 80.00),
(28, '2026-01-20 00:00:00', 2, 130.00),
(29, '2026-01-20 00:00:00', 2, 15.00),
(30, '2026-01-20 00:00:00', 2, 15.00),
(31, '2026-01-20 00:00:00', 2, 15.00),
(32, '2026-01-20 00:00:00', 2, 15.00),
(33, '2026-01-20 00:00:00', 2, 15.00),
(34, '2026-01-20 00:00:00', 3, 15.00),
(35, '2026-01-20 00:00:00', 2, 15.00),
(36, '2026-02-13 00:00:00', 2, 10.00),
(37, '2026-02-13 00:00:00', 2, 15.00),
(38, '2026-02-13 00:00:00', 2, 30.00),
(39, '2026-02-13 00:00:00', 2, 15.00),
(40, '2026-02-13 00:00:00', 2, 10.00),
(41, '2026-02-13 00:00:00', 2, 15.00),
(42, '2026-02-13 00:00:00', 2, 10.00),
(43, '2026-02-13 00:00:00', 2, 15.00),
(44, '2026-02-13 00:00:00', 2, 15.00),
(45, '2026-02-13 00:00:00', 2, 15.00),
(46, '2026-02-13 00:00:00', 2, 15.00),
(47, '2026-02-13 16:12:36', 2, 15.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id_caja`),
  ADD KEY `fk_caja_empleado` (`id_empleado`);

--
-- Indices de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD PRIMARY KEY (`id_detalles_venta`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_empleado` (`id_empleado`),
  ADD KEY `detalles_venta_ibfk_2` (`id_producto`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`);

--
-- Indices de la tabla `estatus`
--
ALTER TABLE `estatus`
  ADD PRIMARY KEY (`id_estatus`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedido_empleado` (`id_empleado`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_estatus` (`id_estatus`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id_caja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  MODIFY `id_detalles_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `estatus`
--
ALTER TABLE `estatus`
  MODIFY `id_estatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja`
--
ALTER TABLE `caja`
  ADD CONSTRAINT `fk_caja_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD CONSTRAINT `detalles_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  ADD CONSTRAINT `detalles_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  ADD CONSTRAINT `detalles_venta_ibfk_3` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`);

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`id_estatus`) REFERENCES `estatus` (`id_estatus`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
