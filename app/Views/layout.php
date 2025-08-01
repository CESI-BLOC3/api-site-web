<?php use App\Core\View; $cfg = require __DIR__.'/../../config/config.php'; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<!doctype html>
<html lang="fr" class="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo View::e($cfg['app_name']); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' };</script>
<script>
(function(){
  const saved = localStorage.getItem('theme'); const root = document.documentElement;
  if(saved==='light') root.classList.remove('dark'); else root.classList.add('dark');
  window.toggleTheme = function(){
    const isDark = root.classList.contains('dark');
    if(isDark){ root.classList.remove('dark'); localStorage.setItem('theme','light'); }
    else { root.classList.add('dark'); localStorage.setItem('theme','dark'); }
  }
})();
</script>
</head>
<body class="bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
<header class="border-b border-slate-200 dark:border-slate-700">
  <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="<?php echo $base; ?>/" class="flex items-center gap-3 font-semibold">
      <img src="<?php echo $base; ?>/assets/logo.svg" class="h-6" alt="logo"> <?php echo View::e($cfg['app_name']); ?>
    </a>
    <nav class="flex items-center gap-4">
      <a class="hover:underline" href="<?php echo $base; ?>/">Accueil</a>
      <a class="hover:underline" href="<?php echo $base; ?>/admin/events">Admin</a>
      <?php if (!empty($_SESSION['uid'])): ?>
        <a class="hover:underline" href="<?php echo $base; ?>/account">Mon compte</a>
        <?php if (!empty($_SESSION['is_admin'])): ?>
          <a class="hover:underline" href="<?php echo $base; ?>/admin/users/create">Créer un membre</a>
        <?php endif; ?>
        <form action="<?php echo $base; ?>/logout" method="post" class="inline">
          <?php echo App\Core\CSRF::input(); ?>
          <button class="px-3 py-1 rounded bg-slate-200 dark:bg-slate-800">Logout</button>
        </form>
      <?php else: ?>
        <a class="hover:underline" href="<?php echo $base; ?>/login">Connexion</a>
      <?php endif; ?>
      <button onclick="toggleTheme()" class="px-3 py-1 rounded bg-slate-200 dark:bg-slate-800">Theme</button>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-6">
  <?php include __DIR__ . '/partials/flash.php'; ?>
  <?php include __DIR__ . '/' . ($tpl ?? 'home') . '.php'; ?>
</main>
<footer class="max-w-5xl mx-auto px-4 py-6 text-sm text-slate-500">© <?php echo date('Y'); ?> <?php echo View::e($cfg['app_name']); ?></footer>
</body></html>
