-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-10-2025 a las 02:38:42
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u185421649_gestor_ordenes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numeric_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `cel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `numeric_id`, `name`, `cel`, `address`, `rfc`, `email`, `created_at`) VALUES
(1, 1, 1, 'Juan Pablo', '5543420292', '', '', '', '2025-09-30 01:13:58'),
(2, 1, 2, 'Gabriela Bañuelos', '5555002535', '', '', '', '2025-09-30 01:13:58'),
(3, 1, 3, 'Ricardo Perez', '5567918335', '', '', '', '2025-09-30 01:13:58'),
(4, 1, 4, 'Tendenzza', '3333597385', '', '', '', '2025-09-30 01:13:58'),
(5, 1, 5, 'Miguel', '5528957944', '', '', '', '2025-09-30 01:13:58'),
(6, 1, 6, 'Miguel Pedroza MIKE', '5528957944', '', '', '', '2025-10-01 17:19:21'),
(7, 1, 7, 'Guillermo Ramirez', '5537560408', '', '', 'iusgrp@gmail.com', '2025-10-01 17:31:42'),
(8, 1, 8, 'Loenel', '5535338195', '', '', '', '2025-10-01 17:46:02'),
(9, 1, 9, 'Erika', '5532728713', '', '', '', '2025-10-01 21:38:31'),
(10, 1, 10, 'Protector', '5534377996', '', '', '', '2025-10-02 16:27:18'),
(11, 1, 11, 'Jose Edgar Montiel', '5523676135', '', '', '', '2025-10-04 20:41:56'),
(12, 1, 12, 'Patricia Gonzalez', '5527101204', '', '', '', '2025-10-04 20:55:10'),
(13, 1, 13, 'Aurelio', '5568015778', '', '', '', '2025-10-04 23:59:57'),
(14, 1, 14, 'Juan Federal', '', '', '', '', '2025-10-06 20:09:39'),
(15, 1, 15, 'Martha Moreno', '5569645140', '', '', '', '2025-10-08 00:58:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numeric_id` int(11) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_cel` varchar(20) DEFAULT NULL,
  `client_address` text DEFAULT NULL,
  `client_rfc` varchar(20) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `vehicle_brand` varchar(255) DEFAULT NULL,
  `vehicle_plates` varchar(20) DEFAULT NULL,
  `vehicle_year` int(11) DEFAULT NULL,
  `vehicle_km` int(11) DEFAULT NULL,
  `vehicle_gas_level` varchar(20) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Recibido',
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `iva_applied` tinyint(1) DEFAULT 0,
  `advance_amount` decimal(10,2) DEFAULT 0.00,
  `advance_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `numeric_id`, `client_name`, `client_cel`, `client_address`, `client_rfc`, `client_email`, `vehicle_brand`, `vehicle_plates`, `vehicle_year`, `vehicle_km`, `vehicle_gas_level`, `observations`, `status`, `subtotal`, `iva`, `total`, `iva_applied`, `advance_amount`, `advance_date`, `created_at`, `updated_at`) VALUES
(3, 1, 9982, 'Miguel Pedroza MIKE', '5528957944', '', '', '', 'SEAT Ibiza blanco', '758-ZXA', 0, 96990, '3/4', 'Entregado 27/09/2025', 'Entregado Pagado', 5990.00, 0.00, 5990.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-10-08 01:19:18'),
(4, 1, 9983, 'Miguel', '5514768547', '', '', '', 'Mercedez Benz A180 Rojo', '973-ZCH', 2013, 203486, '1/2', 'A cuenta $2,000.00 29/09/2025', 'Entregado pendiente de pago', 3600.00, 0.00, 3600.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-10-01 17:05:55'),
(5, 1, 9981, 'Tendenzza', '3333597385', '', '', '', 'GM Aveo blanco', 'PDR-5730', 2015, NULL, '', 'Reparación marcha, clutch, venta de llantas delanteras, tapón de bomba de frenos', 'En reparación', 0.00, 0.00, 0.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-09-27 00:38:39'),
(6, 1, 9978, 'Ricardo Perez', '5567918335', '', '', '', 'Toyota Hilander gris', 'TTS-403-A', 2013, 224457, '1/2', 'Reparación transmisión, cambio de bomba de agua', 'En reparación', 0.00, 0.00, 0.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-09-27 00:38:39'),
(7, 1, 9975, 'Gabriela Bañuelos', '5555002535', '', '', '', 'Dodge Stratus Gris', 'NRM-807-A', 2005, 127309, '1/4', 'Garantia Bomba de aceite, filtro de aceite y aceite 5 litros entregado y pagado 29/09/2025\n', 'Entregado Pagado', 1450.00, 0.00, 1450.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-10-01 17:07:52'),
(8, 1, 9904, 'Juan Pablo', '5543420292', '', '', '', 'Pontiac Montana Azul', 'LPB-537-C', 1999, 205689, 'Lleno', 'Garantia', 'En reparación', 0.00, 0.00, 0.00, 0, 0.00, NULL, '2025-09-27 00:38:39', '2025-09-27 00:38:39'),
(9, 1, 9977, 'Miguel Pedroza MIKE', '5528957944', '', '', '', 'Mazda MX-5', '', 2018, 80332, '1/4', 'PAGADO Y ENTREGADO  29/09/2025', 'Entregado Pagado', 5370.00, 0.00, 5370.00, 0, 0.00, NULL, '2025-10-01 17:19:21', '2025-10-01 17:19:21'),
(10, 1, 9984, 'Guillermo Ramirez', '5537560408', '', '', 'iusgrp@gmail.com', 'Nissan Tiida PLATA', 'A04-BFP', 2017, 85304, '1/4', '', 'Entregado Pagado', 9180.00, 0.00, 9180.00, 0, 0.00, NULL, '2025-10-01 17:31:42', '2025-10-07 19:21:14'),
(11, 1, 9985, 'Loenel', '5535338195', '', '', '', 'Jeep Compass NEGRO', '', 2010, 128449, 'Vacío', '', 'En reparación', 14990.00, 0.00, 14990.00, 0, 0.00, NULL, '2025-10-01 17:46:02', '2025-10-01 17:46:02'),
(12, 1, 9986, 'Erika', '5532728713', '', '', '', 'Nissan Tsuru BLANCO', 'LMV-906-A', 2006, 233857, 'Vacío', '', 'En reparación', 38167.00, 0.00, 38167.00, 0, 0.00, NULL, '2025-10-01 21:38:31', '2025-10-08 01:15:55'),
(13, 1, 9987, 'Protector', '5534377996', '', '', '', 'Ford figo', 'LXV-778-C', 2020, 1908221, '3/4', 'Servicio de frenos, pedal se baja, y revisión sistema de enfriamiento ', 'Entregado pendiente de pago', 6440.00, 1030.40, 7470.40, 1, 0.00, NULL, '2025-10-02 16:27:18', '2025-10-07 19:21:35'),
(15, 1, 9988, 'Miguel Pedroza MIKE', '5528957944', '', '', '', 'Acura MDX Blanca', 'NKY-851-B', 2013, 138975, '1/2', 'Falla electrica', 'Entregado pendiente de pago', 3385.00, 0.00, 3385.00, 0, 0.00, NULL, '2025-10-02 22:47:19', '2025-10-04 19:52:23'),
(16, 1, 9989, 'Protector', '5534377996', '', '', '', 'Ford figo', 'T06-BKB', 2022, 101353, '1/4', '', 'Facturado', 7120.00, 1139.20, 8259.20, 1, 0.00, NULL, '2025-10-03 05:54:24', '2025-10-07 19:19:22'),
(17, 1, 9990, 'Tendenzza', '3333597385', '', '', '', 'GM Aveo blanco', 'PDR-5730', 2015, 0, 'Vacío', 'PRESUPUESTO AVEO 2015 PLACAS PCS-5730', 'Recibido', 5915.00, 946.40, 6861.40, 1, 0.00, NULL, '2025-10-03 20:18:37', '2025-10-03 23:07:03'),
(18, 1, 9991, 'Miguel Pedroza MIKE', '5528957944', '', '', '', 'Gran i10', 'G98-AAR', 2015, 1533087, '3/4', '', 'Entregado pendiente de pago', 3240.00, 518.40, 3758.40, 1, 0.00, NULL, '2025-10-04 20:29:25', '2025-10-04 20:30:04'),
(19, 1, 9992, 'Jose Edgar Montiel', '5523676135', '', '', '', 'Jeep Liberty VERDE', 'MXJ-540-B', 2006, 160510, '1/2', '', 'Entregado Pagado', 3480.00, 0.00, 3480.00, 0, 0.00, NULL, '2025-10-04 20:41:56', '2025-10-07 19:20:27'),
(20, 1, 9993, 'Tendenzza', '3333597385', '', '', '', 'Vento BLANCO', '', 2020, 141947, '1/4', '', 'Recibido', 3320.00, 0.00, 3320.00, 0, 0.00, NULL, '2025-10-04 20:47:50', '2025-10-05 04:17:22'),
(21, 1, 9994, 'Patricia Gonzalez', '5527101204', '', '', '', 'Chevrolet Matiz VERDE', '208-YRR', 2013, 98173, '1/4', '', 'Entregado Pagado', 3000.00, 0.00, 3000.00, 0, 0.00, NULL, '2025-10-04 20:55:10', '2025-10-07 19:20:40'),
(22, 1, 9995, 'Aurelio', '5568015778', '', '', '', 'Sentra BLANCO', '', 2022, 0, 'Vacío', '', 'Entregado Pagado', 1620.00, 0.00, 1620.00, 0, 0.00, NULL, '2025-10-04 23:59:57', '2025-10-08 01:32:43'),
(23, 1, 9996, 'Juan Federal', '', '', '', '', 'Nissan Xtrail Rojo', 'UXU-383-C', 2018, 0, '3/4', '', 'Entregado Pagado', 5530.00, 884.80, 6414.80, 1, 0.00, NULL, '2025-10-06 20:09:39', '2025-10-08 00:36:52'),
(24, 1, 9997, 'Erika', '5532728713', '', '', '', 'Nisssan Urvan', '', 0, 0, 'Vacío', '', 'Preparacion para entrega', 15000.00, 0.00, 15000.00, 0, 0.00, NULL, '2025-10-06 20:13:44', '2025-10-08 01:33:51'),
(25, 1, 9998, 'Tendenzza', '3333597385', '', '', '', 'Vento  ', 'LXJ850C', 2013, 0, 'Vacío', 'PRESUPUESTO', 'Recibido', 3595.00, 575.20, 4170.20, 1, 0.00, NULL, '2025-10-07 16:12:19', '2025-10-07 19:35:52'),
(26, 1, 9998, 'Martha Moreno', '5569645140', '', '', '', 'JETTA 2.5 BLANCO', 'N43-BEF', 2017, 83280, '1/4', '', 'Preparacion para entrega', 5200.00, 0.00, 5200.00, 0, 0.00, NULL, '2025-10-08 00:58:54', '2025-10-08 02:32:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `qty`, `description`, `price`) VALUES
(15, 5, 1.00, 'Servicio', 0.00),
(16, 6, 1.00, 'Servicio', 0.00),
(18, 8, 1.00, 'Arbol  de levas', 0.00),
(23, 4, 1.00, 'Reparacion de alternador', 1600.00),
(24, 4, 1.00, 'Mano de obra', 2000.00),
(25, 7, 1.00, 'Verificacion Estado de Mexico', 1450.00),
(30, 9, 1.00, 'Filtro de aire', 320.00),
(31, 9, 1.00, 'Filtro de aceite', 200.00),
(32, 9, 1.00, 'Aceite sintetico 5W/50', 1300.00),
(33, 9, 1.00, 'Bujia Iridium', 800.00),
(34, 9, 1.00, 'Lavado cuerpo de aceleracion', 100.00),
(35, 9, 1.00, 'Lavado Inyectores', 300.00),
(36, 9, 1.00, 'Verificacion CDMX', 1450.00),
(37, 9, 1.00, 'Mano de obra', 900.00),
(40, 11, 1.00, 'Juego de juntas completas', 1700.00),
(41, 11, 1.00, 'Kit de cadena distribucion', 2500.00),
(42, 11, 1.00, 'Aceite castrol 20W/50', 990.00),
(43, 11, 1.00, 'Bujias platino', 400.00),
(44, 11, 1.00, 'Filtro de aceite', 100.00),
(45, 11, 1.00, 'Filtro de aire', 200.00),
(46, 11, 1.00, 'Reparacion general de cabeza', 2200.00),
(47, 11, 1.00, 'Planear superficie de Monoblok', 900.00),
(48, 11, 1.00, 'Mano de obra', 6000.00),
(139, 10, 1.00, 'Kit de clutch PERFECTION con volante solido', 6380.00),
(140, 10, 1.00, 'Mano de obra', 2800.00),
(159, 17, 2.00, 'Llantas 185/62 R14 (Mirage)', 1660.00),
(160, 17, 1.00, 'Juego de tapones 14', 690.00),
(161, 17, 1.00, 'Reparacion de tapiceria y repaldo', 830.00),
(162, 17, 1.00, 'Juego de tapetes de aveo negros', 750.00),
(163, 17, 1.00, 'servicio', 325.00),
(179, 15, 1.00, 'Verificacion Estado de Mexico', 1450.00),
(180, 15, 1.00, 'Foco de Niebla', 185.00),
(181, 15, 1.00, 'Reparacion de llanta', 150.00),
(182, 15, 1.00, 'Alineacion delantera correccion de camber', 700.00),
(183, 15, 1.00, 'Mano de obra', 900.00),
(184, 13, 1.00, 'Balatas delanteras', 800.00),
(185, 13, 2.00, 'Rectificar discos delanteros', 100.00),
(186, 13, 1.00, 'Juego ligas de caliper', 360.00),
(187, 13, 1.00, 'Deposito de anticongelante', 790.00),
(188, 13, 1.00, 'Termostato completo', 1290.00),
(189, 13, 1.00, 'Anticongelante Naranja', 330.00),
(190, 13, 1.00, 'Litro de liquido de frenos', 170.00),
(191, 13, 1.00, 'Mano de obra', 2500.00),
(197, 18, 1.00, 'Filtro de aire', 280.00),
(198, 18, 1.00, 'Filtro de aceite', 160.00),
(199, 18, 1.00, 'Aceite castrol 20W/50', 1200.00),
(200, 18, 1.00, 'Reparción de sistema escape', 700.00),
(201, 18, 1.00, 'Mano de obra', 900.00),
(202, 19, 1.00, 'Filtro de aire', 250.00),
(203, 19, 1.00, 'Filtro de aceite', 150.00),
(204, 19, 1.00, 'Bujias platino', 480.00),
(205, 19, 1.00, 'Aceite castrol 20W/50', 1200.00),
(206, 19, 1.00, 'Lavado cuerpo de aceleracion', 100.00),
(207, 19, 1.00, 'Lavado de Inyetores ultrasonido', 400.00),
(208, 19, 1.00, 'Mano de obra', 900.00),
(209, 20, 2.00, 'Llantas 185/62 R14 (Mirage)', 1660.00),
(210, 21, 2.00, 'Baleros de rueda delanteros', 450.00),
(211, 21, 2.00, 'Rectificar discos delanteros', 150.00),
(212, 21, 1.00, 'Mano de obra', 1800.00),
(215, 22, 1.00, 'Bielta derecha', 720.00),
(216, 22, 1.00, 'Mano de obra', 900.00),
(218, 24, 1.00, 'Baño de pintura', 15000.00),
(227, 16, 1.00, 'Kit de cadena distribucion', 1190.00),
(228, 16, 1.00, 'Bomba de agua', 780.00),
(229, 16, 1.00, 'Garrafa de anticongelante', 360.00),
(230, 16, 1.00, 'Aceite sintetico 5W/50', 1200.00),
(231, 16, 1.00, 'Filtro de aceite', 90.00),
(232, 16, 1.00, 'Mano de obra', 3500.00),
(233, 25, 1.00, 'Reparacion de tapiceria y repaldo', 830.00),
(234, 25, 1.00, 'Juego de tapones 14', 690.00),
(235, 25, 1.00, 'Juego de tapetes de aveo negros', 750.00),
(236, 25, 1.00, 'Reparación tapicería asiento trasero', 1000.00),
(237, 25, 1.00, 'Servicio', 325.00),
(238, 23, 1.00, 'Filtro de aire', 220.00),
(239, 23, 1.00, 'Filtro de aceite', 150.00),
(240, 23, 1.00, 'Aceite sintetico 5W/50', 1360.00),
(241, 23, 1.00, 'Bujias platino', 500.00),
(242, 23, 1.00, 'Lavado de Inyetores ultrasonido', 300.00),
(243, 23, 1.00, 'Lavado cuerpo de aceleracion', 100.00),
(244, 23, 1.00, 'Bujes de horquilla', 2000.00),
(245, 23, 1.00, 'Mano de obra', 900.00),
(260, 12, 1.00, 'Juego juntas', 1320.00),
(261, 12, 1.00, 'Juego de anillos 020', 790.00),
(262, 12, 1.00, 'Metales de biela 030', 390.00),
(263, 12, 1.00, 'Metales de centro 010', 600.00),
(264, 12, 1.00, 'Bomba de aceite', 1452.00),
(265, 12, 1.00, 'Juego Pistones en STD', 1300.00),
(266, 12, 1.00, 'Camisa', 600.00),
(267, 12, 1.00, 'Filtro de aire', 200.00),
(268, 12, 1.00, 'Filtro de aceite', 110.00),
(269, 12, 1.00, 'Filtro de gasolina', 155.00),
(270, 12, 1.00, 'Bujias platino', 350.00),
(271, 12, 1.00, 'Garrafa de aceite Castrol 20W/50', 990.00),
(272, 12, 1.00, 'Rectificar motor', 900.00),
(273, 12, 1.00, 'Rectificar cigueñal', 800.00),
(274, 12, 1.00, 'Ajuste de bancada y bilas', 600.00),
(275, 12, 1.00, 'Biela usada STD', 600.00),
(276, 12, 1.00, 'Venta de Monoblok', 15000.00),
(277, 12, 1.00, 'Reparacion general de cabeza', 2500.00),
(278, 12, 1.00, 'Kit de cadena distribucion', 1510.00),
(279, 12, 1.00, 'Mano de obra', 8000.00),
(280, 3, 1.00, 'Kit de distribución', 1890.00),
(281, 3, 1.00, 'Bomba de agua', 700.00),
(282, 3, 1.00, 'Garrafa de anticongelante', 400.00),
(283, 3, 1.00, 'Mano de obra en general', 3000.00),
(305, 26, 1.00, 'Filtro de aire', 250.00),
(306, 26, 1.00, 'Filtro de gasolina con regulador', 450.00),
(307, 26, 1.00, 'Filtro de aceite', 280.00),
(308, 26, 5.00, 'Bujias platino', 700.00),
(309, 26, 6.00, 'Litros de aceite Castrol 15w/40', 1170.00),
(310, 26, 1.00, 'Mano de obra', 900.00),
(311, 26, 1.00, 'Verificacion CDMX', 1450.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(49, 1, '6e29391af78243e4ae646424172efd272322e02a56dc138fb304f773420d0d21', '2025-10-15 02:31:50', '2025-10-08 02:31:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplies`
--

CREATE TABLE `supplies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numeric_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `supplies`
--

INSERT INTO `supplies` (`id`, `user_id`, `numeric_id`, `description`, `price`, `created_at`) VALUES
(1, 1, 1, 'Servicio de cambio de kit de distribución', 0.00, '2025-09-30 01:13:58'),
(2, 1, 2, 'Servicio de alternador', 0.00, '2025-09-30 01:13:58'),
(3, 1, 3, 'Servicio', 0.00, '2025-09-30 01:13:58'),
(4, 1, 4, 'Verificacion Estado de Mexico', 1450.00, '2025-09-30 01:13:58'),
(5, 1, 5, 'Arbol  de levas', 0.00, '2025-09-30 01:13:58'),
(6, 1, 6, 'Filtro de aire', 320.00, '2025-10-01 17:19:21'),
(7, 1, 7, 'Filtro de aceite', 200.00, '2025-10-01 17:19:21'),
(8, 1, 8, 'Aceite sintetico 5W/50', 1300.00, '2025-10-01 17:19:21'),
(9, 1, 9, 'Bujia Iridium', 800.00, '2025-10-01 17:19:21'),
(10, 1, 10, 'Lavado cuerpo de aceleracion', 100.00, '2025-10-01 17:19:21'),
(11, 1, 11, 'Lavado Inyectores', 300.00, '2025-10-01 17:19:21'),
(12, 1, 12, 'Verificacion CDMX', 1450.00, '2025-10-01 17:19:21'),
(13, 1, 13, 'Mano de obra', 900.00, '2025-10-01 17:19:21'),
(14, 1, 14, 'Kit de clutch PERFECTION con volante solido', 6380.00, '2025-10-01 17:31:42'),
(15, 1, 15, 'Juego de juntas completas', 1700.00, '2025-10-01 17:46:02'),
(16, 1, 16, 'Kit de cadena distribucion', 2500.00, '2025-10-01 17:46:02'),
(17, 1, 17, 'Aceite castrol 20W/50', 990.00, '2025-10-01 17:46:02'),
(18, 1, 18, 'Bujias platino', 400.00, '2025-10-01 17:46:02'),
(19, 1, 19, 'Reparacion general de cabeza', 2200.00, '2025-10-01 17:46:02'),
(20, 1, 20, 'Planear superficie de Monoblok', 900.00, '2025-10-01 17:46:02'),
(21, 1, 21, 'Juego juntas', 1320.00, '2025-10-01 21:38:31'),
(22, 1, 22, 'Juego de anillos 020', 790.00, '2025-10-01 21:38:31'),
(23, 1, 23, 'Metales de biela 030', 390.00, '2025-10-01 21:38:31'),
(24, 1, 24, 'Metales de centro 010', 600.00, '2025-10-01 21:38:31'),
(25, 1, 25, 'Bomba de aceite', 1452.00, '2025-10-01 21:38:31'),
(26, 1, 26, 'Juego Pistones en STD', 1300.00, '2025-10-01 21:38:31'),
(27, 1, 27, 'Camisa', 600.00, '2025-10-01 21:38:31'),
(28, 1, 28, 'Filtro de gasolina', 155.00, '2025-10-01 21:38:31'),
(29, 1, 29, 'Garrafa de aceite Castrol 20W/50', 990.00, '2025-10-01 21:38:31'),
(30, 1, 30, 'Rectificar motor', 900.00, '2025-10-01 21:38:31'),
(31, 1, 31, 'Rectificar cigueñal', 800.00, '2025-10-01 21:38:31'),
(32, 1, 32, 'Ajuste de bancada y bilas', 600.00, '2025-10-01 21:38:31'),
(33, 1, 33, 'Biela usada STD', 600.00, '2025-10-01 21:38:31'),
(34, 1, 34, 'Venta de Monoblok', 15000.00, '2025-10-01 21:38:31'),
(35, 1, 35, 'Balatas delanteras', 0.00, '2025-10-02 16:27:18'),
(36, 1, 36, 'Llantas 185/62 R14 (Mirage)', 1660.00, '2025-10-03 20:18:37'),
(37, 1, 37, 'Juego de tapones 14', 690.00, '2025-10-03 20:18:37'),
(38, 1, 38, 'Reparacion de tapiceria y repaldo', 830.00, '2025-10-03 20:18:37'),
(39, 1, 39, 'Juego de tapetes de aveo negros', 750.00, '2025-10-03 20:18:37'),
(40, 1, 40, 'Reparción de sistema escape', 700.00, '2025-10-04 20:29:25'),
(41, 1, 41, 'Lavado de Inyetores ultrasonido', 400.00, '2025-10-04 20:41:56'),
(42, 1, 42, 'Baleros de rueda delanteros', 450.00, '2025-10-04 20:55:10'),
(43, 1, 43, 'Rectificar discos delanteros', 150.00, '2025-10-04 20:55:10'),
(44, 1, 44, 'Bielta derecha', 720.00, '2025-10-04 23:59:57'),
(45, 1, 45, 'Baño de pintura', 15000.00, '2025-10-06 20:13:44'),
(46, 1, 46, 'Reparación tapicería asiento trasero', 1000.00, '2025-10-07 16:12:19'),
(47, 1, 47, 'Filtro de gasolina con regulador', 450.00, '2025-10-08 00:58:54'),
(48, 1, 48, 'Litros de aceite Castrol 15w/40', 195.00, '2025-10-08 00:58:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `created_at`, `last_login`) VALUES
(1, 'admin@errautomotriz.online', '$2y$10$Bazm1KyzHpm/2wNYQ/4etOOnFnjXwPMyev.m0P4ivVJPKZU.efNd6', '2025-09-28 22:52:23', '2025-10-08 02:31:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numeric_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `brand` varchar(255) NOT NULL,
  `plates` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `km` int(11) DEFAULT NULL,
  `gas_level` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vehicles`
--

INSERT INTO `vehicles` (`id`, `user_id`, `numeric_id`, `client_id`, `client_name`, `brand`, `plates`, `year`, `km`, `gas_level`, `created_at`) VALUES
(1, 1, 1, 1, 'Juan Pablo', 'Pontiac Montana Azul', 'LPB-537-C', 1999, NULL, NULL, '2025-09-30 01:13:58'),
(2, 1, 2, 2, 'Gabriela Bañuelos', 'Dodge Stratus Gris', 'NRM-807-A', 2005, NULL, NULL, '2025-09-30 01:13:58'),
(3, 1, 3, 3, 'Ricardo Perez', 'Toyota Hilander gris', 'TTS-403-A', 2013, NULL, NULL, '2025-09-30 01:13:58'),
(4, 1, 4, 4, 'Tendenzza', 'GM Aveo blanco', 'PDR-5730', 2015, NULL, NULL, '2025-09-30 01:13:58'),
(5, 1, 5, 5, 'Miguel', 'SEAT Ibiza blanco', '758-ZXA', NULL, NULL, NULL, '2025-09-30 01:13:58'),
(6, 1, 6, 5, 'Miguel', 'Mercedez Benz A180 Rojo', '973-ZCH', 2013, NULL, NULL, '2025-09-30 01:13:58'),
(7, 1, 7, 7, 'Guillermo Ramirez', 'Nissan Tiida PLATA', 'A04-BFP', 2017, 85304, '1/4', '2025-10-01 17:31:42'),
(8, 1, 8, 9, 'Erika', 'Nissan Tsuru BLANCO', 'LMV-906-A', 2006, 233857, 'Vacío', '2025-10-01 21:38:31'),
(9, 1, 9, 10, 'Protector', 'Ford figo', 'LXV-778-C', 2020, 1908221, '3/4', '2025-10-02 16:27:18'),
(10, 1, 10, 6, 'Miguel Pedroza MIKE', 'Acura MDX Blanca', 'NKY-851-B', 2013, 138975, '1/2', '2025-10-02 22:47:19'),
(11, 1, 11, 10, 'Protector', 'Ford figo', 'T06-BKB', 2022, 101353, '1/4', '2025-10-03 05:54:24'),
(12, 1, 12, 6, 'Miguel Pedroza MIKE', 'Gran i10', 'G98-AAR', 2015, 1533087, '3/4', '2025-10-04 20:29:25'),
(13, 1, 13, 11, 'Jose Edgar Montiel', 'Jeep Liberty VERDE', 'MXJ-540-B', 2006, 160510, '1/2', '2025-10-04 20:41:56'),
(14, 1, 14, 12, 'Patricia Gonzalez', 'Chevrolet Matiz VERDE', '208-YRR', 2013, 98173, '1/4', '2025-10-04 20:55:10'),
(15, 1, 15, 14, 'Juan Federal', 'Nissan Xtrail Rojo', 'UXU-383-C', 2018, 0, '3/4', '2025-10-06 20:09:39'),
(16, 1, 16, 4, 'Tendenzza', 'GM Aveo  ', 'LXJ850C', 2013, 0, 'Vacío', '2025-10-07 16:12:19'),
(17, 1, 17, 15, 'Martha Moreno', 'JETTA 2.5 BLANCO', 'N43-BEF', 2017, 83280, '1/4', '2025-10-08 00:58:54');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_client` (`user_id`,`numeric_id`),
  ADD KEY `idx_name` (`name`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_order` (`user_id`,`numeric_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indices de la tabla `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_supply` (`user_id`),
  ADD KEY `idx_user_supply_numeric` (`user_id`,`numeric_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_user_vehicle` (`user_id`),
  ADD KEY `idx_plates` (`plates`),
  ADD KEY `idx_user_vehicle_numeric` (`user_id`,`numeric_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=312;

--
-- AUTO_INCREMENT de la tabla `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `supplies`
--
ALTER TABLE `supplies`
  ADD CONSTRAINT `supplies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
