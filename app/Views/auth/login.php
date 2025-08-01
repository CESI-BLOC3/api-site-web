<?php $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Connexion</h1>
<form method="post" action="<?php echo $base; ?>/login" class="space-y-3 max-w-md">
  <?php echo App\Core\CSRF::input(); ?>
  <label class="block">Email<input name="email" type="email" class="mt-1 w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2" required></label>
  <label class="block">Mot de passe<input name="password" type="password" class="mt-1 w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2" required></label>
  <button class="px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Se connecter</button>
</form>
