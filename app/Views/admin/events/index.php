<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Admin — Événements</h1>
  <div class="flex items-center gap-2">
    <a class="px-3 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900" href="<?php echo $base; ?>/admin/events/create">Créer</a>
    <a class="px-3 py-2 rounded bg-slate-200 dark:bg-slate-800" href="<?php echo $base; ?>/admin/users/create">Créer un membre</a>
  </div>
</div>
<table class="w-full border-collapse">
<thead><tr class="text-left border-b border-slate-200 dark:border-slate-700"><th class="py-2">ID</th><th>Nom</th><th>Date</th><th>Prix</th><th class="w-56">Actions</th></tr></thead>
<tbody>
<?php foreach ($events as $e): ?>
<tr class="border-b border-slate-100 dark:border-slate-800">
  <td class="py-2"><?php echo (int)$e['id']; ?></td>
  <td><?php echo View::e($e['name']); ?></td>
  <td><?php echo View::e($e['event_date']); ?></td>
  <td><?php echo (int)$e['price']; ?> €</td>
  <td class="py-2">
    <a class="text-blue-600 hover:underline mr-3" href="<?php echo $base; ?>/admin/events/<?php echo (int)$e['id']; ?>">Voir</a>
    <a class="text-amber-600 hover:underline mr-3" href="<?php echo $base; ?>/admin/events/<?php echo (int)$e['id']; ?>/edit">Éditer</a>
    <form action="<?php echo $base; ?>/admin/events/<?php echo (int)$e['id']; ?>/delete" method="post" class="inline" onsubmit="return confirm('Supprimer cet événement ?');">
      <?php echo App\Core\CSRF::input(); ?>
      <button class="text-red-600 hover:underline">Supprimer</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
