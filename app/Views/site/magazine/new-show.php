<?php
// Renderiza o preview IDÊNTICO ao admin — página standalone sem CSS do site
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';

$isAdmin = false;
$pages = \App\Models\Magazine::getPages($magazine['id']);

include ROOT_PATH . '/app/Views/admin/magazines/preview.php';
