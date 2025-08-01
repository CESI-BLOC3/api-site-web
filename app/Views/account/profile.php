<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Mon compte</h1>
<div class="grid md:grid-cols-2 gap-6">
  <div>
    <h2 class="font-semibold mb-2">Modifier l'email</h2>
    <form method="post" action="<?php echo $base; ?>/account/email" class="space-y-3">
      <?php echo App\Core\CSRF::input(); ?>
      <input name="email" type="email" value="<?php echo View::e($user['email']); ?>" class="w-full rounded border p-2 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700"/>
      <button class="px-3 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Mettre à jour</button>
    </form>
  </div>
  <div>
    <h2 class="font-semibold mb-2">Changer le mot de passe</h2>
    <form method="post" action="<?php echo $base; ?>/account/password/request" class="space-y-3">
      <?php echo App\Core\CSRF::input(); ?>
      <button class="px-3 py-2 rounded bg-slate-200 dark:bg-slate-800">Envoyer un code par email</button>
    </form>
    <form method="post" action="<?php echo $base; ?>/account/password/reset" class="space-y-3 mt-3">
      <?php echo App\Core\CSRF::input(); ?>
      <input name="code" placeholder="Code reçu" class="w-full rounded border p-2 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700"/>
      <input name="password" type="password" placeholder="Nouveau mot de passe" class="w-full rounded border p-2 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700"/>
      <button class="px-3 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Changer le mot de passe</button>
    </form>
  </div>
</div>
