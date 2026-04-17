-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 17-04-2026 a las 15:06:08
-- Versión del servidor: 8.4.6-6
-- Versión de PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dby49adiueiih2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kmq_intrax_precios`
--

CREATE TABLE `kmq_intrax_precios` (
  `id` int NOT NULL,
  `rango` varchar(50) NOT NULL,
  `desde` decimal(12,4) NOT NULL,
  `hasta` decimal(12,4) NOT NULL,
  `venta_publico` decimal(12,4) NOT NULL,
  `integrador` decimal(12,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `kmq_intrax_precios`
--

INSERT INTO `kmq_intrax_precios` (`id`, `rango`, `desde`, `hasta`, `venta_publico`, `integrador`, `created_at`) VALUES
(1, '$0.00 – $10.99', 0.00, 10.99, 280.00, 150.00, '2026-04-17 15:03:36'),
(2, '$11.00 – $25.99', 11.00, 25.99, 250.00, 120.00, '2026-04-17 15:03:36'),
(3, '$26.00 – $50.99', 26.00, 50.99, 200.00, 90.00, '2026-04-17 15:03:36'),
(4, '$51.00 – $100.99', 51.00, 100.99, 160.00, 80.00, '2026-04-17 15:03:36'),
(5, '$101.00 – $500.99', 101.00, 500.99, 70.00, 57.00, '2026-04-17 15:03:36'),
(6, '$501.00 – $1,000.99', 501.00, 1000.99, 65.00, 52.00, '2026-04-17 15:03:36'),
(7, '$1,001.00 – $2,000.99', 1001.00, 2000.99, 59.00, 48.00, '2026-04-17 15:03:36'),
(8, '$2,001.00 – $3,000.99', 2001.00, 3000.99, 56.00, 46.00, '2026-04-17 15:03:36'),
(9, '$3,001.00 – $4,000.99', 3001.00, 4000.99, 53.00, 43.00, '2026-04-17 15:03:36'),
(10, '$4,001.00 – $5,000.99', 4001.00, 5000.99, 49.00, 41.00, '2026-04-17 15:03:36'),
(11, '$5,001.00 – $8,000.99', 5001.00, 8000.99, 46.00, 39.00, '2026-04-17 15:03:36'),
(12, '$8,001.00 – $15,000.99', 8001.00, 15000.99, 43.00, 37.50, '2026-04-17 15:03:36'),
(13, '$15,001.00 – $30,000.99', 15001.00, 30000.99, 39.00, 35.00, '2026-04-17 15:03:36'),
(14, '$30,001.00 – $50,000.99', 30001.00, 50000.99, 36.00, 33.00, '2026-04-17 15:03:36'),
(15, '$50,001.00 – $75,500.99', 50001.00, 75500.99, 33.00, 30.00, '2026-04-17 15:03:36'),
(16, '$75,501.00 – $1,000,000.00', 75501.00, 1000000.00, 31.00, 28.00, '2026-04-17 15:03:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `kmq_intrax_precios`
--
ALTER TABLE `kmq_intrax_precios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `kmq_intrax_precios`
--
ALTER TABLE `kmq_intrax_precios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
