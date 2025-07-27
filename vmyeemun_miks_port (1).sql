-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 27, 2025 at 05:26 PM
-- Server version: 8.0.39
-- PHP Version: 8.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vmyeemun_miks_port`
--

-- --------------------------------------------------------

--
-- Table structure for table `carouselimages`
--

CREATE TABLE `carouselimages` (
  `image_id` int NOT NULL,
  `project_id` int NOT NULL,
  `image_title` varchar(100) DEFAULT NULL,
  `image_description` text,
  `image_path` varchar(255) NOT NULL,
  `display_order` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `carouselimages`
--

INSERT INTO `carouselimages` (`image_id`, `project_id`, `image_title`, `image_description`, `image_path`, `display_order`, `created_at`) VALUES
(362, 36, '', '', 'uploads/carousel/Image1.jpg', 1, '2024-10-16 16:45:04'),
(363, 36, NULL, NULL, 'uploads/carousel/Image3.jpg', 2, '2024-10-16 16:45:05'),
(364, 36, NULL, NULL, 'uploads/carousel/Image5.jpg', 3, '2024-10-16 16:45:06'),
(365, 36, NULL, NULL, 'uploads/carousel/Image7.jpg', 4, '2024-10-16 16:45:06'),
(366, 36, NULL, NULL, 'uploads/carousel/Image10.jpg', 5, '2024-10-16 16:45:07'),
(367, 36, NULL, NULL, 'uploads/carousel/Image12.jpg', 6, '2024-10-16 16:45:08'),
(368, 36, NULL, NULL, 'uploads/carousel/Image14.jpg', 7, '2024-10-16 16:45:08'),
(369, 36, NULL, NULL, 'uploads/carousel/Image18.jpg', 8, '2024-10-16 16:45:09'),
(370, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 112221.png', 9, '2024-10-16 16:45:09'),
(371, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 112359.png', 10, '2024-10-16 16:45:10'),
(372, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 112431.png', 11, '2024-10-16 16:45:10'),
(373, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 112824.png', 12, '2024-10-16 16:45:12'),
(374, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 112923.png', 13, '2024-10-16 16:45:13'),
(375, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113202.png', 14, '2024-10-16 16:45:13'),
(376, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113216.png', 15, '2024-10-16 16:45:14'),
(377, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113330.png', 16, '2024-10-16 16:45:14'),
(378, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113344.png', 17, '2024-10-16 16:45:16'),
(379, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113417.png', 18, '2024-10-16 16:45:17'),
(380, 36, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-01 113426.png', 19, '2024-10-16 16:45:18'),
(381, 37, '', '', 'uploads/carousel/Image1_002.png', 1, '2024-10-16 17:57:12'),
(382, 37, NULL, NULL, 'uploads/carousel/Image2_001.png', 2, '2024-10-16 17:57:23'),
(383, 37, NULL, NULL, 'uploads/carousel/Image5_002.png', 3, '2024-10-16 17:57:28'),
(384, 37, NULL, NULL, 'uploads/carousel/Image6_000.png', 4, '2024-10-16 17:57:36'),
(385, 37, NULL, NULL, 'uploads/carousel/Image8_000.png', 5, '2024-10-16 17:57:42'),
(386, 37, NULL, NULL, 'uploads/carousel/Image9.png', 6, '2024-10-16 17:57:47'),
(387, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 135246.png', 7, '2024-10-16 17:57:49'),
(388, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 135326.png', 8, '2024-10-16 17:57:49'),
(389, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 135626.png', 9, '2024-10-16 17:57:50'),
(390, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 135915.png', 10, '2024-10-16 17:57:50'),
(391, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140045.png', 11, '2024-10-16 17:57:50'),
(392, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140056.png', 12, '2024-10-16 17:57:50'),
(393, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140227.png', 13, '2024-10-16 17:57:50'),
(394, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140242.png', 14, '2024-10-16 17:57:51'),
(395, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140303.png', 15, '2024-10-16 17:57:51'),
(396, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 140323.png', 16, '2024-10-16 17:57:51'),
(397, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 203112.png', 17, '2024-10-16 17:57:51'),
(398, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 203259.png', 18, '2024-10-16 17:57:51'),
(399, 37, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 203310.png', 19, '2024-10-16 17:57:52'),
(400, 38, '', '', 'uploads/carousel/Image2_000.png', 1, '2024-10-16 18:10:32'),
(401, 38, NULL, NULL, 'uploads/carousel/Image4.png', 2, '2024-10-16 18:10:37'),
(402, 38, NULL, NULL, 'uploads/carousel/Image5_000.png', 3, '2024-10-16 18:10:43'),
(403, 38, NULL, NULL, 'uploads/carousel/Image7_000.png', 4, '2024-10-16 18:10:48'),
(404, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 141318.png', 5, '2024-10-16 18:10:49'),
(405, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 141349.png', 6, '2024-10-16 18:10:49'),
(406, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 141750.png', 7, '2024-10-16 18:10:49'),
(407, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 141822.png', 8, '2024-10-16 18:10:50'),
(408, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 141838.png', 9, '2024-10-16 18:10:50'),
(409, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 142206.png', 10, '2024-10-16 18:10:51'),
(410, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 142311.png', 11, '2024-10-16 18:10:51'),
(411, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 142527.png', 12, '2024-10-16 18:10:51'),
(412, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 142716.png', 13, '2024-10-16 18:10:51'),
(413, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 142807.png', 14, '2024-10-16 18:10:52'),
(414, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 143034.png', 15, '2024-10-16 18:10:52'),
(415, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 143128.png', 16, '2024-10-16 18:10:52'),
(416, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 143407.png', 17, '2024-10-16 18:10:52'),
(417, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-05 143528.png', 18, '2024-10-16 18:10:52'),
(418, 38, NULL, NULL, 'uploads/carousel/Screenshot 2024-08-06 132251.png', 19, '2024-10-16 18:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `mainimages`
--

CREATE TABLE `mainimages` (
  `image_id` int NOT NULL,
  `project_id` int NOT NULL,
  `image_title` varchar(100) DEFAULT NULL,
  `image_description` text,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `mainimages`
--

INSERT INTO `mainimages` (`image_id`, `project_id`, `image_title`, `image_description`, `image_path`, `created_at`) VALUES
(33, 36, '', NULL, 'uploads/main/Image16.jpg', '2024-10-16 16:45:03'),
(34, 37, '', NULL, 'uploads/main/Image4_001.png', '2024-10-16 17:57:07'),
(35, 38, '', NULL, 'uploads/main/Image1.png', '2024-10-16 18:10:27');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `project_description` text,
  `project_location` varchar(255) DEFAULT NULL,
  `project_date` date DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `documentation` blob
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_name`, `project_description`, `project_location`, `project_date`, `project_type`, `created_at`, `documentation`) VALUES
(36, 'Interior Design Project', 'The Architectural Association of Kenya(AAK) has partnered with the Museum of Modern Art(MoMA), Kenya Branch to establish a modern art gallery and to relocate its offices toto Nairobi\'s CBD with the aim to establish a exhibition company of the works of its members. The objective is to have a lasting place for exhibition of outstanding architectural works and architectural competition materials to the profession and the public.', 'Nairobi CBD', '2024-10-16', 'Interior design', '2024-10-16 16:45:03', NULL),
(37, 'Housing project', 'The University of Nairobi wishes to engage my services for the design of a proposed modern environmentally conscious staff housing scheme  for lecturers from the college of Architecture and Nairobi. the proposed site is delineated by Dorobo rd, Club 36 and the prefabs students hostels.', 'Nairobi, off Dorob road', '2024-10-16', 'Housing', '2024-10-16 17:57:02', NULL),
(38, 'Swahili conservation project', 'The East Africa coastal region presents an opportunity to study and understand both light and heavy thermal mass urban buildings and their thermal comfort conditions in the form of Swahili traditional architecture and the 20th century modern architecture. the task is to create a  cultural mixed used development that would foster the already existing principles and abide by the existing by-laws, creating a place that improves social cohesion.', 'Mbarak Hinawy street, Old town Mombasa', '2024-10-16', 'Mixed Use Development', '2024-10-16 18:10:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resume`
--

CREATE TABLE `resume` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `education` varchar(255) NOT NULL,
  `experience` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `resume`
--

INSERT INTO `resume` (`id`, `name`, `bio`, `education`, `experience`) VALUES
(1, 'Samuel Mikaye', 'Innovative student that loves to bring ideas to life. Adept at transforming client visions into compelling architectural solutions. Expertise in utilizing advanced design software and building information modeling (BIM) to enhance project efficiency. Committed to integrating environmentally friendly practices and materials into every design.\r\nHolds a bachelor of architectural studies degree', 'University of Nairobi 2020-', 'Designed several conceptual mixed use developments, residential developments and landscaping.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(3, 'Admin', '$2y$10$5gXZ3h7xVtdvkqWlW6gebeRdfif9QmFO7KWYYISSU23t4TJ8H72Ia');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carouselimages`
--
ALTER TABLE `carouselimages`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `mainimages`
--
ALTER TABLE `mainimages`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`);

--
-- Indexes for table `resume`
--
ALTER TABLE `resume`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carouselimages`
--
ALTER TABLE `carouselimages`
  MODIFY `image_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=419;

--
-- AUTO_INCREMENT for table `mainimages`
--
ALTER TABLE `mainimages`
  MODIFY `image_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `resume`
--
ALTER TABLE `resume`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carouselimages`
--
ALTER TABLE `carouselimages`
  ADD CONSTRAINT `carouselimages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `mainimages`
--
ALTER TABLE `mainimages`
  ADD CONSTRAINT `mainimages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
