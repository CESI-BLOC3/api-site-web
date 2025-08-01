<?php $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Créer un membre</h1>
<form method="post" action="<?php echo $base; ?>/admin/users/store" class="space-y-3 max-w-md">
  <?php echo App\Core\CSRF::input(); ?>
  <label class="block">Nom<input name="name" class="w-full rounded border p-2"/></label>
  <label class="block">Email<input name="email" type="email" class="w-full rounded border p-2"/></label>
  <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_admin" value="1"> Administrateur</label>
  <button class="px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Créer</button>
</form>
