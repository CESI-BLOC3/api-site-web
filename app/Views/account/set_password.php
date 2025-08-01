<?php $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Définir un mot de passe</h1>
<form method="post" action="<?php echo $base; ?>/set-password" class="space-y-3 max-w-md">
  <?php echo App\Core\CSRF::input(); ?>
  <input type="hidden" name="code" value="<?php echo htmlspecialchars($code ?? '',ENT_QUOTES); ?>">
  <input name="password" type="password" placeholder="Nouveau mot de passe" class="w-full rounded border p-2 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700"/>
  <button class="px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Valider</button>
</form>
