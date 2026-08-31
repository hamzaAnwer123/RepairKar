<?php
/**
 * Shared <head> for all admin pages. The including page must set
 * $adminPageTitle BEFORE requiring this file. Outputs the document
 * opening through </head>; the page then writes its own <body>.
 *
 * All admin pages share the same stylesheets and animation helpers —
 * keeping them here means a new admin page only needs:
 *   $adminPageTitle = '...';
 *   require_once __DIR__ . '/../includes/admin-head.php';
 */
if (!isset($adminPageTitle) || $adminPageTitle === '') {
    $adminPageTitle = 'RepairKar Admin';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($adminPageTitle) ?></title>
<meta name="robots" content="noindex, nofollow">

<link rel="icon" type="image/x-icon" href="../assets/images/logo/favicon.ico">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Tailwind CSS — compiled via Tailwind CLI. Run `npm run build:css` after adding new classes. -->
<link rel="stylesheet" href="../assets/css/tailwind.min.css">
<link rel="stylesheet" href="../assets/css/loader.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
<script src="../assets/js/loader.js"></script>

<style>
  body { font-family: 'Inter', sans-serif; }

  @media (prefers-reduced-motion: no-preference) {
    .fade-in-up    { animation: fadeInUp 0.4s ease-out both; }
    .fade-in-up-d1 { animation: fadeInUp 0.4s ease-out 0.08s both; }
    .fade-in-up-d2 { animation: fadeInUp 0.4s ease-out 0.16s both; }
    .kpi-card      { animation: fadeInUp 0.35s ease-out both; }
    .kpi-number    { animation: countIn 0.5s ease-out both; }
    .row-enter     { animation: fadeInUp 0.3s ease-out both; }
    .badge-pop     { animation: popIn 0.25s cubic-bezier(0.34,1.56,0.64,1) both; }
    .drawer-in     { animation: drawerIn 0.25s ease-out both; }
    .backdrop-in   { animation: fadeIn 0.2s ease-out both; }
    .row-hover     { transition: background-color 0.12s ease-out; }
    .btn-tap       { transition: transform 0.12s ease-out; }
    .btn-tap:active { transform: scale(0.96); }
    .modal-backdrop-in { animation: fadeIn 0.2s ease-out both; }
    .modal-in      { animation: modalIn 0.25s ease-out both; }
    .row-clear     { transition: opacity 0.3s ease-out, transform 0.3s ease-out, max-height 0.35s ease-out; }
    .toast-in      { animation: toastIn 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
    .error-msg     { animation: fadeInDown 0.2s ease-out both; }
  }
  @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes countIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes popIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
  @keyframes drawerIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes modalIn { from { opacity: 0; transform: translateY(16px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @keyframes toastIn { from { opacity: 0; transform: translateY(10px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @keyframes fadeInDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
