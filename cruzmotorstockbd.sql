-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 01-10-2025 a las 13:23:18
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cruzmotorstockbd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ajustesinventario`
--

CREATE TABLE `ajustesinventario` (
  `id_ajuste` int NOT NULL,
  `id_producto` int NOT NULL,
  `tipo` enum('aumento','disminucion') NOT NULL,
  `cantidad` int NOT NULL,
  `motivo` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id_alerta` int NOT NULL,
  `tipo` enum('stock_bajo','sin_stock','sistema','otro') NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','resuelta') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backupsistema`
--

CREATE TABLE `backupsistema` (
  `id_backup` int NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `realizado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) DEFAULT NULL,
  `dni_ruc` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `tipo_documento` enum('dni','ruc','pasaporte','carnet_extranjeria') DEFAULT 'dni',
  `numero_documento` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text,
  `distrito` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombres`, `apellidos`, `nombre`, `apellido`, `dni_ruc`, `telefono`, `correo`, `estado`, `tipo_documento`, `numero_documento`, `email`, `direccion`, `distrito`, `provincia`, `departamento`, `fecha_nacimiento`, `fecha_registro`) VALUES
(1, 'brayan', 'tuco', 'brayan', 'tuco', NULL, '9844545', NULL, 'activo', 'dni', '48485454', '', 'puno', 'puno', 'puno', 'Puno', '1984-08-19', '2025-09-23 16:22:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracionalmacen`
--

CREATE TABLE `configuracionalmacen` (
  `id_config` int NOT NULL,
  `nombre_almacen` varchar(100) NOT NULL,
  `capacidad_maxima` int DEFAULT NULL,
  `horario_apertura` time DEFAULT NULL,
  `horario_cierre` time DEFAULT NULL,
  `responsable` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id_cotizacion` int NOT NULL,
  `id_cliente` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) DEFAULT '0.00',
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id_cotizacion`, `id_cliente`, `fecha`, `total`, `estado`, `observaciones`) VALUES
(1, 1, '2025-09-23 00:00:00', 200.00, 'rechazada', ''),
(2, 1, '2025-09-23 00:00:00', 200.00, 'rechazada', ''),
(3, 1, '2025-09-23 00:00:00', 222.00, 'rechazada', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallecotizacion`
--

CREATE TABLE `detallecotizacion` (
  `id_detalle` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_producto` int DEFAULT NULL,
  `descripcion_servicio` varchar(255) DEFAULT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleinventario`
--

CREATE TABLE `detalleinventario` (
  `id_detalle` int NOT NULL,
  `id_inventario` int NOT NULL,
  `id_producto` int NOT NULL,
  `stock_fisico` int NOT NULL,
  `diferencia` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradasproductos`
--

CREATE TABLE `entradasproductos` (
  `id_entrada` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historialcambios`
--

CREATE TABLE `historialcambios` (
  `id_cambio` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `campo_modificado` varchar(100) DEFAULT NULL,
  `valor_anterior` text,
  `valor_nuevo` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventarioconteo`
--

CREATE TABLE `inventarioconteo` (
  `id_inventario` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logssistema`
--

CREATE TABLE `logssistema` (
  `id_log` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientosistema`
--

CREATE TABLE `mantenimientosistema` (
  `id_parametro` int NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int NOT NULL,
  `id_alerta` int DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('web','app','email') DEFAULT 'web',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacionesusuarios`
--

CREATE TABLE `notificacionesusuarios` (
  `id_notificacion_usuario` int NOT NULL,
  `id_notificacion` int NOT NULL,
  `id_usuario` int NOT NULL,
  `leida` tinyint(1) DEFAULT '0',
  `fecha_leida` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenestrabajo`
--

CREATE TABLE `ordenestrabajo` (
  `id_orden` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_vehiculo` int DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('abierta','en_proceso','cerrada') DEFAULT 'abierta',
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int NOT NULL,
  `id_rol` int NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `puede_leer` tinyint(1) DEFAULT '1',
  `puede_escribir` tinyint(1) DEFAULT '0',
  `puede_eliminar` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int NOT NULL,
  `id_subcategoria` int NOT NULL,
  `id_unidad` int NOT NULL,
  `id_marca` int DEFAULT NULL,
  `id_ubicacion` int DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `stock_actual` int DEFAULT '0',
  `stock_minimo` int DEFAULT '0',
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `id_subcategoria`, `id_unidad`, `id_marca`, `id_ubicacion`, `nombre`, `descripcion`, `codigo_barras`, `precio_unitario`, `stock_actual`, `stock_minimo`, `estado`) VALUES
(2, 2, 1, 0, 0, 'AIR FILTER (STP)', 'FILTRO DE AIRE', '995241', 20.00, 1, 5, 'activo'),
(3, 1, 1, 1, NULL, 'Producto de Prueba', NULL, NULL, 100.50, 50, 10, 'activo'),
(4, 1, 1, 1, NULL, 'Producto de Prueba', NULL, NULL, 100.50, 50, 10, 'activo'),
(5, 1, 1, 1, NULL, 'Producto de Prueba', NULL, NULL, 100.50, 50, 10, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registrosstock`
--

CREATE TABLE `registrosstock` (
  `id_registro` int NOT NULL,
  `id_producto` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `origen` varchar(50) DEFAULT NULL,
  `referencia_id` int DEFAULT NULL,
  `id_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporteconsumo`
--

CREATE TABLE `reporteconsumo` (
  `id_reporte_consumo` int NOT NULL,
  `id_reporte` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad_total` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportemovimientos`
--

CREATE TABLE `reportemovimientos` (
  `id_reporte_movimiento` int NOT NULL,
  `id_reporte` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` int NOT NULL,
  `tipo` enum('stock','movimientos','consumo','personalizado') NOT NULL,
  `periodo` varchar(50) DEFAULT NULL,
  `fecha_generado` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportestock`
--

CREATE TABLE `reportestock` (
  `id_reporte_stock` int NOT NULL,
  `id_reporte` int NOT NULL,
  `id_producto` int NOT NULL,
  `stock_actual` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_usuarios`
--

CREATE TABLE `roles_usuarios` (
  `id_rol` int NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `roles_usuarios`
--

INSERT INTO `roles_usuarios` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Rol con todos los permisos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidasproductos`
--

CREATE TABLE `salidasproductos` (
  `id_salida` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesionusuarios`
--

CREATE TABLE `sesionusuarios` (
  `id_sesion` int NOT NULL,
  `id_usuario` int NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `inicio_sesion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fin_sesion` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id_subcategoria` int NOT NULL,
  `id_categoria` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id_ubicacion` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id_unidad` int NOT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `usuario`, `password_hash`, `telefono`, `id_rol`, `estado`) VALUES
(1, 'Usuario', 'Prueba', 'testuser', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', '123456789', 1, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculoscliente`
--

CREATE TABLE `vehiculoscliente` (
  `id_vehiculo` int NOT NULL,
  `id_cliente` int NOT NULL,
  `placa` varchar(20) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Tabla para correos de notificación de stock bajo
CREATE TABLE IF NOT EXISTS `notificacion_correos` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajustesinventario`
--
ALTER TABLE `ajustesinventario`
  ADD PRIMARY KEY (`id_ajuste`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id_alerta`);

--
-- Indices de la tabla `backupsistema`
--
ALTER TABLE `backupsistema`
  ADD PRIMARY KEY (`id_backup`),
  ADD KEY `realizado_por` (`realizado_por`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `dni_ruc` (`dni_ruc`);

--
-- Indices de la tabla `configuracionalmacen`
--
ALTER TABLE `configuracionalmacen`
  ADD PRIMARY KEY (`id_config`),
  ADD KEY `responsable` (`responsable`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id_cotizacion`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `detallecotizacion`
--
ALTER TABLE `detallecotizacion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `detalleinventario`
--
ALTER TABLE `detalleinventario`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_inventario` (`id_inventario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `entradasproductos`
--
ALTER TABLE `entradasproductos`
  ADD PRIMARY KEY (`id_entrada`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `historialcambios`
--
ALTER TABLE `historialcambios`
  ADD PRIMARY KEY (`id_cambio`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `inventarioconteo`
--
ALTER TABLE `inventarioconteo`
  ADD PRIMARY KEY (`id_inventario`);

--
-- Indices de la tabla `logssistema`
--
ALTER TABLE `logssistema`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `mantenimientosistema`
--
ALTER TABLE `mantenimientosistema`
  ADD PRIMARY KEY (`id_parametro`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_alerta` (`id_alerta`);

--
-- Indices de la tabla `notificacionesusuarios`
--
ALTER TABLE `notificacionesusuarios`
  ADD PRIMARY KEY (`id_notificacion_usuario`),
  ADD KEY `id_notificacion` (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ordenestrabajo`
--
ALTER TABLE `ordenestrabajo`
  ADD PRIMARY KEY (`id_orden`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_subcategoria` (`id_subcategoria`),
  ADD KEY `id_marca` (`id_marca`),
  ADD KEY `id_ubicacion` (`id_ubicacion`);

--
-- Indices de la tabla `registrosstock`
--
ALTER TABLE `registrosstock`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `reporteconsumo`
--
ALTER TABLE `reporteconsumo`
  ADD PRIMARY KEY (`id_reporte_consumo`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `reportemovimientos`
--
ALTER TABLE `reportemovimientos`
  ADD PRIMARY KEY (`id_reporte_movimiento`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `reportestock`
--
ALTER TABLE `reportestock`
  ADD PRIMARY KEY (`id_reporte_stock`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `roles_usuarios`
--
ALTER TABLE `roles_usuarios`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `salidasproductos`
--
ALTER TABLE `salidasproductos`
  ADD PRIMARY KEY (`id_salida`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `sesionusuarios`
--
ALTER TABLE `sesionusuarios`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id_subcategoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id_ubicacion`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id_unidad`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `vehiculoscliente`
--
ALTER TABLE `vehiculoscliente`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajustesinventario`
--
ALTER TABLE `ajustesinventario`
  MODIFY `id_ajuste` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id_alerta` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `backupsistema`
--
ALTER TABLE `backupsistema`
  MODIFY `id_backup` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `configuracionalmacen`
--
ALTER TABLE `configuracionalmacen`
  MODIFY `id_config` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id_cotizacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detallecotizacion`
--
ALTER TABLE `detallecotizacion`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalleinventario`
--
ALTER TABLE `detalleinventario`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entradasproductos`
--
ALTER TABLE `entradasproductos`
  MODIFY `id_entrada` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historialcambios`
--
ALTER TABLE `historialcambios`
  MODIFY `id_cambio` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventarioconteo`
--
ALTER TABLE `inventarioconteo`
  MODIFY `id_inventario` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logssistema`
--
ALTER TABLE `logssistema`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientosistema`
--
ALTER TABLE `mantenimientosistema`
  MODIFY `id_parametro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificacionesusuarios`
--
ALTER TABLE `notificacionesusuarios`
  MODIFY `id_notificacion_usuario` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenestrabajo`
--
ALTER TABLE `ordenestrabajo`
  MODIFY `id_orden` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `registrosstock`
--
ALTER TABLE `registrosstock`
  MODIFY `id_registro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reporteconsumo`
--
ALTER TABLE `reporteconsumo`
  MODIFY `id_reporte_consumo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportemovimientos`
--
ALTER TABLE `reportemovimientos`
  MODIFY `id_reporte_movimiento` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportestock`
--
ALTER TABLE `reportestock`
  MODIFY `id_reporte_stock` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles_usuarios`
--
ALTER TABLE `roles_usuarios`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `salidasproductos`
--
ALTER TABLE `salidasproductos`
  MODIFY `id_salida` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesionusuarios`
--
ALTER TABLE `sesionusuarios`
  MODIFY `id_sesion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id_subcategoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id_ubicacion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `vehiculoscliente`
--
ALTER TABLE `vehiculoscliente`
  MODIFY `id_vehiculo` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ajustesinventario`
--
ALTER TABLE `ajustesinventario`
  ADD CONSTRAINT `ajustesinventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `backupsistema`
--
ALTER TABLE `backupsistema`
  ADD CONSTRAINT `backupsistema_ibfk_1` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `configuracionalmacen`
--
ALTER TABLE `configuracionalmacen`
  ADD CONSTRAINT `configuracionalmacen_ibfk_1` FOREIGN KEY (`responsable`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `detallecotizacion`
--
ALTER TABLE `detallecotizacion`
  ADD CONSTRAINT `detallecotizacion_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`),
  ADD CONSTRAINT `detallecotizacion_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `detalleinventario`
--
ALTER TABLE `detalleinventario`
  ADD CONSTRAINT `detalleinventario_ibfk_1` FOREIGN KEY (`id_inventario`) REFERENCES `inventarioconteo` (`id_inventario`),
  ADD CONSTRAINT `detalleinventario_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `entradasproductos`
--
ALTER TABLE `entradasproductos`
  ADD CONSTRAINT `entradasproductos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `historialcambios`
--
ALTER TABLE `historialcambios`
  ADD CONSTRAINT `historialcambios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `logssistema`
--
ALTER TABLE `logssistema`
  ADD CONSTRAINT `logssistema_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_alerta`) REFERENCES `alertas` (`id_alerta`);

--
-- Filtros para la tabla `notificacionesusuarios`
--
ALTER TABLE `notificacionesusuarios`
  ADD CONSTRAINT `notificacionesusuarios_ibfk_1` FOREIGN KEY (`id_notificacion`) REFERENCES `notificaciones` (`id_notificacion`),
  ADD CONSTRAINT `notificacionesusuarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `ordenestrabajo`
--
ALTER TABLE `ordenestrabajo`
  ADD CONSTRAINT `ordenestrabajo_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `ordenestrabajo_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculoscliente` (`id_vehiculo`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles_usuarios` (`id_rol`);

--
-- Filtros para la tabla `registrosstock`
--
ALTER TABLE `registrosstock`
  ADD CONSTRAINT `registrosstock_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `reporteconsumo`
--
ALTER TABLE `reporteconsumo`
  ADD CONSTRAINT `reporteconsumo_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`),
  ADD CONSTRAINT `reporteconsumo_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `reportemovimientos`
--
ALTER TABLE `reportemovimientos`
  ADD CONSTRAINT `reportemovimientos_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`),
  ADD CONSTRAINT `reportemovimientos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `reportestock`
--
ALTER TABLE `reportestock`
  ADD CONSTRAINT `reportestock_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`),
  ADD CONSTRAINT `reportestock_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `salidasproductos`
--
ALTER TABLE `salidasproductos`
  ADD CONSTRAINT `salidasproductos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `sesionusuarios`
--
ALTER TABLE `sesionusuarios`
  ADD CONSTRAINT `sesionusuarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles_usuarios` (`id_rol`);

--
-- Filtros para la tabla `vehiculoscliente`
--
ALTER TABLE `vehiculoscliente`
  ADD CONSTRAINT `vehiculoscliente_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
