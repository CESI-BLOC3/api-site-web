<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Événements</h1>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach ($events as $e): ?>
  <a class="block rounded border border-slate-200 dark:border-slate-700 p-4 hover:bg-slate-50 dark:hover:bg-slate-800" href="<?php echo $base; ?>/events/<?php echo (int)$e['id']; ?>">
    <div class="font-semibold"><?php echo View::e($e['name']); ?></div>
    <div class="text-slate-500"><?php echo View::e($e['event_date']); ?> · <?php echo (int)$e['price']; ?> €</div>
    <div class="text-sm line-clamp-3"><?php echo View::e(mb_substr(strip_tags($e['description']),0,120)); ?>...</div>
  </a>
<?php endforeach; ?>
</div>
