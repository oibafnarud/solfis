-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-03-2025 a las 20:35:16
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
-- Base de datos: `solfis_blog`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `application_stages`
--

CREATE TABLE `application_stages` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `status` enum('pending','in_progress','passed','failed') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `cv_updated_at` datetime DEFAULT NULL,
  `profile_completed` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidate_education`
--

CREATE TABLE `candidate_education` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `institution` varchar(100) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `field` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `current` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidate_experiences`
--

CREATE TABLE `candidate_experiences` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `company` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `current` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidate_skills`
--

CREATE TABLE `candidate_skills` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `skill` varchar(100) NOT NULL,
  `level` enum('Básico','Intermedio','Avanzado','Experto') NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Fiscal', 'fiscal', 'Artículos relacionados con temas fiscales y tributarios', '2025-03-06 15:35:04', '2025-03-06 15:35:04'),
(2, 'Contabilidad', 'contabilidad', 'Información sobre contabilidad y gestión financiera', '2025-03-06 15:35:04', '2025-03-10 13:26:07'),
(3, 'Finanzas', 'finanzas', 'Artículos sobre finanzas personales y empresariales', '2025-03-06 15:35:04', '2025-03-10 13:26:07'),
(4, 'Auditoría', 'auditoria', 'Contenido relacionado con auditorías y control interno', '2025-03-06 15:35:04', '2025-03-06 15:35:04'),
(5, 'Legal', 'legal', 'Temas legales y regulatorios para empresas', '2025-03-06 15:35:04', '2025-03-06 15:35:04'),
(6, 'Tecnología', 'tecnologia', 'Novedades tecnológicas en el ámbito financiero y contable', '2025-03-06 15:35:04', '2025-03-06 15:35:04'),
(7, 'Impuestos', 'impuestos', 'Información sobre impuestos y declaraciones fiscales', '2025-03-10 13:17:35', '2025-03-10 13:26:07'),
(8, 'Negocios', 'negocios', 'Temas relacionados con la gestión empresarial', '2025-03-10 13:17:35', '2025-03-10 13:26:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `parent_id`, `name`, `email`, `content`, `status`, `created_at`) VALUES
(1, 1, 0, 'Roberto Méndez', 'roberto@example.com', 'Excelente artículo. ¿Podrían profundizar más sobre cómo afectarán estos cambios a las empresas de zona franca?', 'approved', '2025-03-04 15:35:04'),
(2, 1, 0, 'María González', 'maria@example.com', 'Muy útil la información. Estaremos atentos a la implementación de estos cambios.', 'approved', '2025-03-05 15:35:04'),
(3, 2, 0, 'Carlos Jiménez', 'carlos@example.com', 'Estamos implementando algunas de estas tecnologías en nuestra empresa y definitivamente han mejorado nuestra eficiencia. Recomendado.', 'approved', '2025-03-03 15:35:04'),
(5, 3, 0, 'Fabio Duran', 'duranr.fabio@gmal.com', 'Excelente articulo!', 'approved', '2025-03-11 09:53:32'),
(6, 5, 0, 'Julio Ramirez', 'julioramirez@hola.com', 'Esto esta super genial, debería crear mas artículos. ', 'approved', '2025-03-11 13:22:59'),
(7, 5, 0, 'Julio Ramirez', 'julioramirez@hola.com', 'Esto esta super genial, debería crear mas artículos. ', 'approved', '2025-03-11 13:24:49'),
(8, 5, 0, 'Fabio Duran', 'oibafnarud@gmail.com', 'Todo esta muy muy bien!', 'approved', '2025-03-11 14:28:33'),
(9, 4, 0, 'Oibaf Narud', 'julioramirez@hola.com', 'Que buen articulo!', 'approved', '2025-03-11 16:02:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `ip_address`, `created_at`, `updated_at`) VALUES
(2, 'Fabio Duran', 'fduran@jazindustrial.com.do', '80977770147', 'Solicitud de información sobre contabilidad', 'Mensaje de: Fabio Duran\nEmpresa: Jaz Industrial\nServicio de interés: contabilidad\n\nHOla', 'replied', '::1', '2025-03-18 14:03:54', '2025-03-20 11:20:28'),
(3, 'Fabio Duran', 'fduran@jazindustrial.com.do', '80977770147', 'Solicitud de información sobre sistemas', 'Mensaje de: Fabio Duran\nEmpresa: Jaz Industrial\nServicio de interés: sistemas\n\nQuiero mas informacion sobre este servicios', 'replied', '::1', '2025-03-18 15:09:19', '2025-03-18 15:16:33'),
(4, 'Julio Ramirez', 'fduran@jazindustrial.com.do', '809865985', 'Solicitud de información sobre fiscal', 'Mensaje de: Julio Ramirez\nEmpresa: Rock Auto\nServicio de interés: fiscal\n\nMe interesa este servicio.', 'replied', '::1', '2025-03-18 15:46:33', '2025-03-18 15:47:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_settings`
--

CREATE TABLE `email_settings` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(100) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_secure` enum('tls','ssl') NOT NULL DEFAULT 'tls',
  `smtp_auth` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_username` varchar(100) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `from_email` varchar(100) NOT NULL,
  `from_name` varchar(100) NOT NULL,
  `reply_to` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_settings`
--

INSERT INTO `email_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_secure`, `smtp_auth`, `smtp_username`, `smtp_password`, `from_email`, `from_name`, `reply_to`, `recipient_email`, `updated_at`) VALUES
(1, 'cp7128.webempresa.eu', 465, 'ssl', 1, 'fduran@solfis.com.do', 'jr010101@', 'info@solfis.com.do', 'SolFis Contacto', 'fduran@solfis.com.do', 'fduran@solfis.com.do', '2025-03-20 11:18:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `vacancy_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewed','interviewing','rejected','offered','hired') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `rejection_email_sent` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_application_answers`
--

CREATE TABLE `job_application_answers` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_categories`
--

CREATE TABLE `job_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `job_categories`
--

INSERT INTO `job_categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Contabilidad', 'contabilidad', 'Puestos relacionados con contabilidad y finanzas', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(2, 'Fiscalidad', 'fiscalidad', 'Puestos relacionados con impuestos y fiscalidad', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(3, 'Auditoría', 'auditoria', 'Puestos relacionados con auditoría interna y externa', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(4, 'Consultoría', 'consultoria', 'Puestos relacionados con consultoría empresarial', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(5, 'Administración', 'administracion', 'Puestos relacionados con administración y gestión', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(6, 'Recursos Humanos', 'recursos-humanos', 'Puestos relacionados con RRHH y gestión del talento', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(7, 'Sistemas', 'sistemas', 'Puestos relacionados con tecnología y sistemas', '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(8, 'Legal', 'legal', 'Puestos relacionados con asesoría legal', '2025-03-20 13:11:22', '2025-03-20 13:11:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_vacancies`
--

CREATE TABLE `job_vacancies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `work_mode` enum('presencial','remoto','híbrido') NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `responsibilities` text NOT NULL,
  `benefits` text DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `show_salary` tinyint(1) DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('draft','published','closed','archived') NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `applications` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_vacancy_questions`
--

CREATE TABLE `job_vacancy_questions` (
  `id` int(11) NOT NULL,
  `vacancy_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `type` enum('text','textarea','select','checkbox','radio') NOT NULL DEFAULT 'text',
  `options` text DEFAULT NULL,
  `required` tinyint(1) DEFAULT 1,
  `order` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `media`
--

INSERT INTO `media` (`id`, `name`, `file_name`, `path`, `type`, `size`, `created_at`) VALUES
(11, '2149191355.jpg', '67d066c569a17_2149191355.jpg', 'img/blog/uploads/67d066c569a17_2149191355.jpg', 'image/jpeg', 393121, '2025-03-11 12:37:25'),
(15, '2149241226.jpg', '67d06ba3e1401_2149241226.jpg', 'img/blog/uploads/67d06ba3e1401_2149241226.jpg', 'image/jpeg', 529311, '2025-03-11 12:58:11'),
(16, '15818.jpg', '67d06f81ed553_15818.jpg', 'img/blog/uploads/67d06f81ed553_15818.jpg', 'image/jpeg', 578544, '2025-03-11 13:14:41'),
(17, '2148475340.jpg', '67d06fd4db688_2148475340.jpg', 'img/blog/uploads/67d06fd4db688_2148475340.jpg', 'image/jpeg', 512438, '2025-03-11 13:16:04'),
(18, '135731.jpg', '67d06fe3c0aa7_135731.jpg', 'img/blog/uploads/67d06fe3c0aa7_135731.jpg', 'image/jpeg', 142597, '2025-03-11 13:16:19'),
(19, '10327.jpg', '67d0711a68994_10327.jpg', 'img/blog/uploads/67d0711a68994_10327.jpg', 'image/jpeg', 97176, '2025-03-11 13:21:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `image` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `category_id`, `author_id`, `status`, `image`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'Principales Cambios Fiscales para 2025', 'cambios-fiscales-2025', 'Análisis detallado de las nuevas regulaciones fiscales y su impacto en las empresas dominicanas. Descubra cómo prepararse para los cambios venideros.', '<p>Las regulaciones fiscales en Rep&uacute;blica Dominicana experimentar&aacute;n cambios significativos en 2025, afectando a empresas de todos los sectores. Este art&iacute;culo analiza en profundidad las modificaciones m&aacute;s relevantes y ofrece recomendaciones para prepararse adecuadamente.</p>\r\n<h2>1. Modificaciones en el ITBIS</h2>\r\n<p>A partir de enero de 2025, entrar&aacute;n en vigor nuevas disposiciones relacionadas con el Impuesto sobre Transferencias de Bienes Industrializados y Servicios (ITBIS). Entre los cambios m&aacute;s destacados se encuentran:</p>\r\n<ul>\r\n<li>Ampliaci&oacute;n de la base gravable a servicios digitales prestados desde el exterior</li>\r\n<li>Modificaciones en el r&eacute;gimen de retenci&oacute;n para ciertos sectores econ&oacute;micos</li>\r\n<li>Nuevos procedimientos de registro y declaraci&oacute;n para empresas extranjeras</li>\r\n</ul>\r\n<h2>2. Impuesto Sobre la Renta Corporativo</h2>\r\n<p>El tratamiento del Impuesto Sobre la Renta (ISR) para personas jur&iacute;dicas tambi&eacute;n experimentar&aacute; ajustes importantes:</p>\r\n<ul>\r\n<li>Revisi&oacute;n de deducciones permitidas para gastos operativos</li>\r\n<li>Cambios en el c&aacute;lculo del anticipo mensual</li>\r\n<li>Nuevos incentivos para inversiones en sectores estrat&eacute;gicos</li>\r\n</ul>\r\n<h2>3. Documentaci&oacute;n y Reporting Fiscal</h2>\r\n<p>Los requisitos de documentaci&oacute;n y presentaci&oacute;n de informes fiscales se volver&aacute;n m&aacute;s estrictos:</p>\r\n<ul>\r\n<li>Implementaci&oacute;n completa del sistema de Facturaci&oacute;n Electr&oacute;nica</li>\r\n<li>Nuevos formatos para reportes 606, 607 y 608</li>\r\n<li>Mayor fiscalizaci&oacute;n en operaciones entre empresas vinculadas</li>\r\n</ul>\r\n<h2>Recomendaciones para Empresas</h2>\r\n<p>Para adaptarse adecuadamente a estos cambios, recomendamos:</p>\r\n<ol>\r\n<li>Realizar un diagn&oacute;stico completo de su situaci&oacute;n fiscal actual</li>\r\n<li>Actualizar sus sistemas contables y de facturaci&oacute;n</li>\r\n<li>Capacitar al personal responsable del &aacute;rea fiscal</li>\r\n<li>Considerar una reestructuraci&oacute;n de operaciones si fuera necesario</li>\r\n<li>Buscar asesor&iacute;a especializada para maximizar beneficios y minimizar riesgos</li>\r\n</ol>\r\n<p>En SolFis contamos con un equipo de expertos fiscales listos para ayudarle a navegar estos cambios de manera eficiente. <a href=\"../../contacto\">Cont&aacute;ctenos</a> para una consulta personalizada.</p>', 1, NULL, 'published', 'img/blog/uploads/67d06fd4db688_2148475340.jpg', '2025-03-06 15:35:04', '2025-03-06 15:35:04', '2025-03-11 13:16:09'),
(2, 'Transformación Digital en la Contabilidad Empresarial', 'transformacion-digital-contabilidad', 'Descubra cómo la tecnología está revolucionando los procesos contables y cómo su empresa puede beneficiarse de esta transformación.', '<p>La transformaci&oacute;n digital est&aacute; revolucionando la forma en que las empresas gestionan su contabilidad. Las nuevas tecnolog&iacute;as permiten automatizar procesos, mejorar la precisi&oacute;n y obtener informaci&oacute;n financiera en tiempo real.</p>\r\n<h2>El Impacto de la Tecnolog&iacute;a en la Contabilidad</h2>\r\n<p>La contabilidad tradicional est&aacute; dando paso a sistemas digitales que ofrecen numerosas ventajas:</p>\r\n<ul>\r\n<li>Automatizaci&oacute;n de tareas repetitivas</li>\r\n<li>Reducci&oacute;n de errores humanos</li>\r\n<li>Acceso a informaci&oacute;n financiera en tiempo real</li>\r\n<li>Mejor colaboraci&oacute;n entre equipos</li>\r\n<li>Mayor seguridad y respaldo de datos</li>\r\n</ul>\r\n<h2>Principales Tecnolog&iacute;as Contables</h2>\r\n<p>Entre las tecnolog&iacute;as que est&aacute;n transformando el campo contable destacan:</p>\r\n<h3>1. Software Contable en la Nube</h3>\r\n<p>Los sistemas basados en la nube permiten acceso desde cualquier lugar y dispositivo, facilitando el trabajo remoto y la colaboraci&oacute;n entre equipos. Adem&aacute;s, eliminan la necesidad de grandes inversiones en infraestructura tecnol&oacute;gica.</p>\r\n<h3>2. Inteligencia Artificial y Machine Learning</h3>\r\n<p>Estas tecnolog&iacute;as est&aacute;n permitiendo automatizar procesos como la clasificaci&oacute;n de transacciones, detecci&oacute;n de anomal&iacute;as y predicci&oacute;n de flujos de caja, liberando tiempo para tareas de mayor valor.</p>\r\n<h3>3. Blockchain</h3>\r\n<p>La tecnolog&iacute;a blockchain promete revolucionar la auditor&iacute;a y la verificaci&oacute;n de transacciones, creando registros inmutables y transparentes.</p>\r\n<h2>Beneficios para su Empresa</h2>\r\n<p>Implementar estas tecnolog&iacute;as puede traer importantes ventajas competitivas:</p>\r\n<ul>\r\n<li>Reducci&oacute;n de costos operativos hasta en un 30%</li>\r\n<li>Mejor toma de decisiones basada en datos actualizados</li>\r\n<li>Escalabilidad del departamento contable sin aumentar proporcionalmente los recursos</li>\r\n<li>Mayor cumplimiento normativo y reducci&oacute;n de riesgos</li>\r\n</ul>\r\n<h2>&iquest;C&oacute;mo Iniciar la Transformaci&oacute;n Digital?</h2>\r\n<p>La implementaci&oacute;n de tecnolog&iacute;as contables debe ser un proceso planificado:</p>\r\n<ol>\r\n<li>Evaluar los procesos actuales e identificar oportunidades de mejora</li>\r\n<li>Seleccionar las herramientas tecnol&oacute;gicas adecuadas para su negocio</li>\r\n<li>Capacitar al personal en el uso de las nuevas tecnolog&iacute;as</li>\r\n<li>Implementar gradualmente los cambios</li>\r\n<li>Monitorear resultados y realizar ajustes necesarios</li>\r\n</ol>\r\n<p>En SolFis ofrecemos soluciones tecnol&oacute;gicas adaptadas a las necesidades espec&iacute;ficas de cada empresa. <a href=\"../../contacto\">Cont&aacute;ctenos</a> para conocer c&oacute;mo podemos ayudarle en su proceso de transformaci&oacute;n digital contable.</p>', 2, NULL, 'published', 'img/blog/uploads/67d06fe3c0aa7_135731.jpg', '2025-03-01 15:35:04', '2025-03-01 15:35:04', '2025-03-11 13:16:22'),
(3, 'Bienvenidos al Blog de SolFis', 'bienvenidos-al-blog-de-solfis', 'Bienvenidos al blog oficial de SolFis, donde compartiremos información relevante sobre contabilidad, finanzas, impuestos y gestión empresarial.', '<p>Bienvenidos al blog oficial de SolFis, donde compartiremos informaci&oacute;n relevante sobre contabilidad, finanzas, impuestos y gesti&oacute;n empresarial.</p>\r\n<p>Nuestro objetivo es proporcionar contenido de valor que ayude a profesionales y empresarios a estar al d&iacute;a con las &uacute;ltimas novedades en el &aacute;mbito financiero y contable..</p>\r\n<p>&iexcl;Esperamos que disfruten de nuestros art&iacute;culos!</p>', 2, 3, 'published', 'img/blog/uploads/67d06f81ed553_15818.jpg', '2025-03-10 13:17:35', '2025-03-10 13:17:35', '2025-03-11 13:15:53'),
(4, 'Consejos para evitar auditorías fiscales en la República Dominicana', 'consejos-para-evitar-auditor-as-fiscales-en-la-rep-blica-dominicana', 'Una auditoría fiscal puede ser un proceso estresante y que consume mucho tiempo. Sin embargo, hay varias estrategias que puedes implementar para minimizar la posibilidad de ser auditado. En este artículo, compartiremos algunos consejos sobre cómo evitar auditorías fiscales en la República Dominicana.', '<p style=\"text-align: justify;\">Una auditor&iacute;a fiscal puede ser un proceso estresante y que consume mucho tiempo. Sin embargo, hay varias estrategias que puedes implementar para minimizar la posibilidad de ser auditado. En este art&iacute;culo, compartiremos algunos consejos sobre c&oacute;mo evitar auditor&iacute;as fiscales en la Rep&uacute;blica Dominicana.</p>\r\n<p style=\"text-align: justify;\"><strong>Mant&eacute;n registros precisos</strong></p>\r\n<p style=\"text-align: justify;\">El primer paso para evitar una auditor&iacute;a fiscal es mantener registros precisos y detallados de todas tus transacciones financieras. Esto incluye facturas, recibos, estados de cuenta bancarios y cualquier otro documento que pueda ser relevante para tus impuestos.</p>\r\n<p style=\"text-align: justify;\"><strong>Conoce qu&eacute; gastos son deducibles</strong></p>\r\n<p style=\"text-align: justify;\">No todos los gastos son deducibles. Por lo tanto, es importante entender qu&eacute; gastos puedes deducir y cu&aacute;les no. Algunos gastos comunes que son deducibles incluyen los gastos de oficina, los gastos de viaje relacionados con el negocio y los gastos de formaci&oacute;n.</p>\r\n<p style=\"text-align: justify;\"><strong>Evita errores comunes en las declaraciones de impuestos</strong></p>\r\n<p style=\"text-align: justify;\">Los errores en las declaraciones de impuestos pueden ser una bandera roja para la Direcci&oacute;n General de Impuestos Internos (DGII). Algunos errores comunes incluyen no declarar todos los ingresos, reclamar deducciones por las que no eres elegible y cometer errores matem&aacute;ticos.</p>\r\n<p style=\"text-align: justify;\"><strong>Conclusi&oacute;n</strong></p>\r\n<p style=\"text-align: justify;\">Evitar una auditor&iacute;a fiscal puede ser tan simple como mantener buenos registros, entender qu&eacute; gastos son deducibles y evitar errores comunes en las declaraciones de impuestos. Sin embargo, si alguna vez te encuentras frente a una auditor&iacute;a, recuerda que no est&aacute;s solo. En SolFis, estamos aqu&iacute; para ayudarte a navegar por el proceso de auditor&iacute;a y asegurarnos de que tus derechos sean protegidos.</p>', 4, 5, 'published', 'img/blog/uploads/67d06f81ed553_15818.jpg', '2025-03-11 10:27:35', '2025-03-11 10:27:35', '2025-03-11 13:14:45'),
(5, 'Guía para principiantes sobre contabilidad y gestión fiscal en la República Dominicana', 'gu-a-para-principiantes-sobre-contabilidad-y-gesti-n-fiscal-en-la-rep-blica-dominicana', 'Preparar y presentar tus declaraciones de impuestos puede ser un proceso complicado, pero es una parte esencial de la gestión fiscal. En la República Dominicana, las empresas deben presentar declaraciones de impuestos anuales y pagos trimestrales del ISR, así como declaraciones mensuales del ITBIS y otros impuestos aplicables. Es importante asegurarte de que tus declaraciones de impuestos sean precisas y se presenten a tiempo para evitar multas y sanciones.', '<p style=\"text-align: justify;\">La contabilidad y la gesti&oacute;n fiscal son aspectos fundamentales de cualquier negocio. Sin embargo, para los principiantes, estos temas pueden parecer abrumadores. En esta gu&iacute;a, desglosaremos los conceptos b&aacute;sicos de la contabilidad y la gesti&oacute;n fiscal en la Rep&uacute;blica Dominicana para ayudarte a entender mejor estos temas cruciales.</p>\r\n<p style=\"text-align: justify;\"><strong>Entendiendo los impuestos b&aacute;sicos</strong></p>\r\n<p style=\"text-align: justify;\">En la Rep&uacute;blica Dominicana, existen varios tipos de impuestos que las empresas deben tener en cuenta. Estos incluyen el Impuesto sobre la Renta (ISR), el Impuesto a la Transferencia de Bienes Industrializados y Servicios (ITBIS), y los impuestos a la n&oacute;mina, entre otros. Cada uno de estos impuestos tiene sus propias reglas y regulaciones, y es importante entender c&oacute;mo se aplican a tu negocio.</p>\r\n<p style=\"text-align: justify;\"><strong>C&oacute;mo llevar los libros de contabilidad de tu empresa</strong></p>\r\n<p style=\"text-align: justify;\">Llevar los libros de contabilidad de tu empresa es esencial para mantener un registro preciso de tus transacciones financieras. Esto incluye todo, desde tus ventas y gastos hasta tus activos y pasivos. En la Rep&uacute;blica Dominicana, las empresas est&aacute;n obligadas a mantener un registro detallado de sus transacciones y a presentar informes financieros regulares a la Direcci&oacute;n General de Impuestos Internos (DGII).</p>\r\n<p style=\"text-align: justify;\"><strong>C&oacute;mo preparar y presentar declaraciones de impuestos</strong></p>\r\n<p style=\"text-align: justify;\">Preparar y presentar tus declaraciones de impuestos puede ser un proceso complicado, pero es una parte esencial de la gesti&oacute;n fiscal. En la Rep&uacute;blica Dominicana, las empresas deben presentar declaraciones de impuestos anuales y pagos trimestrales del ISR, as&iacute; como declaraciones mensuales del ITBIS y otros impuestos aplicables. Es importante asegurarte de que tus declaraciones de impuestos sean precisas y se presenten a tiempo para evitar multas y sanciones.</p>\r\n<p style=\"text-align: justify;\"><strong>Conclusi&oacute;n</strong></p>\r\n<p style=\"text-align: justify;\">La contabilidad y la gesti&oacute;n fiscal son aspectos cruciales de la gesti&oacute;n de un negocio en la Rep&uacute;blica Dominicana. Aunque estos temas pueden parecer complicados al principio, con el tiempo y la pr&aacute;ctica, te familiarizar&aacute;s con ellos. Recuerda, si alguna vez te sientes abrumado, siempre puedes buscar la ayuda de un profesional en contabilidad o gesti&oacute;n fiscal.</p>\r\n<p style=\"text-align: justify;\">En futuras publicaciones, profundizaremos m&aacute;s en cada uno de estos temas, proporcion&aacute;ndote la informaci&oacute;n que necesitas para manejar eficazmente la contabilidad y la gesti&oacute;n fiscal de tu negocio. Mantente atento a m&aacute;s contenido &uacute;til y relevante en nuestro blog.</p>\r\n<p style=\"text-align: justify;\">Esperamos que esta gu&iacute;a te haya proporcionado una visi&oacute;n general &uacute;til de la contabilidad y la gesti&oacute;n fiscal en la Rep&uacute;blica Dominicana. Si tienes alguna pregunta o necesitas m&aacute;s informaci&oacute;n, no dudes en ponerte en contacto con nosotros. En SolFis, estamos aqu&iacute; para ayudarte a acercarte al &eacute;xito.</p>', 2, 5, 'published', 'img/blog/uploads/67d0711a68994_10327.jpg', '2025-03-11 13:21:33', '2025-03-11 13:21:33', '2025-03-11 13:21:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `post_tag`
--

CREATE TABLE `post_tag` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `post_tag`
--

INSERT INTO `post_tag` (`post_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 7),
(2, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recruitment_stages`
--

CREATE TABLE `recruitment_stages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recruitment_stages`
--

INSERT INTO `recruitment_stages` (`id`, `name`, `description`, `order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Aplicación recibida', 'La aplicación ha sido recibida y está pendiente de revisión', 1, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(2, 'Revisión de CV', 'El CV y la aplicación están siendo revisados', 2, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(3, 'Entrevista inicial', 'Entrevista inicial para conocer al candidato', 3, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(4, 'Prueba técnica', 'Evaluación de habilidades técnicas', 4, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(5, 'Entrevista técnica', 'Entrevista para evaluar conocimientos técnicos', 5, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(6, 'Entrevista final', 'Entrevista final con el equipo directivo', 6, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(7, 'Oferta', 'Se ha extendido una oferta al candidato', 7, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(8, 'Contratado', 'El candidato ha aceptado la oferta y ha sido contratado', 8, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22'),
(9, 'Rechazado', 'El candidato ha sido rechazado en alguna etapa del proceso', 9, 1, '2025-03-20 13:11:22', '2025-03-20 13:11:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','unsubscribed') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `subscribers`
--

INSERT INTO `subscribers` (`id`, `email`, `name`, `status`, `created_at`) VALUES
(1, 'subscriber1@example.com', 'Juan Pérez', 'active', '2025-02-04 15:35:04'),
(2, 'subscriber2@example.com', 'María Rodríguez', 'active', '2025-02-09 15:35:04'),
(3, 'subscriber3@example.com', 'Roberto Sánchez', 'active', '2025-02-14 15:35:04'),
(4, 'oibafnarud@gmail.com', '', 'active', '2025-03-11 10:29:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'DGII', 'dgii', '2025-03-06 15:35:04'),
(2, 'Impuestos', 'impuestos', '2025-03-06 15:35:04'),
(3, 'TSS', 'tss', '2025-03-06 15:35:04'),
(4, 'Finanzas Personales', 'finanzas-personales', '2025-03-06 15:35:04'),
(5, 'Emprendimiento', 'emprendimiento', '2025-03-06 15:35:04'),
(6, 'Innovación', 'innovacion', '2025-03-06 15:35:04'),
(7, 'NIIF', 'niif', '2025-03-06 15:35:04'),
(8, 'Facturación', 'facturacion', '2025-03-06 15:35:04'),
(9, 'IA', 'ia', '2025-03-06 15:35:04'),
(10, 'Gestión Empresarial', 'gestion-empresarial', '2025-03-06 15:35:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','author','editor') NOT NULL DEFAULT 'author',
  `image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `image`, `bio`, `created_at`, `updated_at`) VALUES
(2, 'Administrador', 'admin@solfis.com.do', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '2025-03-10 11:15:27', '2025-03-10 11:15:27'),
(3, 'Admin', 'admin@solfis.com', '$2y$10$Yg1LQjjToJH2P1AYrwQniu/CeI1vTK5TLQ3Ppny.JelAcJj0iX.c.', 'admin', NULL, NULL, '2025-03-10 13:17:35', '2025-03-10 13:26:07'),
(5, 'Fabio Duran', 'fduran@solfis.com.do', '$2y$10$Zu6Vn8LxlBNqSkB3zc7HtOZ8GDBm/ypfrN5bWjV9vkt/kJ7swNKnm', 'admin', NULL, '', '2025-03-10 15:56:34', '2025-03-13 15:46:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `application_stages`
--
ALTER TABLE `application_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `stage_id` (`stage_id`);

--
-- Indices de la tabla `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indices de la tabla `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indices de la tabla `candidate_experiences`
--
ALTER TABLE `candidate_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indices de la tabla `candidate_skills`
--
ALTER TABLE `candidate_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_unique` (`slug`);

--
-- Indices de la tabla `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indices de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `email_settings`
--
ALTER TABLE `email_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vacancy_candidate` (`vacancy_id`,`candidate_id`),
  ADD KEY `vacancy_id` (`vacancy_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indices de la tabla `job_application_answers`
--
ALTER TABLE `job_application_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indices de la tabla `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `job_vacancies`
--
ALTER TABLE `job_vacancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `job_vacancy_questions`
--
ALTER TABLE `job_vacancy_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vacancy_id` (`vacancy_id`);

--
-- Indices de la tabla `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_unique` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indices de la tabla `post_tag`
--
ALTER TABLE `post_tag`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indices de la tabla `recruitment_stages`
--
ALTER TABLE `recruitment_stages`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indices de la tabla `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_unique` (`slug`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indices de la tabla `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `application_stages`
--
ALTER TABLE `application_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `candidate_education`
--
ALTER TABLE `candidate_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `candidate_experiences`
--
ALTER TABLE `candidate_experiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `candidate_skills`
--
ALTER TABLE `candidate_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `email_settings`
--
ALTER TABLE `email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `job_application_answers`
--
ALTER TABLE `job_application_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `job_vacancies`
--
ALTER TABLE `job_vacancies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `job_vacancy_questions`
--
ALTER TABLE `job_vacancy_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `recruitment_stages`
--
ALTER TABLE `recruitment_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `post_tag`
--
ALTER TABLE `post_tag`
  ADD CONSTRAINT `post_tag_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
