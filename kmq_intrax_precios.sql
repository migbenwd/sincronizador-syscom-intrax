-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 17-04-2026 a las 15:45:14
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
  `rango` varchar(100) NOT NULL,
  `desde` decimal(12,4) NOT NULL,
  `hasta` decimal(12,4) NOT NULL,
  `venta_publico` decimal(12,4) DEFAULT '0.0000',
  `descuento_publico` decimal(12,4) DEFAULT '0.0000',
  `cliente_vip` decimal(12,4) DEFAULT '0.0000',
  `descuento_vip` decimal(12,4) DEFAULT '0.0000',
  `integrador` decimal(12,4) DEFAULT '0.0000',
  `descuento_integrador` decimal(12,4) DEFAULT '0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `kmq_intrax_precios`
--

INSERT INTO `kmq_intrax_precios` (`id`, `rango`, `desde`, `hasta`, `venta_publico`, `descuento_publico`, `cliente_vip`, `descuento_vip`, `integrador`, `descuento_integrador`) VALUES
(1, 'De $0 a $10.99', 0.0000, 10.9900, 3.8000, 0.8000, 3.6000, 0.6000, 2.5000, 1.6000),
(2, 'De $11 a $25.99', 11.0000, 25.9900, 3.5000, 0.4000, 3.1000, 0.5000, 2.2000, 1.6500),
(3, 'De $26 a $50.99', 26.0000, 50.9900, 3.0000, 0.4500, 2.6000, 0.4000, 1.9000, 1.7000),
(4, 'De $51 a $100.99', 51.0000, 100.9900, 2.6000, 0.4000, 2.1000, 0.3500, 1.8000, 1.8000),
(5, 'De $101.00 a $500.99', 101.0000, 500.9900, 1.7000, 0.1500, 1.2500, 0.2000, 1.5700, 1.9300),
(6, 'De $501.00 a $1,000.99', 501.0000, 1000.9900, 1.6500, 0.1000, 1.2000, 0.2500, 1.5200, 1.9500),
(7, 'De $1,001.00 a $2,000.99', 1001.0000, 2000.9900, 1.5900, 0.0700, 0.9500, 0.2000, 1.4800, 1.7000),
(8, 'De $2,001.00 a $3,000.99', 2001.0000, 3000.9900, 1.5600, 0.0500, 0.7800, 0.1900, 1.4600, 1.8000),
(9, 'De $3,001.00 a $4,000.99', 3001.0000, 4000.9900, 1.5300, 0.0500, 0.6800, 0.1800, 1.4300, 1.0800),
(10, 'De $4,001.00 a $5,000.99', 4001.0000, 5000.9900, 1.4900, 0.0500, 0.6200, 0.1600, 1.4100, 1.9800),
(11, 'De $5,001.00 a $8,000.99', 5001.0000, 8000.9900, 1.4600, 0.0500, 0.5500, 0.1300, 1.3900, 1.9800),
(12, 'De $8,001.00 a $15,000.99', 8001.0000, 15000.9900, 1.4300, 0.0400, 0.5000, 0.1000, 1.3750, 1.9895),
(13, 'De $15,001.00 a $30,000.99', 15001.0000, 30000.9900, 1.3900, 0.0400, 0.4500, 0.0800, 1.3500, 1.0300),
(14, 'De $30,001.00 a $50,000.99', 30001.0000, 50000.9900, 1.3600, 0.0400, 0.4000, 0.0700, 1.3300, 1.9850),
(15, 'De $50,001.00 a $75,500.99', 50001.0000, 75500.9900, 1.3300, 0.0300, 0.3800, 0.0600, 1.3000, 1.9900),
(16, 'De $75,001.00 a $100,000.99', 75501.0000, 100000.0000, 1.3100, 0.0200, 0.3500, 0.0400, 1.2800, 2.0000);

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
