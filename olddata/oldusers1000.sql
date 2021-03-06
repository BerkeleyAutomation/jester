-- phpMyAdmin SQL Dump
-- version 2.8.2.4
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jul 06, 2007 at 01:24 AM
-- Server version: 5.0.22
-- PHP Version: 5.1.6
-- 
-- Database: `jestercopy`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `users`
-- 

CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL auto_increment,
  `numrated` smallint(6) unsigned NOT NULL default '0',
  `alphaflag` tinyint(1) unsigned NOT NULL default '0',
  `betaflag` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=73422 ;

-- 
-- Dumping data for table `users`
-- 

INSERT INTO `users` (`userid`, `numrated`, `alphaflag`, `betaflag`) VALUES (1, 74, 1, 0),
(2, 100, 1, 0),
(3, 49, 1, 0),
(4, 48, 1, 0),
(5, 91, 1, 0),
(6, 100, 1, 0),
(7, 47, 1, 0),
(8, 100, 1, 0),
(9, 100, 1, 0),
(10, 72, 1, 0),
(11, 36, 1, 0),
(12, 100, 1, 0),
(13, 47, 1, 0),
(14, 100, 1, 0),
(15, 100, 1, 0),
(16, 100, 1, 0),
(17, 51, 1, 0),
(18, 100, 1, 0),
(19, 49, 1, 0),
(20, 53, 1, 0),
(21, 55, 1, 0),
(22, 50, 1, 0),
(23, 72, 1, 0),
(24, 100, 1, 0),
(25, 74, 1, 0),
(26, 67, 1, 0),
(27, 60, 1, 0),
(28, 72, 1, 0),
(29, 54, 1, 0),
(30, 46, 1, 0),
(31, 100, 1, 0),
(32, 54, 1, 0),
(33, 100, 1, 0),
(34, 38, 1, 0),
(35, 100, 1, 0),
(36, 41, 1, 0),
(37, 72, 1, 0),
(38, 51, 1, 0),
(39, 100, 1, 0),
(40, 47, 1, 0),
(41, 73, 1, 0),
(42, 50, 1, 0),
(43, 46, 1, 0),
(44, 100, 1, 0),
(45, 100, 1, 0),
(46, 92, 1, 0),
(47, 73, 1, 0),
(48, 89, 1, 0),
(49, 71, 1, 0),
(50, 100, 1, 0),
(51, 100, 1, 0),
(52, 60, 1, 0),
(53, 40, 1, 0),
(54, 100, 1, 0),
(55, 45, 1, 0),
(56, 53, 1, 0),
(57, 100, 1, 0),
(58, 62, 1, 0),
(59, 100, 1, 0),
(60, 63, 1, 0),
(61, 71, 1, 0),
(62, 100, 1, 0),
(63, 41, 1, 0),
(64, 100, 1, 0),
(65, 73, 1, 0),
(66, 61, 1, 0),
(67, 72, 1, 0),
(68, 71, 1, 0),
(69, 47, 1, 0),
(70, 65, 1, 0),
(71, 100, 1, 0),
(72, 68, 1, 0),
(73, 100, 1, 0),
(74, 67, 1, 0),
(75, 100, 1, 0),
(76, 48, 1, 0),
(77, 100, 1, 0),
(78, 55, 1, 0),
(79, 100, 1, 0),
(80, 53, 1, 0),
(81, 38, 1, 0),
(82, 53, 1, 0),
(83, 100, 1, 0),
(84, 72, 1, 0),
(85, 41, 1, 0),
(86, 72, 1, 0),
(87, 100, 1, 0),
(88, 74, 1, 0),
(89, 72, 1, 0),
(90, 100, 1, 0),
(91, 68, 1, 0),
(92, 72, 1, 0),
(93, 48, 1, 0),
(94, 69, 1, 0),
(95, 100, 1, 0),
(96, 73, 1, 0),
(97, 100, 1, 0),
(98, 37, 1, 0),
(99, 100, 1, 0),
(100, 37, 1, 0),
(101, 65, 1, 0),
(102, 38, 1, 0),
(103, 67, 1, 0),
(104, 74, 1, 0),
(105, 47, 1, 0),
(106, 56, 1, 0),
(107, 100, 1, 0),
(108, 66, 1, 0),
(109, 73, 1, 0),
(110, 100, 1, 0),
(111, 73, 1, 0),
(112, 64, 1, 0),
(113, 73, 1, 0),
(114, 41, 1, 0),
(115, 64, 1, 0),
(116, 100, 1, 0),
(117, 36, 1, 0),
(118, 72, 1, 0),
(119, 100, 1, 0),
(120, 38, 1, 0),
(121, 82, 1, 0),
(122, 100, 1, 0),
(123, 36, 1, 0),
(124, 100, 1, 0),
(125, 38, 1, 0),
(126, 72, 1, 0),
(127, 36, 1, 0),
(128, 37, 1, 0),
(129, 67, 1, 0),
(130, 71, 1, 0),
(131, 100, 1, 0),
(132, 62, 1, 0),
(133, 100, 1, 0),
(134, 63, 1, 0),
(135, 62, 1, 0),
(136, 100, 1, 0),
(137, 57, 1, 0),
(138, 64, 1, 0),
(139, 73, 1, 0),
(140, 100, 1, 0),
(141, 73, 1, 0),
(142, 72, 1, 0),
(143, 45, 1, 0),
(144, 60, 1, 0),
(145, 53, 1, 0),
(146, 53, 1, 0),
(147, 89, 1, 0),
(148, 49, 1, 0),
(149, 75, 1, 0),
(150, 88, 1, 0),
(151, 72, 1, 0),
(152, 50, 1, 0),
(153, 68, 1, 0),
(154, 100, 1, 0),
(155, 86, 1, 0),
(156, 72, 1, 0),
(157, 43, 1, 0),
(158, 65, 1, 0),
(159, 100, 1, 0),
(160, 83, 1, 0),
(161, 71, 1, 0),
(162, 38, 1, 0),
(163, 36, 1, 0),
(164, 72, 1, 0),
(165, 68, 1, 0),
(166, 93, 1, 0),
(167, 100, 1, 0),
(168, 51, 1, 0),
(169, 62, 1, 0),
(170, 41, 1, 0),
(171, 41, 1, 0),
(172, 67, 1, 0),
(173, 43, 1, 0),
(174, 43, 1, 0),
(175, 38, 1, 0),
(176, 72, 1, 0),
(177, 94, 1, 0),
(178, 44, 1, 0),
(179, 53, 1, 0),
(180, 44, 1, 0),
(181, 40, 1, 0),
(182, 57, 1, 0),
(183, 63, 1, 0),
(184, 54, 1, 0),
(185, 59, 1, 0),
(186, 73, 1, 0),
(187, 73, 1, 0),
(188, 39, 1, 0),
(189, 41, 1, 0),
(190, 67, 1, 0),
(191, 61, 1, 0),
(192, 71, 1, 0),
(193, 100, 1, 0),
(194, 100, 1, 0),
(195, 100, 1, 0),
(196, 100, 1, 0),
(197, 71, 1, 0),
(198, 100, 1, 0),
(199, 43, 1, 0),
(200, 71, 1, 0),
(201, 67, 1, 0),
(202, 100, 1, 0),
(203, 73, 1, 0),
(204, 60, 1, 0),
(205, 100, 1, 0),
(206, 38, 1, 0),
(207, 100, 1, 0),
(208, 100, 1, 0),
(209, 71, 1, 0),
(210, 100, 1, 0),
(211, 71, 1, 0),
(212, 100, 1, 0),
(213, 58, 1, 0),
(214, 71, 1, 0),
(215, 40, 1, 0),
(216, 71, 1, 0),
(217, 75, 1, 0),
(218, 72, 1, 0),
(219, 82, 1, 0),
(220, 72, 1, 0),
(221, 100, 1, 0),
(222, 100, 1, 0),
(223, 100, 1, 0),
(224, 60, 1, 0),
(225, 37, 1, 0),
(226, 100, 1, 0),
(227, 71, 1, 0),
(228, 70, 1, 0),
(229, 82, 1, 0),
(230, 100, 1, 0),
(231, 47, 1, 0),
(232, 46, 1, 0),
(233, 100, 1, 0),
(234, 41, 1, 0),
(235, 55, 1, 0),
(236, 70, 1, 0),
(237, 73, 1, 0),
(238, 38, 1, 0),
(239, 73, 1, 0),
(240, 100, 1, 0),
(241, 75, 1, 0),
(242, 100, 1, 0),
(243, 46, 1, 0),
(244, 100, 1, 0),
(245, 57, 1, 0),
(246, 78, 1, 0),
(247, 100, 1, 0),
(248, 71, 1, 0),
(249, 51, 1, 0),
(250, 55, 1, 0),
(251, 43, 1, 0),
(252, 38, 1, 0),
(253, 57, 1, 0),
(254, 73, 1, 0),
(255, 45, 1, 0),
(256, 72, 1, 0),
(257, 100, 1, 0),
(258, 100, 1, 0),
(259, 73, 1, 0),
(260, 71, 1, 0),
(261, 50, 1, 0),
(262, 71, 1, 0),
(263, 53, 1, 0),
(264, 36, 1, 0),
(265, 97, 1, 0),
(266, 75, 1, 0),
(267, 44, 1, 0),
(268, 100, 1, 0),
(269, 44, 1, 0),
(270, 38, 1, 0),
(271, 57, 1, 0),
(272, 66, 1, 0),
(273, 71, 1, 0),
(274, 63, 1, 0),
(275, 100, 1, 0),
(276, 100, 1, 0),
(277, 100, 1, 0),
(278, 45, 1, 0),
(279, 37, 1, 0),
(280, 100, 1, 0),
(281, 67, 1, 0),
(282, 36, 1, 0),
(283, 73, 1, 0),
(284, 100, 1, 0),
(285, 40, 1, 0),
(286, 100, 1, 0),
(287, 100, 1, 0),
(288, 46, 1, 0),
(289, 46, 1, 0),
(290, 75, 1, 0),
(291, 100, 1, 0),
(292, 100, 1, 0),
(293, 100, 1, 0),
(294, 71, 1, 0),
(295, 90, 1, 0),
(296, 78, 1, 0),
(297, 72, 1, 0),
(298, 66, 1, 0),
(299, 70, 1, 0),
(300, 40, 1, 0),
(301, 47, 1, 0),
(302, 61, 1, 0),
(303, 100, 1, 0),
(304, 72, 1, 0),
(305, 70, 1, 0),
(306, 100, 1, 0),
(307, 53, 1, 0),
(308, 100, 1, 0),
(309, 45, 1, 0),
(310, 100, 1, 0),
(311, 100, 1, 0),
(312, 39, 1, 0),
(313, 100, 1, 0),
(314, 100, 1, 0),
(315, 41, 1, 0),
(316, 100, 1, 0),
(317, 72, 1, 0),
(318, 38, 1, 0),
(319, 58, 1, 0),
(320, 36, 1, 0),
(321, 100, 1, 0),
(322, 100, 1, 0),
(323, 73, 1, 0),
(324, 86, 1, 0),
(325, 100, 1, 0),
(326, 84, 1, 0),
(327, 100, 1, 0),
(328, 100, 1, 0),
(329, 64, 1, 0),
(330, 71, 1, 0),
(331, 46, 1, 0),
(332, 100, 1, 0),
(333, 43, 1, 0),
(334, 100, 1, 0),
(335, 100, 1, 0),
(336, 100, 1, 0),
(337, 72, 1, 0),
(338, 100, 1, 0),
(339, 73, 1, 0),
(340, 78, 1, 0),
(341, 73, 1, 0),
(342, 59, 1, 0),
(343, 97, 1, 0),
(344, 70, 1, 0),
(345, 100, 1, 0),
(346, 49, 1, 0),
(347, 72, 1, 0),
(348, 58, 1, 0),
(349, 72, 1, 0),
(350, 54, 1, 0),
(351, 38, 1, 0),
(352, 54, 1, 0),
(353, 36, 1, 0),
(354, 47, 1, 0),
(355, 38, 1, 0),
(356, 98, 1, 0),
(357, 72, 1, 0),
(358, 47, 1, 0),
(359, 100, 1, 0),
(360, 100, 1, 0),
(361, 100, 1, 0),
(362, 38, 1, 0),
(363, 36, 1, 0),
(364, 41, 1, 0),
(365, 45, 1, 0),
(366, 100, 1, 0),
(367, 100, 1, 0),
(368, 49, 1, 0),
(369, 48, 1, 0),
(370, 74, 1, 0),
(371, 72, 1, 0),
(372, 73, 1, 0),
(373, 100, 1, 0),
(374, 71, 1, 0),
(375, 100, 1, 0),
(376, 68, 1, 0),
(377, 100, 1, 0),
(378, 100, 1, 0),
(379, 100, 1, 0),
(380, 59, 1, 0),
(381, 100, 1, 0),
(382, 54, 1, 0),
(383, 100, 1, 0),
(384, 94, 1, 0),
(385, 72, 1, 0),
(386, 44, 1, 0),
(387, 96, 1, 0),
(388, 100, 1, 0),
(389, 73, 1, 0),
(390, 72, 1, 0),
(391, 83, 1, 0),
(392, 100, 1, 0),
(393, 100, 1, 0),
(394, 52, 1, 0),
(395, 100, 1, 0),
(396, 100, 1, 0),
(397, 80, 1, 0),
(398, 42, 1, 0),
(399, 48, 1, 0),
(400, 74, 1, 0),
(401, 70, 1, 0),
(402, 37, 1, 0),
(403, 48, 1, 0),
(404, 100, 1, 0),
(405, 100, 1, 0),
(406, 51, 1, 0),
(407, 100, 1, 0),
(408, 73, 1, 0),
(409, 65, 1, 0),
(410, 73, 1, 0),
(411, 74, 1, 0),
(412, 72, 1, 0),
(413, 39, 1, 0),
(414, 100, 1, 0),
(415, 48, 1, 0),
(416, 43, 1, 0),
(417, 44, 1, 0),
(418, 38, 1, 0),
(419, 74, 1, 0),
(420, 72, 1, 0),
(421, 49, 1, 0),
(422, 75, 1, 0),
(423, 100, 1, 0),
(424, 71, 1, 0),
(425, 64, 1, 0),
(426, 72, 1, 0),
(427, 68, 1, 0),
(428, 100, 1, 0),
(429, 74, 1, 0),
(430, 41, 1, 0),
(431, 72, 1, 0),
(432, 90, 1, 0),
(433, 100, 1, 0),
(434, 67, 1, 0),
(435, 100, 1, 0),
(436, 61, 1, 0),
(437, 86, 1, 0),
(438, 100, 1, 0),
(439, 73, 1, 0),
(440, 64, 1, 0),
(441, 47, 1, 0),
(442, 56, 1, 0),
(443, 70, 1, 0),
(444, 38, 1, 0),
(445, 51, 1, 0),
(446, 78, 1, 0),
(447, 100, 1, 0),
(448, 46, 1, 0),
(449, 100, 1, 0),
(450, 100, 1, 0),
(451, 100, 1, 0),
(452, 49, 1, 0),
(453, 100, 1, 0),
(454, 74, 1, 0),
(455, 100, 1, 0),
(456, 71, 1, 0),
(457, 100, 1, 0),
(458, 100, 1, 0),
(459, 72, 1, 0),
(460, 100, 1, 0),
(461, 45, 1, 0),
(462, 73, 1, 0),
(463, 100, 1, 0),
(464, 39, 1, 0),
(465, 74, 1, 0),
(466, 74, 1, 0),
(467, 100, 1, 0),
(468, 37, 1, 0),
(469, 56, 1, 0),
(470, 37, 1, 0),
(471, 59, 1, 0),
(472, 53, 1, 0),
(473, 46, 1, 0),
(474, 100, 1, 0),
(475, 100, 1, 0),
(476, 57, 1, 0),
(477, 36, 1, 0),
(478, 39, 1, 0),
(479, 67, 1, 0),
(480, 68, 1, 0),
(481, 60, 1, 0),
(482, 100, 1, 0),
(483, 57, 1, 0),
(484, 100, 1, 0),
(485, 100, 1, 0),
(486, 64, 1, 0),
(487, 43, 1, 0),
(488, 38, 1, 0),
(489, 68, 1, 0),
(490, 68, 1, 0),
(491, 100, 1, 0),
(492, 88, 1, 0),
(493, 71, 1, 0),
(494, 69, 1, 0),
(495, 100, 1, 0),
(496, 72, 1, 0),
(497, 70, 1, 0),
(498, 44, 1, 0),
(499, 74, 1, 0),
(500, 73, 1, 0),
(501, 100, 1, 0),
(502, 48, 1, 0),
(503, 81, 1, 0),
(504, 62, 1, 0),
(505, 100, 1, 0),
(506, 100, 1, 0),
(507, 46, 1, 0),
(508, 100, 1, 0),
(509, 42, 1, 0),
(510, 94, 1, 0),
(511, 48, 1, 0),
(512, 46, 1, 0),
(513, 53, 1, 0),
(514, 72, 1, 0),
(515, 100, 1, 0),
(516, 65, 1, 0),
(517, 39, 1, 0),
(518, 38, 1, 0),
(519, 66, 1, 0),
(520, 71, 1, 0),
(521, 44, 1, 0),
(522, 74, 1, 0),
(523, 69, 1, 0),
(524, 72, 1, 0),
(525, 75, 1, 0),
(526, 53, 1, 0),
(527, 100, 1, 0),
(528, 65, 1, 0),
(529, 71, 1, 0),
(530, 100, 1, 0),
(531, 68, 1, 0),
(532, 100, 1, 0),
(533, 71, 1, 0),
(534, 37, 1, 0),
(535, 39, 1, 0),
(536, 39, 1, 0),
(537, 80, 1, 0),
(538, 73, 1, 0),
(539, 80, 1, 0),
(540, 55, 1, 0),
(541, 100, 1, 0),
(542, 36, 1, 0),
(543, 59, 1, 0),
(544, 100, 1, 0),
(545, 71, 1, 0),
(546, 40, 1, 0),
(547, 100, 1, 0),
(548, 71, 1, 0),
(549, 100, 1, 0),
(550, 100, 1, 0),
(551, 100, 1, 0),
(552, 43, 1, 0),
(553, 60, 1, 0),
(554, 56, 1, 0),
(555, 100, 1, 0),
(556, 56, 1, 0),
(557, 53, 1, 0),
(558, 100, 1, 0),
(559, 48, 1, 0),
(560, 37, 1, 0),
(561, 70, 1, 0),
(562, 100, 1, 0),
(563, 54, 1, 0),
(564, 50, 1, 0),
(565, 44, 1, 0),
(566, 37, 1, 0),
(567, 73, 1, 0),
(568, 73, 1, 0),
(569, 71, 1, 0),
(570, 37, 1, 0),
(571, 72, 1, 0),
(572, 71, 1, 0),
(573, 47, 1, 0),
(574, 100, 1, 0),
(575, 73, 1, 0),
(576, 47, 1, 0),
(577, 44, 1, 0),
(578, 72, 1, 0),
(579, 71, 1, 0),
(580, 100, 1, 0),
(581, 39, 1, 0),
(582, 74, 1, 0),
(583, 70, 1, 0),
(584, 49, 1, 0),
(585, 100, 1, 0),
(586, 72, 1, 0),
(587, 88, 1, 0),
(588, 48, 1, 0),
(589, 59, 1, 0),
(590, 63, 1, 0),
(591, 100, 1, 0),
(592, 45, 1, 0),
(593, 40, 1, 0),
(594, 47, 1, 0),
(595, 72, 1, 0),
(596, 100, 1, 0),
(597, 59, 1, 0),
(598, 39, 1, 0),
(599, 73, 1, 0),
(600, 72, 1, 0),
(601, 53, 1, 0),
(602, 49, 1, 0),
(603, 71, 1, 0),
(604, 37, 1, 0),
(605, 55, 1, 0),
(606, 59, 1, 0),
(607, 38, 1, 0),
(608, 72, 1, 0),
(609, 71, 1, 0),
(610, 49, 1, 0),
(611, 50, 1, 0),
(612, 41, 1, 0),
(613, 55, 1, 0),
(614, 100, 1, 0),
(615, 71, 1, 0),
(616, 100, 1, 0),
(617, 73, 1, 0),
(618, 60, 1, 0),
(619, 73, 1, 0),
(620, 72, 1, 0),
(621, 98, 1, 0),
(622, 73, 1, 0),
(623, 76, 1, 0),
(624, 100, 1, 0),
(625, 100, 1, 0),
(626, 70, 1, 0),
(627, 72, 1, 0),
(628, 74, 1, 0),
(629, 73, 1, 0),
(630, 72, 1, 0),
(631, 48, 1, 0),
(632, 88, 1, 0),
(633, 100, 1, 0),
(634, 100, 1, 0),
(635, 73, 1, 0),
(636, 100, 1, 0),
(637, 75, 1, 0),
(638, 40, 1, 0),
(639, 72, 1, 0),
(640, 62, 1, 0),
(641, 71, 1, 0),
(642, 42, 1, 0),
(643, 36, 1, 0),
(644, 89, 1, 0),
(645, 66, 1, 0),
(646, 100, 1, 0),
(647, 65, 1, 0),
(648, 37, 1, 0),
(649, 36, 1, 0),
(650, 52, 1, 0),
(651, 71, 1, 0),
(652, 66, 1, 0),
(653, 73, 1, 0),
(654, 50, 1, 0),
(655, 90, 1, 0),
(656, 72, 1, 0),
(657, 100, 1, 0),
(658, 100, 1, 0),
(659, 45, 1, 0),
(660, 100, 1, 0),
(661, 38, 1, 0),
(662, 72, 1, 0),
(663, 42, 1, 0),
(664, 70, 1, 0),
(665, 54, 1, 0),
(666, 100, 1, 0),
(667, 100, 1, 0),
(668, 81, 1, 0),
(669, 72, 1, 0),
(670, 37, 1, 0),
(671, 73, 1, 0),
(672, 36, 1, 0),
(673, 72, 1, 0),
(674, 72, 1, 0),
(675, 100, 1, 0),
(676, 74, 1, 0),
(677, 37, 1, 0),
(678, 72, 1, 0),
(679, 53, 1, 0),
(680, 100, 1, 0),
(681, 44, 1, 0),
(682, 66, 1, 0),
(683, 49, 1, 0),
(684, 100, 1, 0),
(685, 73, 1, 0),
(686, 100, 1, 0),
(687, 43, 1, 0),
(688, 58, 1, 0),
(689, 86, 1, 0),
(690, 61, 1, 0),
(691, 93, 1, 0),
(692, 39, 1, 0),
(693, 100, 1, 0),
(694, 36, 1, 0),
(695, 100, 1, 0),
(696, 36, 1, 0),
(697, 71, 1, 0),
(698, 73, 1, 0),
(699, 100, 1, 0),
(700, 36, 1, 0),
(701, 93, 1, 0),
(702, 100, 1, 0),
(703, 73, 1, 0),
(704, 51, 1, 0),
(705, 100, 1, 0),
(706, 56, 1, 0),
(707, 54, 1, 0),
(708, 68, 1, 0),
(709, 100, 1, 0),
(710, 100, 1, 0),
(711, 100, 1, 0),
(712, 100, 1, 0),
(713, 46, 1, 0),
(714, 44, 1, 0),
(715, 100, 1, 0),
(716, 100, 1, 0),
(717, 100, 1, 0),
(718, 58, 1, 0),
(719, 73, 1, 0),
(720, 64, 1, 0),
(721, 100, 1, 0),
(722, 100, 1, 0),
(723, 73, 1, 0),
(724, 55, 1, 0),
(725, 65, 1, 0),
(726, 89, 1, 0),
(727, 100, 1, 0),
(728, 52, 1, 0),
(729, 100, 1, 0),
(730, 58, 1, 0),
(731, 73, 1, 0),
(732, 100, 1, 0),
(733, 61, 1, 0),
(734, 51, 1, 0),
(735, 73, 1, 0),
(736, 100, 1, 0),
(737, 100, 1, 0),
(738, 56, 1, 0),
(739, 100, 1, 0),
(740, 67, 1, 0),
(741, 73, 1, 0),
(742, 36, 1, 0),
(743, 100, 1, 0),
(744, 73, 1, 0),
(745, 72, 1, 0),
(746, 100, 1, 0),
(747, 51, 1, 0),
(748, 100, 1, 0),
(749, 100, 1, 0),
(750, 44, 1, 0),
(751, 50, 1, 0),
(752, 38, 1, 0),
(753, 100, 1, 0),
(754, 48, 1, 0),
(755, 82, 1, 0),
(756, 38, 1, 0),
(757, 44, 1, 0),
(758, 98, 1, 0),
(759, 71, 1, 0),
(760, 73, 1, 0),
(761, 69, 1, 0),
(762, 72, 1, 0),
(763, 47, 1, 0),
(764, 100, 1, 0),
(765, 39, 1, 0),
(766, 100, 1, 0),
(767, 62, 1, 0),
(768, 38, 1, 0),
(769, 47, 1, 0),
(770, 72, 1, 0),
(771, 51, 1, 0),
(772, 100, 1, 0),
(773, 38, 1, 0),
(774, 41, 1, 0),
(775, 97, 1, 0),
(776, 44, 1, 0),
(777, 100, 1, 0),
(778, 71, 1, 0),
(779, 100, 1, 0),
(780, 81, 1, 0),
(781, 72, 1, 0),
(782, 72, 1, 0),
(783, 42, 1, 0),
(784, 74, 1, 0),
(785, 43, 1, 0),
(786, 100, 1, 0),
(787, 88, 1, 0),
(788, 46, 1, 0),
(789, 51, 1, 0),
(790, 73, 1, 0),
(791, 73, 1, 0),
(792, 66, 1, 0),
(793, 72, 1, 0),
(794, 83, 1, 0),
(795, 61, 1, 0),
(796, 100, 1, 0),
(797, 100, 1, 0),
(798, 100, 1, 0),
(799, 72, 1, 0),
(800, 83, 1, 0),
(801, 73, 1, 0),
(802, 41, 1, 0),
(803, 89, 1, 0),
(804, 71, 1, 0),
(805, 45, 1, 0),
(806, 74, 1, 0),
(807, 100, 1, 0),
(808, 51, 1, 0),
(809, 100, 1, 0),
(810, 72, 1, 0),
(811, 72, 1, 0),
(812, 71, 1, 0),
(813, 50, 1, 0),
(814, 72, 1, 0),
(815, 73, 1, 0),
(816, 100, 1, 0),
(817, 100, 1, 0),
(818, 73, 1, 0),
(819, 100, 1, 0),
(820, 67, 1, 0),
(821, 46, 1, 0),
(822, 39, 1, 0),
(823, 72, 1, 0),
(824, 72, 1, 0),
(825, 60, 1, 0),
(826, 42, 1, 0),
(827, 79, 1, 0),
(828, 100, 1, 0),
(829, 73, 1, 0),
(830, 36, 1, 0),
(831, 46, 1, 0),
(832, 60, 1, 0),
(833, 37, 1, 0),
(834, 36, 1, 0),
(835, 40, 1, 0),
(836, 100, 1, 0),
(837, 38, 1, 0),
(838, 38, 1, 0),
(839, 82, 1, 0),
(840, 100, 1, 0),
(841, 73, 1, 0),
(842, 72, 1, 0),
(843, 74, 1, 0),
(844, 71, 1, 0),
(845, 73, 1, 0),
(846, 40, 1, 0),
(847, 88, 1, 0),
(848, 48, 1, 0),
(849, 75, 1, 0),
(850, 59, 1, 0),
(851, 46, 1, 0),
(852, 69, 1, 0),
(853, 74, 1, 0),
(854, 100, 1, 0),
(855, 72, 1, 0),
(856, 100, 1, 0),
(857, 69, 1, 0),
(858, 63, 1, 0),
(859, 84, 1, 0),
(860, 36, 1, 0),
(861, 55, 1, 0),
(862, 41, 1, 0),
(863, 71, 1, 0),
(864, 45, 1, 0),
(865, 50, 1, 0),
(866, 50, 1, 0),
(867, 100, 1, 0),
(868, 72, 1, 0),
(869, 39, 1, 0),
(870, 73, 1, 0),
(871, 42, 1, 0),
(872, 100, 1, 0),
(873, 45, 1, 0),
(874, 72, 1, 0),
(875, 100, 1, 0),
(876, 64, 1, 0),
(877, 71, 1, 0),
(878, 56, 1, 0),
(879, 73, 1, 0),
(880, 46, 1, 0),
(881, 41, 1, 0),
(882, 42, 1, 0),
(883, 100, 1, 0),
(884, 73, 1, 0),
(885, 100, 1, 0),
(886, 100, 1, 0),
(887, 73, 1, 0),
(888, 73, 1, 0),
(889, 73, 1, 0),
(890, 37, 1, 0),
(891, 100, 1, 0),
(892, 100, 1, 0),
(893, 100, 1, 0),
(894, 36, 1, 0),
(895, 84, 1, 0),
(896, 64, 1, 0),
(897, 46, 1, 0),
(898, 53, 1, 0),
(899, 42, 1, 0),
(900, 100, 1, 0),
(901, 54, 1, 0),
(902, 64, 1, 0),
(903, 72, 1, 0),
(904, 73, 1, 0),
(905, 68, 1, 0),
(906, 100, 1, 0),
(907, 43, 1, 0),
(908, 39, 1, 0),
(909, 40, 1, 0),
(910, 72, 1, 0),
(911, 44, 1, 0),
(912, 72, 1, 0),
(913, 72, 1, 0),
(914, 100, 1, 0),
(915, 71, 1, 0),
(916, 72, 1, 0),
(917, 75, 1, 0),
(918, 100, 1, 0),
(919, 85, 1, 0),
(920, 76, 1, 0),
(921, 100, 1, 0),
(922, 72, 1, 0),
(923, 55, 1, 0),
(924, 100, 1, 0),
(925, 100, 1, 0),
(926, 100, 1, 0),
(927, 70, 1, 0),
(928, 72, 1, 0),
(929, 55, 1, 0),
(930, 100, 1, 0),
(931, 73, 1, 0),
(932, 48, 1, 0),
(933, 71, 1, 0),
(934, 100, 1, 0),
(935, 100, 1, 0),
(936, 49, 1, 0),
(937, 41, 1, 0),
(938, 68, 1, 0),
(939, 100, 1, 0),
(940, 91, 1, 0),
(941, 100, 1, 0),
(942, 100, 1, 0),
(943, 40, 1, 0),
(944, 73, 1, 0),
(945, 52, 1, 0),
(946, 100, 1, 0),
(947, 44, 1, 0),
(948, 36, 1, 0),
(949, 100, 1, 0),
(950, 60, 1, 0),
(951, 65, 1, 0),
(952, 70, 1, 0),
(953, 100, 1, 0),
(954, 100, 1, 0),
(955, 41, 1, 0),
(956, 38, 1, 0),
(957, 71, 1, 0),
(958, 46, 1, 0),
(959, 100, 1, 0),
(960, 100, 1, 0),
(961, 36, 1, 0),
(962, 100, 1, 0),
(963, 37, 1, 0),
(964, 39, 1, 0),
(965, 55, 1, 0),
(966, 37, 1, 0),
(967, 70, 1, 0),
(968, 57, 1, 0),
(969, 36, 1, 0),
(970, 100, 1, 0),
(971, 71, 1, 0),
(972, 43, 1, 0),
(973, 71, 1, 0),
(974, 63, 1, 0),
(975, 37, 1, 0),
(976, 42, 1, 0),
(977, 73, 1, 0),
(978, 100, 1, 0),
(979, 100, 1, 0),
(980, 70, 1, 0),
(981, 36, 1, 0),
(982, 48, 1, 0),
(983, 73, 1, 0),
(984, 71, 1, 0),
(985, 73, 1, 0),
(986, 83, 1, 0),
(987, 49, 1, 0),
(988, 41, 1, 0),
(989, 56, 1, 0),
(990, 48, 1, 0),
(991, 50, 1, 0),
(992, 71, 1, 0),
(993, 71, 1, 0),
(994, 59, 1, 0),
(995, 100, 1, 0),
(996, 100, 1, 0),
(997, 73, 1, 0),
(998, 71, 1, 0),
(999, 100, 1, 0),
(1000, 73, 1, 0);
