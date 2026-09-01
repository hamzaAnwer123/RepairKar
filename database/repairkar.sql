-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 05:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `repairkar`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mechanic_id` int(10) UNSIGNED NOT NULL,
  `gig_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','accepted','en_route','completed','cancelled') NOT NULL DEFAULT 'pending',
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `address` varchar(255) NOT NULL,
  `scheduled_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `mechanic_id`, `gig_id`, `status`, `lat`, `lng`, `address`, `scheduled_time`, `created_at`, `updated_at`) VALUES
(9, 7, 5, NULL, 'cancelled', 0.0000000, 0.0000000, 'Chat started before booking', NULL, '2026-08-25 07:49:28', '2026-08-27 07:21:31'),
(10, 8, 5, NULL, 'accepted', 24.8611681, 67.0101643, 'House 42, Street 6, DHA Phase 6, Karachi', NULL, '2026-08-26 07:38:25', '2026-08-27 07:21:08'),
(11, 7, 5, NULL, 'completed', 24.8607000, 67.0011000, 'House 51, Street 18, Gulistan-e-Johar Block 10, Karachi', NULL, '2026-08-27 07:20:31', '2026-08-28 07:13:22'),
(12, 7, 5, NULL, 'completed', 24.8607000, 67.0011000, 'House 51, Street 18, Gulistan-e-Johar Block 10, Karachi', NULL, '2026-08-27 08:50:33', '2026-08-27 10:54:07'),
(13, 7, 5, NULL, 'completed', 24.8607000, 67.0011000, 'House 51, Street 18, Gulistan-e-Johar Block 10, Karachi', NULL, '2026-08-28 07:43:29', '2026-08-28 07:43:56'),
(16, 7, 6, 6, 'pending', 25.0230710, 66.8819580, 'House 42, Street 6, DHA Phase 6, Karachi', NULL, '2026-08-31 08:22:32', '2026-08-31 08:22:32');

-- --------------------------------------------------------

--
-- Table structure for table `call_signals`
--

CREATE TABLE `call_signals` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `signal_type` varchar(20) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `call_signals`
--

INSERT INTO `call_signals` (`id`, `booking_id`, `sender_id`, `signal_type`, `payload`, `created_at`) VALUES
(15, 12, 7, 'hangup', '[]', '2026-08-27 17:01:09'),
(17, 13, 8, 'offer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 2700895201744803988 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS c964c8ed-c514-4e41-9bd3-2fc8acad5117\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:W4vl\\r\\na=ice-pwd:3wCehoH0wNFjPLAqnTFeEb++\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 79:F8:83:2F:13:85:16:2A:42:D5:2A:8C:E1:32:D0:78:E1:53:19:BC:B2:24:52:06:D7:18:38:CD:17:35:F4:30\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:c964c8ed-c514-4e41-9bd3-2fc8acad5117 3ded6e42-70c4-4da3-b1e4-0415174788cb\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:132415679 cname:btFDVoGdD23npflk\\r\\na=ssrc:132415679 msid:c964c8ed-c514-4e41-9bd3-2fc8acad5117 3ded6e42-70c4-4da3-b1e4-0415174788cb\\r\\nm=video 9 UDP\\/TLS\\/RTP\\/SAVPF 96 97 102 103 104 107 108 109 114 115 116 117 39 40 45 46 98 99 100 101 118 119 122 123 124\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:W4vl\\r\\na=ice-pwd:3wCehoH0wNFjPLAqnTFeEb++\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 79:F8:83:2F:13:85:16:2A:42:D5:2A:8C:E1:32:D0:78:E1:53:19:BC:B2:24:52:06:D7:18:38:CD:17:35:F4:30\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/playout-delay\\r\\na=extmap:6 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/video-content-type\\r\\na=extmap:7 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/video-timing\\r\\na=extmap:8 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:c964c8ed-c514-4e41-9bd3-2fc8acad5117 f32c00f4-b718-4927-9ad4-97eb8b7adb97\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:96 rrtr\\r\\na=rtcp-fb:97 rrtr\\r\\na=rtcp-fb:102 rrtr\\r\\na=rtcp-fb:103 rrtr\\r\\na=rtcp-fb:104 rrtr\\r\\na=rtcp-fb:107 rrtr\\r\\na=rtcp-fb:108 rrtr\\r\\na=rtcp-fb:109 rrtr\\r\\na=rtcp-fb:114 rrtr\\r\\na=rtcp-fb:115 rrtr\\r\\na=rtcp-fb:116 rrtr\\r\\na=rtcp-fb:117 rrtr\\r\\na=rtcp-fb:39 rrtr\\r\\na=rtcp-fb:40 rrtr\\r\\na=rtcp-fb:45 rrtr\\r\\na=rtcp-fb:46 rrtr\\r\\na=rtcp-fb:98 rrtr\\r\\na=rtcp-fb:99 rrtr\\r\\na=rtcp-fb:100 rrtr\\r\\na=rtcp-fb:101 rrtr\\r\\na=rtcp-fb:118 rrtr\\r\\na=rtcp-fb:119 rrtr\\r\\na=rtcp-fb:122 rrtr\\r\\na=rtcp-fb:123 rrtr\\r\\na=rtcp-fb:124 rrtr\\r\\na=rtpmap:96 VP8\\/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx\\/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:102 H264\\/90000\\r\\na=rtcp-fb:102 goog-remb\\r\\na=rtcp-fb:102 transport-cc\\r\\na=rtcp-fb:102 ccm fir\\r\\na=rtcp-fb:102 nack\\r\\na=rtcp-fb:102 nack pli\\r\\na=fmtp:102 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:103 rtx\\/90000\\r\\na=fmtp:103 apt=102\\r\\na=rtpmap:104 H264\\/90000\\r\\na=rtcp-fb:104 goog-remb\\r\\na=rtcp-fb:104 transport-cc\\r\\na=rtcp-fb:104 ccm fir\\r\\na=rtcp-fb:104 nack\\r\\na=rtcp-fb:104 nack pli\\r\\na=fmtp:104 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:107 rtx\\/90000\\r\\na=fmtp:107 apt=104\\r\\na=rtpmap:108 H264\\/90000\\r\\na=rtcp-fb:108 goog-remb\\r\\na=rtcp-fb:108 transport-cc\\r\\na=rtcp-fb:108 ccm fir\\r\\na=rtcp-fb:108 nack\\r\\na=rtcp-fb:108 nack pli\\r\\na=fmtp:108 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:109 rtx\\/90000\\r\\na=fmtp:109 apt=108\\r\\na=rtpmap:114 H264\\/90000\\r\\na=rtcp-fb:114 goog-remb\\r\\na=rtcp-fb:114 transport-cc\\r\\na=rtcp-fb:114 ccm fir\\r\\na=rtcp-fb:114 nack\\r\\na=rtcp-fb:114 nack pli\\r\\na=fmtp:114 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:115 rtx\\/90000\\r\\na=fmtp:115 apt=114\\r\\na=rtpmap:116 H264\\/90000\\r\\na=rtcp-fb:116 goog-remb\\r\\na=rtcp-fb:116 transport-cc\\r\\na=rtcp-fb:116 ccm fir\\r\\na=rtcp-fb:116 nack\\r\\na=rtcp-fb:116 nack pli\\r\\na=fmtp:116 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:117 rtx\\/90000\\r\\na=fmtp:117 apt=116\\r\\na=rtpmap:39 H264\\/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx\\/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1\\/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx\\/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9\\/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx\\/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9\\/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx\\/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:118 H264\\/90000\\r\\na=rtcp-fb:118 goog-remb\\r\\na=rtcp-fb:118 transport-cc\\r\\na=rtcp-fb:118 ccm fir\\r\\na=rtcp-fb:118 nack\\r\\na=rtcp-fb:118 nack pli\\r\\na=fmtp:118 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:119 rtx\\/90000\\r\\na=fmtp:119 apt=118\\r\\na=rtpmap:122 red\\/90000\\r\\na=rtpmap:123 rtx\\/90000\\r\\na=fmtp:123 apt=122\\r\\na=rtpmap:124 ulpfec\\/90000\\r\\na=ssrc-group:FID 3294773710 2589289450\\r\\na=ssrc:3294773710 cname:btFDVoGdD23npflk\\r\\na=ssrc:3294773710 msid:c964c8ed-c514-4e41-9bd3-2fc8acad5117 f32c00f4-b718-4927-9ad4-97eb8b7adb97\\r\\na=ssrc:2589289450 cname:btFDVoGdD23npflk\\r\\na=ssrc:2589289450 msid:c964c8ed-c514-4e41-9bd3-2fc8acad5117 f32c00f4-b718-4927-9ad4-97eb8b7adb97\\r\\n\",\"type\":\"offer\"},\"mode\":\"video\"}', '2026-08-31 10:00:09'),
(18, 13, 8, 'ice', '{\"candidate\":\"candidate:2152194367 1 udp 2122194687 192.168.8.100 61754 typ host generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(19, 13, 8, 'ice', '{\"candidate\":\"candidate:633434922 1 udp 2122260223 172.26.240.1 61753 typ host generation 0 ufrag W4vl network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(20, 13, 8, 'ice', '{\"candidate\":\"candidate:633434922 1 udp 2122260223 172.26.240.1 61755 typ host generation 0 ufrag W4vl network-id 1\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(21, 13, 8, 'ice', '{\"candidate\":\"candidate:2152194367 1 udp 2122194687 192.168.8.100 61756 typ host generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(22, 13, 8, 'ice', '{\"candidate\":\"candidate:789381121 1 udp 1685987071 119.155.193.255 60692 typ srflx raddr 192.168.8.100 rport 61756 generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(23, 13, 8, 'ice', '{\"candidate\":\"candidate:789381121 1 udp 1685987071 119.155.193.255 60690 typ srflx raddr 192.168.8.100 rport 61754 generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(24, 13, 8, 'ice', '{\"candidate\":\"candidate:1527681458 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag W4vl network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(25, 13, 8, 'ice', '{\"candidate\":\"candidate:4270326695 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(26, 13, 8, 'ice', '{\"candidate\":\"candidate:1527681458 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag W4vl network-id 1\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(27, 13, 8, 'ice', '{\"candidate\":\"candidate:4270326695 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag W4vl network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"W4vl\"}', '2026-08-31 10:00:09'),
(28, 13, 8, 'hangup', '[]', '2026-08-31 10:00:35'),
(29, 13, 8, 'ice', '{\"candidate\":\"candidate:1353878845 1 udp 2122260223 172.26.240.1 52543 typ host generation 0 ufrag alnM network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"alnM\"}', '2026-08-31 10:00:44'),
(30, 13, 8, 'offer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 5704001532724620428 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 4c18b26e-3890-4765-8486-d4ae7aad9a47\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:alnM\\r\\na=ice-pwd:PQVYG6+AP\\/dlwnrprVT5DzZB\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CC:97:77:2A:A5:25:A0:71:38:26:50:23:23:D6:7B:F4:9D:03:43:34:B2:0D:06:89:97:32:89:5D:9A:BB:A9:AB\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:4c18b26e-3890-4765-8486-d4ae7aad9a47 869d8bc7-62a0-494f-9119-e360f380991c\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:1747161985 cname:Cxbiym+nZwKTOR2f\\r\\na=ssrc:1747161985 msid:4c18b26e-3890-4765-8486-d4ae7aad9a47 869d8bc7-62a0-494f-9119-e360f380991c\\r\\n\",\"type\":\"offer\"},\"mode\":\"voice\"}', '2026-08-31 10:00:44'),
(31, 13, 8, 'ice', '{\"candidate\":\"candidate:1036709700 1 udp 2122194687 192.168.8.100 52544 typ host generation 0 ufrag alnM network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"alnM\"}', '2026-08-31 10:00:44'),
(32, 13, 8, 'ice', '{\"candidate\":\"candidate:3277863888 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag alnM network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"alnM\"}', '2026-08-31 10:00:44'),
(33, 13, 8, 'ice', '{\"candidate\":\"candidate:2920831401 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag alnM network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"alnM\"}', '2026-08-31 10:00:44'),
(34, 13, 8, 'ice', '{\"candidate\":\"candidate:4228167694 1 udp 1685987071 119.155.200.255 38152 typ srflx raddr 192.168.8.100 rport 52544 generation 0 ufrag alnM network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"alnM\"}', '2026-08-31 10:00:45'),
(35, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 5573692824159915032 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS a5a9a5fa-9bad-480a-a7da-2ca67b18f76b\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:tHEs\\r\\na=ice-pwd:WkMqmy4+lx6Sv3H8auOP0hx9\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 14:8D:90:52:32:CD:8A:3A:13:E5:14:7D:BA:CD:DF:C3:9D:81:0F:03:BC:88:7F:FB:FA:69:54:BB:88:F5:5A:03\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:a5a9a5fa-9bad-480a-a7da-2ca67b18f76b d5196bab-6f91-4397-bf77-f982a9769fe5\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:3746837109 cname:D2erS4k6ggT2gs8V\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:00:49'),
(36, 13, 7, 'ice', '{\"candidate\":\"candidate:2923129923 1 udp 2122260223 172.26.240.1 52677 typ host generation 0 ufrag tHEs network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"tHEs\"}', '2026-08-31 10:00:49'),
(37, 13, 7, 'ice', '{\"candidate\":\"candidate:3275951674 1 udp 2122194687 192.168.8.100 52678 typ host generation 0 ufrag tHEs network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"tHEs\"}', '2026-08-31 10:00:49'),
(38, 13, 7, 'ice', '{\"candidate\":\"candidate:42817904 1 udp 1685987071 119.155.200.255 38216 typ srflx raddr 192.168.8.100 rport 52678 generation 0 ufrag tHEs network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"tHEs\"}', '2026-08-31 10:00:49'),
(39, 13, 7, 'ice', '{\"candidate\":\"candidate:1038736046 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag tHEs network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"tHEs\"}', '2026-08-31 10:00:49'),
(40, 13, 7, 'ice', '{\"candidate\":\"candidate:1351727319 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag tHEs network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"tHEs\"}', '2026-08-31 10:00:49'),
(41, 13, 7, 'hangup', '[]', '2026-08-31 10:01:02'),
(42, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 1022117625691144206 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS f8f0d91f-d68b-4cf5-9b5d-84333e48b6b8\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:8waA\\r\\na=ice-pwd:TC2jGjvdEuzR5ENX2ON8rIFh\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 44:3D:ED:52:05:8E:20:FD:EB:22:16:62:78:12:38:9B:2A:4E:93:13:4A:C4:22:E6:9E:49:0F:4F:54:AE:E6:16\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:f8f0d91f-d68b-4cf5-9b5d-84333e48b6b8 df2762e4-5503-4610-b447-4822b2188fce\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:3974503766 cname:jK3Q4F8ZVW5TZ8eb\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:26:15'),
(43, 13, 7, 'ice', '{\"candidate\":\"candidate:1141505368 1 udp 2122194687 192.168.8.100 54185 typ host generation 0 ufrag 8waA network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"8waA\"}', '2026-08-31 10:26:15'),
(44, 13, 7, 'ice', '{\"candidate\":\"candidate:3784273741 1 udp 2122260223 172.26.240.1 54184 typ host generation 0 ufrag 8waA network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"8waA\"}', '2026-08-31 10:26:15'),
(45, 13, 7, 'ice', '{\"candidate\":\"candidate:986062784 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag 8waA network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"8waA\"}', '2026-08-31 10:26:15'),
(46, 13, 7, 'ice', '{\"candidate\":\"candidate:2671807957 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag 8waA network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"8waA\"}', '2026-08-31 10:26:15'),
(47, 13, 7, 'hangup', '[]', '2026-08-31 10:26:16'),
(48, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 8402178617952638939 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 51c9a3ac-20f1-40e6-993e-23e93878beb6\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:ju01\\r\\na=ice-pwd:EVgtbwJfdjhglx7LvACCuNo1\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 77:F4:07:2F:44:E0:BF:9B:A7:10:0C:5F:DC:33:B2:F3:E9:FF:E3:94:3C:55:74:21:2B:C1:40:F7:BD:4F:32:0C\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:51c9a3ac-20f1-40e6-993e-23e93878beb6 a64640dd-a956-416d-8d93-4425b296f575\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:2498275413 cname:x3qofrITN2J914Yx\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:27:10'),
(49, 13, 7, 'ice', '{\"candidate\":\"candidate:1798524006 1 udp 2122260223 172.26.240.1 54919 typ host generation 0 ufrag ju01 network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"ju01\"}', '2026-08-31 10:27:10'),
(50, 13, 7, 'ice', '{\"candidate\":\"candidate:105590303 1 udp 2122194687 192.168.8.100 54920 typ host generation 0 ufrag ju01 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"ju01\"}', '2026-08-31 10:27:10'),
(51, 13, 7, 'ice', '{\"candidate\":\"candidate:2509871346 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag ju01 network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"ju01\"}', '2026-08-31 10:27:10'),
(52, 13, 7, 'ice', '{\"candidate\":\"candidate:3347413333 1 udp 1685987071 119.155.200.255 38150 typ srflx raddr 192.168.8.100 rport 54920 generation 0 ufrag ju01 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"ju01\"}', '2026-08-31 10:27:10'),
(53, 13, 7, 'ice', '{\"candidate\":\"candidate:4175559307 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag ju01 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"ju01\"}', '2026-08-31 10:27:10'),
(54, 13, 7, 'hangup', '[]', '2026-08-31 10:27:13'),
(55, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 3036520078587082074 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS af128e02-fd78-4ec1-8778-9d4ef77eec86\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:4+qT\\r\\na=ice-pwd:fMZeCGSE0W0IHqUpcIEKvHko\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 41:E1:16:5F:62:4A:89:3E:30:B4:F2:71:CE:88:33:55:F5:5F:C5:3B:9C:19:DC:02:BA:9D:8C:07:6D:7A:39:33\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:af128e02-fd78-4ec1-8778-9d4ef77eec86 0e321eea-97be-4984-80af-eea1d61afa04\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:2946402719 cname:LLFupWAKolA\\/CQ2l\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:27:18'),
(56, 13, 7, 'ice', '{\"candidate\":\"candidate:3104059997 1 udp 2122260223 172.26.240.1 57477 typ host generation 0 ufrag 4+qT network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"4+qT\"}', '2026-08-31 10:27:18'),
(57, 13, 7, 'ice', '{\"candidate\":\"candidate:3564916772 1 udp 2122194687 192.168.8.100 57478 typ host generation 0 ufrag 4+qT network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"4+qT\"}', '2026-08-31 10:27:18'),
(58, 13, 7, 'ice', '{\"candidate\":\"candidate:363988846 1 udp 1685987071 119.155.193.255 60748 typ srflx raddr 192.168.8.100 rport 57478 generation 0 ufrag 4+qT network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"4+qT\"}', '2026-08-31 10:27:18'),
(59, 13, 7, 'ice', '{\"candidate\":\"candidate:1202644681 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag 4+qT network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"4+qT\"}', '2026-08-31 10:27:18'),
(60, 13, 7, 'ice', '{\"candidate\":\"candidate:718701744 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag 4+qT network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"4+qT\"}', '2026-08-31 10:27:18'),
(61, 13, 7, 'hangup', '[]', '2026-08-31 10:29:04'),
(62, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 410380833575005598 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 749d13ce-9144-48d9-a7c6-c15317d35780\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Se4f\\r\\na=ice-pwd:YztvrlExo9VNdk0I0peRI+RV\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CA:0E:4D:7D:74:ED:A9:14:31:0D:E1:EC:FC:8B:FC:35:83:0A:6A:28:DC:CD:27:49:67:2F:32:D6:0E:CF:9A:7F\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:749d13ce-9144-48d9-a7c6-c15317d35780 1ecd7c5a-14de-4513-af15-1f7634a35a93\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:2732195321 cname:PUTAKFw05qSb04Co\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:39:38'),
(63, 13, 7, 'ice', '{\"candidate\":\"candidate:2783322694 1 udp 2122260223 172.26.240.1 60166 typ host generation 0 ufrag Se4f network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Se4f\"}', '2026-08-31 10:39:38'),
(64, 13, 7, 'ice', '{\"candidate\":\"candidate:3365814335 1 udp 2122194687 192.168.8.100 60167 typ host generation 0 ufrag Se4f network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Se4f\"}', '2026-08-31 10:39:38'),
(65, 13, 7, 'ice', '{\"candidate\":\"candidate:1531770578 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag Se4f network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Se4f\"}', '2026-08-31 10:39:38'),
(66, 13, 7, 'ice', '{\"candidate\":\"candidate:909415595 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag Se4f network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Se4f\"}', '2026-08-31 10:39:38'),
(67, 13, 7, 'ice', '{\"candidate\":\"candidate:156243829 1 udp 1685987071 119.155.193.255 60721 typ srflx raddr 192.168.8.100 rport 60167 generation 0 ufrag Se4f network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Se4f\"}', '2026-08-31 10:39:38'),
(68, 13, 7, 'hangup', '[]', '2026-08-31 10:39:49'),
(69, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 5306277551930600839 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 1ab154f4-3882-459a-a48f-c9c218139c09\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:olxG\\r\\na=ice-pwd:hpst0Tk\\/oQ4nnuMHO3pC5+OT\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 6C:E8:DE:CF:21:93:B6:BD:CA:41:ED:FE:F0:2B:1C:1A:51:D3:C8:85:D7:8E:09:8F:5A:23:1D:42:CF:87:E8:3D\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:1ab154f4-3882-459a-a48f-c9c218139c09 8faaf5fa-3f78-4b66-927f-04afb2bfa435\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:3162174998 cname:\\/74x4erXDMtyLvnV\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:39:56'),
(70, 13, 7, 'ice', '{\"candidate\":\"candidate:3899362046 1 udp 2122260223 172.26.240.1 59484 typ host generation 0 ufrag olxG network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"olxG\"}', '2026-08-31 10:39:56'),
(71, 13, 7, 'ice', '{\"candidate\":\"candidate:1307384043 1 udp 2122194687 192.168.8.100 59485 typ host generation 0 ufrag olxG network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"olxG\"}', '2026-08-31 10:39:56'),
(72, 13, 7, 'ice', '{\"candidate\":\"candidate:3802659285 1 udp 1685987071 119.155.193.255 60775 typ srflx raddr 192.168.8.100 rport 59485 generation 0 ufrag olxG network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"olxG\"}', '2026-08-31 10:39:56'),
(73, 13, 7, 'ice', '{\"candidate\":\"candidate:2527357030 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag olxG network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"olxG\"}', '2026-08-31 10:39:56'),
(74, 13, 7, 'ice', '{\"candidate\":\"candidate:857930355 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag olxG network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"olxG\"}', '2026-08-31 10:39:56'),
(75, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 4821972571888341649 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 09ab46ae-aa02-4509-8df2-15fe225b7906\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:kYxW\\r\\na=ice-pwd:yyiVLS9tP1s8eiXXMaKv6OaR\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 1C:6E:3B:8E:00:BE:B4:44:07:ED:D4:15:B8:0B:C5:87:CB:96:AB:D9:0B:9E:5B:72:49:3B:41:53:91:0E:7B:5F\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:09ab46ae-aa02-4509-8df2-15fe225b7906 7f27e1e1-6116-445e-a2e3-196d69d74b6a\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:2930640589 cname:Sw+yyciZE0u1z1Mk\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:50:52'),
(76, 13, 7, 'ice', '{\"candidate\":\"candidate:1187304078 1 udp 2122260223 172.26.240.1 49837 typ host generation 0 ufrag kYxW network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kYxW\"}', '2026-08-31 10:50:52'),
(77, 13, 7, 'ice', '{\"candidate\":\"candidate:733786359 1 udp 2122194687 192.168.8.100 49838 typ host generation 0 ufrag kYxW network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kYxW\"}', '2026-08-31 10:50:52'),
(78, 13, 7, 'ice', '{\"candidate\":\"candidate:3575014499 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag kYxW network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kYxW\"}', '2026-08-31 10:50:52'),
(79, 13, 7, 'ice', '{\"candidate\":\"candidate:3094218266 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag kYxW network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kYxW\"}', '2026-08-31 10:50:52'),
(80, 13, 7, 'hangup', '[]', '2026-08-31 10:50:56'),
(81, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 35080103734728657 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 1ce79827-2907-4f50-ab18-a5a6c21ba247\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:vWIL\\r\\na=ice-pwd:suWJIsXlKgDuNAAHdGFKiG91\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 7C:92:8D:4A:AD:4A:BC:E6:85:58:96:E9:BA:F2:E6:0B:60:17:B2:34:EE:ED:3E:47:E1:2A:E0:4D:83:B1:95:58\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:1ce79827-2907-4f50-ab18-a5a6c21ba247 2416d0b5-be71-4afc-bdff-22433100e581\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:375549505 cname:dhiIXM+wAJ6LSuvg\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:51:01'),
(82, 13, 7, 'ice', '{\"candidate\":\"candidate:3852022289 1 udp 2122260223 172.26.240.1 62163 typ host generation 0 ufrag vWIL network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"vWIL\"}', '2026-08-31 10:51:01'),
(83, 13, 7, 'ice', '{\"candidate\":\"candidate:2296467560 1 udp 2122194687 192.168.8.100 62164 typ host generation 0 ufrag vWIL network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"vWIL\"}', '2026-08-31 10:51:01'),
(84, 13, 7, 'ice', '{\"candidate\":\"candidate:1227818786 1 udp 1685987071 119.155.193.255 59560 typ srflx raddr 192.168.8.100 rport 62164 generation 0 ufrag vWIL network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"vWIL\"}', '2026-08-31 10:51:01'),
(85, 13, 7, 'ice', '{\"candidate\":\"candidate:456386181 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag vWIL network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"vWIL\"}', '2026-08-31 10:51:01'),
(86, 13, 7, 'ice', '{\"candidate\":\"candidate:1984660732 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag vWIL network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"vWIL\"}', '2026-08-31 10:51:01'),
(87, 13, 7, 'hangup', '[]', '2026-08-31 10:51:38'),
(88, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 961073533166126553 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS d9154377-27dd-4685-9652-c7dd5f8f9c88\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Ala8\\r\\na=ice-pwd:3MXM4b\\/5dWMjPkUsoXMYFeAw\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 47:E7:04:22:F2:9D:11:98:78:E2:B2:C7:78:14:AC:DD:28:70:CE:7F:24:56:02:93:70:55:69:92:82:30:F5:54\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:d9154377-27dd-4685-9652-c7dd5f8f9c88 b6d68b3d-cf7a-4683-8e91-a04a79c2d8df\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:656811634 cname:wiiPm0sUXSW5AgR8\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 10:51:44'),
(89, 13, 7, 'ice', '{\"candidate\":\"candidate:154513706 1 udp 2122260223 172.26.240.1 61751 typ host generation 0 ufrag Ala8 network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Ala8\"}', '2026-08-31 10:51:44'),
(90, 13, 7, 'ice', '{\"candidate\":\"candidate:2897421119 1 udp 2122194687 192.168.8.100 61752 typ host generation 0 ufrag Ala8 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Ala8\"}', '2026-08-31 10:51:44'),
(91, 13, 7, 'ice', '{\"candidate\":\"candidate:2012894130 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag Ala8 network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Ala8\"}', '2026-08-31 10:51:44'),
(92, 13, 7, 'ice', '{\"candidate\":\"candidate:3531391399 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag Ala8 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Ala8\"}', '2026-08-31 10:51:44'),
(93, 13, 7, 'ice', '{\"candidate\":\"candidate:66699777 1 udp 1685987071 119.155.200.255 16880 typ srflx raddr 192.168.8.100 rport 61752 generation 0 ufrag Ala8 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Ala8\"}', '2026-08-31 10:51:44'),
(94, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 4086491050702887850 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS e2b5e3b3-8595-48ae-97e7-86455586afd0\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:7av+\\r\\na=ice-pwd:ZAZOT5kwFITrTTePeTlHNaD7\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 4A:47:1F:53:5A:0C:EE:C9:15:85:96:1D:97:EC:47:AF:82:0B:28:FD:E1:BE:0C:B0:9F:A0:1B:D9:B2:D7:71:35\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:e2b5e3b3-8595-48ae-97e7-86455586afd0 f5b39016-11e7-4027-b397-3ba2a1951e1b\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:2127242158 cname:DSxOs2MZ23KyyUzH\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 11:00:19'),
(95, 13, 7, 'ice', '{\"candidate\":\"candidate:3302982670 1 udp 2122260223 172.26.240.1 57628 typ host generation 0 ufrag 7av+ network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"7av+\"}', '2026-08-31 11:00:19'),
(96, 13, 7, 'ice', '{\"candidate\":\"candidate:1633228315 1 udp 2122194687 192.168.8.100 57629 typ host generation 0 ufrag 7av+ network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"7av+\"}', '2026-08-31 11:00:19'),
(97, 13, 7, 'ice', '{\"candidate\":\"candidate:3121637014 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag 7av+ network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"7av+\"}', '2026-08-31 11:00:19'),
(98, 13, 7, 'ice', '{\"candidate\":\"candidate:529986691 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag 7av+ network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"7av+\"}', '2026-08-31 11:00:19'),
(99, 13, 7, 'ice', '{\"candidate\":\"candidate:3457414949 1 udp 1685987071 119.155.193.255 59593 typ srflx raddr 192.168.8.100 rport 57629 generation 0 ufrag 7av+ network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"7av+\"}', '2026-08-31 11:00:19'),
(100, 13, 7, 'hangup', '[]', '2026-08-31 11:00:31'),
(101, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 3052958288552390270 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 94ea95ae-7f77-40ab-8ab1-92fb18f750e7\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:\\/BLA\\r\\na=ice-pwd:cmolkviikf4VanPbrR7ItbGy\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 FF:A4:56:DB:D1:85:8F:CC:D5:0A:D1:6A:89:3F:DA:7B:C3:1B:CD:29:DF:84:82:9C:B9:68:25:1D:33:2E:EC:20\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:94ea95ae-7f77-40ab-8ab1-92fb18f750e7 d83a0384-09a7-423b-a80d-a926a8cda10f\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:94210680 cname:Yx0PEzr8oxltX7Yy\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 11:00:41'),
(102, 13, 7, 'ice', '{\"candidate\":\"candidate:2327329254 1 udp 2122260223 172.26.240.1 52223 typ host generation 0 ufrag \\/BLA network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"\\/BLA\"}', '2026-08-31 11:00:41'),
(103, 13, 7, 'ice', '{\"candidate\":\"candidate:3888143263 1 udp 2122194687 192.168.8.100 52224 typ host generation 0 ufrag \\/BLA network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"\\/BLA\"}', '2026-08-31 11:00:41'),
(104, 13, 7, 'ice', '{\"candidate\":\"candidate:638454997 1 udp 1685987071 119.155.200.255 16776 typ srflx raddr 192.168.8.100 rport 52224 generation 0 ufrag \\/BLA network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"\\/BLA\"}', '2026-08-31 11:00:41'),
(105, 13, 7, 'ice', '{\"candidate\":\"candidate:1947397490 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag \\/BLA network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"\\/BLA\"}', '2026-08-31 11:00:41'),
(106, 13, 7, 'ice', '{\"candidate\":\"candidate:426413835 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag \\/BLA network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"\\/BLA\"}', '2026-08-31 11:00:41'),
(107, 13, 7, 'hangup', '[]', '2026-08-31 11:00:51'),
(108, 13, 7, 'answer', '{\"description\":{\"sdp\":\"v=0\\r\\no=- 7862027828950401545 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS e1289936-13a2-40c8-bb0a-12c2e49c79e7\\r\\nm=audio 9 UDP\\/TLS\\/RTP\\/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:kqvu\\r\\na=ice-pwd:k2qKOKg\\/m4EfICi\\/5LcrH4Ox\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 D3:91:C2:47:58:05:D9:57:1D:F8:EB:8C:13:97:8A:BC:0F:2D:6F:39:AE:85:82:92:BF:64:F3:CB:35:CB:C3:4E\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http:\\/\\/www.webrtc.org\\/experiments\\/rtp-hdrext\\/abs-send-time\\r\\na=extmap:3 http:\\/\\/www.ietf.org\\/id\\/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:e1289936-13a2-40c8-bb0a-12c2e49c79e7 55c0c13f-461f-4290-b073-0ae294378ade\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtcp-xr:rcvr-rtt=all\\r\\na=rtcp-fb:111 rrtr\\r\\na=rtcp-fb:63 rrtr\\r\\na=rtcp-fb:9 rrtr\\r\\na=rtcp-fb:0 rrtr\\r\\na=rtcp-fb:8 rrtr\\r\\na=rtcp-fb:13 rrtr\\r\\na=rtcp-fb:110 rrtr\\r\\na=rtcp-fb:126 rrtr\\r\\na=rtpmap:111 opus\\/48000\\/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red\\/48000\\/2\\r\\na=fmtp:63 111\\/111\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:13 CN\\/8000\\r\\na=rtpmap:110 telephone-event\\/48000\\r\\na=rtpmap:126 telephone-event\\/8000\\r\\na=ssrc:3814647972 cname:aqj2b7ljW6y2wh\\/4\\r\\n\",\"type\":\"answer\"}}', '2026-08-31 11:00:56'),
(109, 13, 7, 'ice', '{\"candidate\":\"candidate:1936733530 1 udp 2122260223 172.26.240.1 52427 typ host generation 0 ufrag kqvu network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:00:56'),
(110, 13, 7, 'ice', '{\"candidate\":\"candidate:3606488911 1 udp 2122194687 192.168.8.100 52428 typ host generation 0 ufrag kqvu network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:00:56'),
(111, 13, 7, 'ice', '{\"candidate\":\"candidate:230676418 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag kqvu network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:00:56'),
(112, 13, 7, 'ice', '{\"candidate\":\"candidate:2822325719 1 tcp 1518214911 192.168.8.100 9 typ host tcptype active generation 0 ufrag kqvu network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:00:56'),
(113, 13, 7, 'ice', '{\"candidate\":\"candidate:4041615651 1 udp 2122194687 192.168.100.13 64708 typ host generation 0 ufrag kqvu network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:01:22'),
(114, 13, 7, 'ice', '{\"candidate\":\"candidate:2385103803 1 tcp 1518214911 192.168.100.13 9 typ host tcptype active generation 0 ufrag kqvu network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:01:22'),
(115, 13, 7, 'ice', '{\"candidate\":\"candidate:3606488911 1 udp 2122260223 192.168.8.100 53450 typ host generation 0 ufrag kqvu network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:03:49'),
(116, 13, 7, 'ice', '{\"candidate\":\"candidate:2822325719 1 tcp 1518280447 192.168.8.100 9 typ host tcptype active generation 0 ufrag kqvu network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:03:49'),
(117, 13, 7, 'ice', '{\"candidate\":\"candidate:4041615651 1 udp 2113937151 192.168.100.13 60267 typ host generation 0 ufrag kqvu network-cost 999\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:03:49');
INSERT INTO `call_signals` (`id`, `booking_id`, `sender_id`, `signal_type`, `payload`, `created_at`) VALUES
(118, 13, 7, 'ice', '{\"candidate\":\"candidate:230676418 1 tcp 1518280447 172.26.240.1 9 typ host tcptype active generation 0 ufrag kqvu network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:03:53'),
(119, 13, 7, 'ice', '{\"candidate\":\"candidate:1936733530 1 udp 2122260223 172.26.240.1 51512 typ host generation 0 ufrag kqvu network-id 1\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"kqvu\"}', '2026-08-31 11:03:53'),
(120, 13, 7, 'hangup', '[]', '2026-08-31 11:06:07');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','in_progress','resolved') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 8, 'Zahid Electronics', 'tesew85742@ehwit.com', 'Location or calling', 'dn lk nice work', 'resolved', '2026-08-27 09:15:24'),
(2, NULL, 'sd', 'pitis28342@ehwit.com', 'Location or calling', 'Tell us what happened and include your booking or account details when they are relevant. We will use your message to help resolve the issue.', 'new', '2026-08-31 08:23:27'),
(4, 7, 'xalidir', 'xalidir477@ehwit.com', 'Location or calling', 'Use this form for booking problems, chat issues, account access, mechanic support', 'new', '2026-08-31 08:49:19');

-- --------------------------------------------------------

--
-- Table structure for table `gigs`
--

CREATE TABLE `gigs` (
  `id` int(10) UNSIGNED NOT NULL,
  `mechanic_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `price_min` decimal(10,2) NOT NULL,
  `price_max` decimal(10,2) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `gigs`
--

INSERT INTO `gigs` (`id`, `mechanic_id`, `title`, `description`, `price_min`, `price_max`, `photo_path`, `active`, `created_at`) VALUES
(5, 5, 'Front Load Washing Machine Repair', 'Diagnosis and repair for drum, motor, and drainage issues on front-load machines. All major brands serviced.', 500.00, 1500.00, '/assets/uploads/gigs/8a8300fdebb5f2a466fe4746f05366ff.jpg', 1, '2026-08-25 07:35:49'),
(6, 6, 'Home Wiring Fault Finding & Repair', 'Diagnosis of tripped breakers, faulty switches, and wiring issues.', 600.00, 600.00, NULL, 1, '2026-08-28 17:44:46');

-- --------------------------------------------------------

--
-- Table structure for table `mechanics`
--

CREATE TABLE `mechanics` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cnic_doc_path` varchar(255) DEFAULT NULL,
  `shop_photo_path` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `avg_rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `review_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rejected` tinyint(1) NOT NULL DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `position_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`id`, `user_id`, `shop_name`, `category`, `bio`, `address`, `cnic_doc_path`, `shop_photo_path`, `verified`, `lat`, `lng`, `last_active`, `avg_rating`, `review_count`, `rejected`, `rejection_reason`, `created_at`, `position_at`) VALUES
(5, 8, 'Zahid Electronics', 'washing-machine', '10 years of experience repairing all major washing machine brands. Specializing in drum, motor, and electrical faults. Fast, reliable service across Karachi.', '45 Beacon Street, Apt 3B, Boston, MA 02108', NULL, '../assets/uploads/shops/1d95bbc465cb1f90a3530eaca74b1957.jpg', 1, 24.8607000, 67.0011000, '2026-08-24 08:38:34', 4.0, 1, 0, NULL, '2026-08-25 06:35:14', NULL),
(6, 12, 'Tariq Electrical Services', 'electrician', 'Licensed electrician handling home wiring, fault-finding, and fixture installation safely and efficiently.', 'House 18, Street 5, PECHS Block 2, Karachi', NULL, '../assets/uploads/shops/8b7240d4f2927b6f09d57e252edd9025.jpg', 1, 24.8750000, 67.0300000, NULL, 0.0, 0, 0, NULL, '2026-08-28 17:35:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `sender_role` enum('user','mechanic') NOT NULL,
  `message_type` enum('text','image','video','document','audio','location','live_location') NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `live_location_expires_at` timestamp NULL DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `booking_id`, `sender_id`, `sender_role`, `message_type`, `content`, `created_at`, `read_at`, `live_location_expires_at`, `original_filename`, `file_size`) VALUES
(45, 9, 7, 'user', 'text', ';sdka', '2026-08-25 07:49:28', '2026-08-25 12:49:37', NULL, NULL, NULL),
(46, 9, 8, 'mechanic', 'text', 'hello', '2026-08-26 06:36:16', '2026-08-26 11:36:37', NULL, NULL, NULL),
(47, 9, 7, 'user', 'text', 'hello', '2026-08-26 06:42:39', '2026-08-26 11:42:47', NULL, NULL, NULL),
(48, 12, 8, 'mechanic', 'text', 'hello', '2026-08-27 10:54:39', '2026-08-27 16:24:46', NULL, NULL, NULL),
(49, 13, 7, 'user', 'text', 'hello', '2026-08-31 09:13:24', '2026-08-31 14:59:44', NULL, NULL, NULL),
(50, 13, 7, 'user', 'text', ';lk', '2026-08-31 09:28:03', '2026-08-31 14:59:44', NULL, NULL, NULL),
(51, 13, 7, 'user', 'location', '{\"lat\":25.023071,\"lng\":66.881958,\"address\":\"Live user location\"}', '2026-08-31 09:28:40', '2026-08-31 14:59:44', NULL, NULL, NULL),
(52, 13, 7, 'user', 'location', '{\"lat\":25.023071,\"lng\":66.881958,\"address\":\"Live user location\"}', '2026-08-31 09:28:40', '2026-08-31 14:59:44', NULL, NULL, NULL),
(58, 13, 7, 'user', 'location', '{\"lat\":25.023071,\"lng\":66.881958,\"address\":\"Live user location\"}', '2026-08-31 09:49:31', '2026-08-31 14:59:44', NULL, NULL, NULL),
(59, 13, 7, 'user', 'document', '/assets/uploads/chat/1cd4c24531b5d07df09b611d8b9b96de.xlsx', '2026-08-31 09:49:50', '2026-08-31 14:59:44', NULL, 'Assignments 3 Records.xlsx', 70253),
(60, 13, 7, 'user', 'video', '/assets/uploads/chat/13cf39080daef90329e204b5a38085a7.mp4', '2026-08-31 09:50:01', '2026-08-31 14:59:44', NULL, 'Satisfying Yellow Watermelon Cutting ASMR 🍉🔪 #Shorts.mp4', 4830947),
(61, 13, 7, 'user', 'text', ';adka;dlka', '2026-08-31 09:57:26', '2026-08-31 14:59:44', NULL, NULL, NULL),
(62, 13, 7, 'user', 'text', 'Tell us what happened and include your booking or account details when they are relevant. We will use your message to help resolve the issue.', '2026-08-31 09:57:49', '2026-08-31 14:59:44', NULL, NULL, NULL),
(63, 13, 8, 'mechanic', 'text', 'a\'AS\'', '2026-08-31 09:59:49', '2026-08-31 14:59:55', NULL, NULL, NULL),
(66, 13, 8, 'mechanic', 'text', 'hello', '2026-08-31 10:51:31', '2026-08-31 15:51:36', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `rating`, `comment`, `photo_path`, `created_at`) VALUES
(2, 13, 4, 'nice work 👍👍 (Highlights: High Quality Work)', NULL, '2026-08-28 08:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','mechanic','admin') NOT NULL DEFAULT 'user',
  `city` varchar(100) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `failed_attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `email`, `password_hash`, `role`, `city`, `photo_path`, `failed_attempts`, `locked_until`, `created_at`) VALUES
(7, 'xalidir', NULL, 'xalidir477@ehwit.com', '$2y$10$Ek.2dYFIXQkmxAIWlKXnf.KXz7KkmWYIoIUZyBSR6z9OdtJnnSHIe', 'user', NULL, '../assets/uploads/profiles/dc77fee63da29720e5735e3d0d1d7eeb.png', 0, NULL, '2026-08-24 06:47:38'),
(8, 'Zahid Electronics', NULL, 'tesew85742@ehwit.com', '$2y$10$O07VizNsFN2qVvpuB0BnMulrvAbATTJBMYhNZAc7B1T0CKayOBGVy', 'mechanic', NULL, NULL, 0, NULL, '2026-08-25 06:35:14'),
(12, 'Tariq Electrical Services', NULL, 'kojipa3496@mediseat.com', '$2y$10$ZWGl8nqdFcQjr6rfywqgS.vx6Mkdl1EH0lxM9QbKPCxcblPnn6LWO', 'mechanic', NULL, NULL, 0, NULL, '2026-08-28 17:35:08'),
(14, 'RepairKar Admin', NULL, 'repairkar81@gmail.com', '$2y$10$Em.J4PeBECqh8YOKD/nNvOewLhx48/TmYt2EACAPV5BgcysqVDsRq', 'admin', NULL, NULL, 0, NULL, '2026-08-29 17:39:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookings_gig` (`gig_id`),
  ADD KEY `idx_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_mechanic` (`mechanic_id`),
  ADD KEY `idx_bookings_status` (`status`);

--
-- Indexes for table `call_signals`
--
ALTER TABLE `call_signals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_call_signals` (`booking_id`,`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contact_user` (`user_id`),
  ADD KEY `idx_contact_status` (`status`),
  ADD KEY `idx_contact_created` (`created_at`);

--
-- Indexes for table `gigs`
--
ALTER TABLE `gigs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gigs_mechanic` (`mechanic_id`),
  ADD KEY `idx_gigs_active` (`active`);

--
-- Indexes for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mechanics_user` (`user_id`),
  ADD KEY `idx_mechanics_category` (`category`),
  ADD KEY `idx_mechanics_verified` (`verified`),
  ADD KEY `idx_mechanics_location` (`lat`,`lng`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_booking` (`booking_id`,`id`),
  ADD KEY `idx_messages_read` (`read_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reviews_booking` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `call_signals`
--
ALTER TABLE `call_signals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gigs`
--
ALTER TABLE `gigs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_gig` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bookings_mechanic` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`),
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `call_signals`
--
ALTER TABLE `call_signals`
  ADD CONSTRAINT `fk_call_signal_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_contact_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gigs`
--
ALTER TABLE `gigs`
  ADD CONSTRAINT `fk_gigs_mechanic` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD CONSTRAINT `fk_mechanics_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
