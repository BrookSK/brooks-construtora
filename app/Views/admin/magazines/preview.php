<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
$baseUrl = $scheme . '://' . $host;
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch(\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
$_renderContext = [
    'showAdminToolbar' => true,
    'magazineEditUrl'  => '/admin/magazines/edit/' . ($magazine['id'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - <?= htmlspecialchars($magazine['title']) ?></title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" />
</head>
<body style="margin:0;padding:0;font-family:'Inter',sans-serif;background:#333;">
<?php include ROOT_PATH . '/app/Views/shared/_magazine_render.php'; ?>
</body>
</html>
